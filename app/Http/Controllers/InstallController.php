<?php

namespace App\Http\Controllers;

use App\Models\EncryptedTool;
use App\Models\InstallationHistory;
use App\Models\ToolServerAsset;
use App\Services\BDApiService;
use App\Services\LicenseService;
use App\Services\PlainLicenseEnforcer;
use App\Services\ToolEncryptionService;
use App\Services\ToolPayloadBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class InstallController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService,
        protected ToolEncryptionService $encryption,
        protected ToolPayloadBuilder $payloadBuilder,
        protected PlainLicenseEnforcer $plainLicenseEnforcer
    ) {}

    public function showForm(Request $request): View
    {
        // Check for installation success FIRST before any clearing
        $installSuccess = session('install_success', false);
        $installResults = session('install_results', []);
        
        // If fresh=1 is passed, clear all session data to start fresh
        // But don't clear if we just had a successful installation
        if ($request->query('fresh') == '1' && !$installSuccess) {
            session()->forget(['bd_base_url', 'bd_api_key', 'bd_connected', 'install_existing_widgets', 'install_tool_slug', 'install_confirm_needed', 'existing_widgets']);
        }
        
        if (!session('install_confirm_needed', false)) {
            session()->forget(['install_existing_widgets', 'install_tool_slug']);
        }
        $tools = config('tools.registry', []);
        $toolSlug = $this->resolveToolSlug($request);
        
        // DEBUG: Log what we're getting
        $debug = [
            'query_tool' => $request->query('tool'),
            'old_tool' => $request->old('tool_slug'),
            'resolved_toolSlug' => $toolSlug,
            'registry_keys' => array_keys($tools),
        ];
        
        $registry = config("tools.registry.{$toolSlug}");
        $debug['registry'] = $registry ? 'found' : 'null';
        $debug['help_text'] = $registry['help_text'] ?? null;
        
        // Log to see what's happening
        \Illuminate\Support\Facades\Log::debug('InstallController showForm debug', $debug);
        
        $supportsServerFetch = ($registry['delivery_mode'] ?? '') === 'server_fetch';
        $helpText = $registry['help_text'] ?? null;
        
        // Clear the session data after reading (so it shows only once on redirect)
        if ($installSuccess) {
            session()->forget(['install_success', 'install_results']);
        }
        
        return view('install.form', [
            'tools' => $tools,
            'bdBaseUrl' => $request->old('bd_base_url', session('bd_base_url', '')),
            'bdApiKey' => $request->old('bd_api_key', session('bd_api_key', '')),
            'bdConnected' => session('bd_connected', false),
            'licenseToken' => $request->old('license_token', ''),
            'toolSlug' => $toolSlug,
            'installConfirmNeeded' => session('install_confirm_needed', false),
            'existingWidgets' => session('existing_widgets', []),
            'supportsServerFetch' => $supportsServerFetch,
            'helpText' => $helpText,
            'installSuccess' => $installSuccess,
            'installResults' => $installResults,
        ]);
    }

    /**
     * Setup tool: upload server files and optional custom base URL (for server_fetch tools).
     */
    public function setupTool(Request $request)
    {
        $request->validate([
            'tool_slug' => 'required|string|in:' . implode(',', array_keys(config('tools.registry', []))),
            'custom_base_url' => 'nullable|url|max:500',
            'server_files' => 'nullable|array',
            'server_files.*' => 'file|max:10240',
        ]);
        $toolSlug = $request->input('tool_slug');
        $registry = config("tools.registry.{$toolSlug}");
        if (($registry['delivery_mode'] ?? '') !== 'server_fetch') {
            return redirect()->back()->withInput()->with('error', 'This tool does not support server setup.');
        }
        $basePath = 'tool-assets/' . $toolSlug;
        if ($request->hasFile('server_files')) {
            foreach ($request->file('server_files') as $file) {
                if (!$file->isValid()) {
                    continue;
                }
                $name = $file->getClientOriginalName();
                $stored = $file->storeAs($basePath, $name, 'local');
                ToolServerAsset::updateOrCreate(
                    ['tool_slug' => $toolSlug, 'file_name' => $name],
                    ['storage_path' => $stored]
                );
            }
        }
        if ($request->filled('custom_base_url')) {
            ToolServerAsset::updateOrCreate(
                ['tool_slug' => $toolSlug, 'file_name' => ToolServerAsset::BASE_URL_KEY],
                ['custom_base_url' => rtrim($request->input('custom_base_url'), '/'), 'storage_path' => null]
            );
        }
        return redirect()->route('admin.install.form', ['tool' => $toolSlug])
            ->with('success', 'Tool setup saved. You can now run Install.');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'bd_base_url' => 'required|url',
            'bd_api_key' => 'required|string',
        ]);
        $baseUrl = rtrim($request->input('bd_base_url'), '/');
        $apiKey = $request->input('bd_api_key');
        session(['bd_base_url' => $baseUrl, 'bd_api_key' => $apiKey, 'bd_connected' => true]);
        $bd = new BDApiService($baseUrl, $apiKey);
        $result = $bd->verifyToken();
        if ($result['success']) {
            return redirect()->route('admin.install.form')->with('success', 'BD API token is valid.')->withInput();
        }
        session(['bd_connected' => false]);
        return redirect()->back()
            ->withInput()
            ->with('error', 'Token verification failed: ' . ($result['body']['message'] ?? 'HTTP ' . $result['status']));
    }

    public function install(Request $request)
    {
        $request->validate([
            'bd_base_url' => 'required|url',
            'bd_api_key' => 'required|string',
            'tool_slug' => 'required|string|in:' . implode(',', array_keys(config('tools.registry', []))),
            'install_domain' => 'nullable|string|max:255',
        ]);
        $toolSlug = $request->input('tool_slug');
        $registry = config("tools.registry.{$toolSlug}");
        if (!$registry) {
            return redirect()->back()->withInput()->with('error', 'Unknown tool.');
        }
        $isService = ($registry['type'] ?? '') === 'service';
        $plainInstall = $request->boolean('plain_install');
        $enforceLicense = $request->boolean('enforce_license');
        $license = null;
        $licenseToken = $request->input('license_token');

        // For service tools with license, install_domain is required to ensure domain-specific licensing works
        if ($isService && !empty($licenseToken)) {
            $installDomain = $request->input('install_domain');
            if (empty($installDomain)) {
                return redirect()->back()->withInput()->with('error', 'Install Domain is required for licensed tools to ensure the license is bound to the correct website.');
            }
        }

        if ($isService && !$plainInstall) {
            $request->validate(['license_token' => 'required|string']);
            $validation = $this->licenseService->validate($licenseToken, $request->input('install_domain'));
            if (!$validation['valid']) {
                return redirect()->back()->withInput()->with('error', $validation['message']);
            }
            $license = $validation['license'] ?? null;
        } elseif ($plainInstall && $enforceLicense) {
            $request->validate(['license_token' => 'required|string']);
            $validation = $this->licenseService->validate($licenseToken, $request->input('install_domain'));
            if (!$validation['valid']) {
                return redirect()->back()->withInput()->with('error', $validation['message']);
            }
            $license = $validation['license'] ?? null;
        }
        session([
            'bd_base_url' => rtrim($request->input('bd_base_url'), '/'),
            'bd_api_key' => $request->input('bd_api_key'),
        ]);
        $baseUrl = session('bd_base_url');
        $apiKey = session('bd_api_key');
        $bd = new BDApiService($baseUrl, $apiKey);
        $verify = $bd->verifyToken();
        if (!$verify['success']) {
            return redirect()->back()->withInput()->with('error', 'BD token verify failed. Please verify again.');
        }
        $assets = $this->getToolAssets($toolSlug, $isService, $plainInstall && !$enforceLicense ? null : $licenseToken);
        if (empty($assets)) {
            return redirect()->back()->withInput()->with('error', 'No tool assets available. For service tools ensure encrypted payload is stored; for direct tools ensure plugin-assets are built.');
        }
        if ($plainInstall && $enforceLicense && $licenseToken) {
            // Build widget payloads first to get viewport mapping for each asset
            $tempPayloads = $this->payloadBuilder->buildWidgetPayloads($toolSlug, $assets);
            // Build viewport map: asset key => viewport
            $widgetViewportMap = [];
            foreach ($tempPayloads as $payload) {
                $widgetDataKey = null;
                $registry = config("tools.registry.{$toolSlug}");
                if ($registry && !empty($registry['widgets'])) {
                    foreach ($registry['widgets'] as $widget) {
                        if ($widget['widget_name'] === $payload['widget_name']) {
                            $widgetDataKey = $widget['widget_data_key'];
                            break;
                        }
                    }
                }
                if ($widgetDataKey) {
                    $widgetViewportMap[$widgetDataKey] = $payload['widget_viewport'];
                }
            }
            // Wrap each PHP asset based on its widget's viewport
            $checkUrl = rtrim(config('app.url', request()->getSchemeAndHttpHost()), '/') . '/api/license/check';
            $wrappedAssets = [];
            foreach ($assets as $key => $content) {
                if ($this->plainLicenseEnforcer->isPhpAsset($key)) {
                    $viewport = $widgetViewportMap[$key] ?? 'admin';
                    $wrappedAssets[$key] = $this->plainLicenseEnforcer->wrapPhpAssetsWithLicenseCheck(
                        [$key => $content],
                        $checkUrl,
                        $licenseToken,
                        $viewport
                    )[$key];
                } else {
                    $wrappedAssets[$key] = $content;
                }
            }
            $assets = $wrappedAssets;
        }
        $payloads = $this->payloadBuilder->buildWidgetPayloads($toolSlug, $assets);
        if (empty($payloads)) {
            return redirect()->back()->withInput()->with('error', 'No widgets defined for this tool.');
        }

        $widgetIds = session('widget_ids', []);
        if (!is_array($widgetIds)) {
            $widgetIds = [];
        }
        $widgetIdsForTool = $widgetIds[$toolSlug] ?? [];

        if ($request->input('install_confirm') !== 'update') {
            $existingOnSite = [];
            foreach ($payloads as $payload) {
                $found = $bd->getWidgetByProperty('widget_name', $payload['widget_name']);
                if ($found && !empty($found['widget_id'])) {
                    $existingOnSite[] = $found;
                    $widgetIdsForTool[$found['widget_name']] = $found['widget_id'];
                }
            }
            if (!empty($existingOnSite)) {
                session([
                    'install_existing_widgets' => $existingOnSite,
                    'install_tool_slug' => $toolSlug,
                ]);
                return redirect()->back()
                    ->withInput()
                    ->with('install_confirm_needed', true)
                    ->with('existing_widgets', $existingOnSite);
            }
        } else {
            $existing = session('install_existing_widgets', []);
            foreach ($existing as $w) {
                if (!empty($w['widget_name']) && !empty($w['widget_id'])) {
                    $widgetIdsForTool[$w['widget_name']] = $w['widget_id'];
                }
            }
            session()->forget(['install_existing_widgets', 'install_tool_slug']);
        }

        $results = [];
        foreach ($payloads as $payload) {
            $widgetName = $payload['widget_name'];
            $existingId = $widgetIdsForTool[$widgetName] ?? null;
            if ($existingId && is_numeric($existingId)) {
                $update = $bd->updateWidget((int) $existingId, $payload);
                if ($update['success']) {
                    $results[] = ['widget' => $widgetName, 'ok' => true, 'widget_id' => (int) $existingId];
                } else {
                    $create = $bd->createWidget($payload);
                    if ($create['success']) {
                        $widgetIdsForTool[$widgetName] = $create['widget_id'];
                        $results[] = ['widget' => $widgetName, 'ok' => true, 'widget_id' => $create['widget_id']];
                    } else {
                        $results[] = ['widget' => $widgetName, 'ok' => false, 'message' => $create['body']['message'] ?? 'HTTP ' . $create['status']];
                    }
                }
            } else {
                $create = $bd->createWidget($payload);
                if ($create['success']) {
                    $widgetIdsForTool[$widgetName] = $create['widget_id'];
                    $results[] = ['widget' => $widgetName, 'ok' => true, 'widget_id' => $create['widget_id'] ?? null];
                } else {
                    $results[] = ['widget' => $widgetName, 'ok' => false, 'message' => $create['body']['message'] ?? 'HTTP ' . $create['status']];
                }
            }
        }
        $widgetIds[$toolSlug] = $widgetIdsForTool;
        session(['widget_ids' => $widgetIds]);
        $allOk = collect($results)->every(fn ($r) => $r['ok']);

        InstallationHistory::create([
            'license_id' => $license?->id,
            'tool_slug' => $toolSlug,
            'install_domain' => $request->input('install_domain') ?: null,
            'bd_base_url' => $baseUrl,
            'source' => 'web',
            'success' => $allOk,
            'details' => [
                'widgets' => $results,
                'widget_count' => count($results),
            ],
        ]);

        if ($allOk) {
            return redirect()->route('admin.install.form')->with('install_success', true)->with('install_results', $results);
        }
        return redirect()->route('admin.install.form')->with('install_results', $results)->with('warning', 'Some widgets failed.');
    }

    /**
     * Get tool assets: for service tools from encrypted storage (after license check); for direct from plugin-assets path.
     * @return array<string, string>
     */
    protected function getToolAssets(string $toolSlug, bool $isService, ?string $licenseToken): array
    {
        if ($isService) {
            $encrypted = EncryptedTool::where('tool_slug', $toolSlug)->first();
            if (!$encrypted) {
                return [];
            }
            return $this->encryption->decrypt($encrypted->encrypted_payload);
        }
        $path = base_path('plugin-assets');
        if (!File::isDirectory($path)) {
            $path = base_path('../plugin-assets');
        }
        if (!File::isDirectory($path)) {
            $path = storage_path('app/plugin-assets');
        }
        return $this->payloadBuilder->readAssetsFromPath($path);
    }

    private function resolveToolSlug(Request $request): string
    {
        // Priority: 1) query param (tool), 2) old input (tool_slug), 3) default
        $slug = $request->query('tool') ?? $request->old('tool_slug');
        $registry = array_keys(config('tools.registry', []));
        return $slug && in_array($slug, $registry, true) ? $slug : ($registry[0] ?? 'related-posts');
    }
}

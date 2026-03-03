<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EncryptedTool;
use App\Models\InstallationHistory;
use App\Models\ToolServerAsset;
use App\Services\BDApiService;
use App\Services\LicenseService;
use App\Services\PlainLicenseEnforcer;
use App\Services\ServerFetchPayloadPreparer;
use App\Services\ToolEncryptionService;
use App\Services\ToolPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class InstallApiController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService,
        protected ToolEncryptionService $encryption,
        protected ToolPayloadBuilder $payloadBuilder,
        protected ServerFetchPayloadPreparer $serverFetchPreparer,
        protected PlainLicenseEnforcer $plainLicenseEnforcer
    ) {}

    /**
     * GET /api/tools – List available tools (for BDGS to show options).
     * Query params: type (service|direct), product_type (service|quick_service|flagship_service|tool).
     */
    public function tools(Request $request): JsonResponse
    {
        $registry = config('tools.registry', []);
        $list = [];
        $filterType = $request->query('type');
        $filterProductType = $request->query('product_type');
        foreach ($registry as $slug => $config) {
            $type = $config['type'] ?? 'service';
            $productType = $config['product_type'] ?? 'tool';
            if ($filterType !== null && $filterType !== '' && $type !== $filterType) {
                continue;
            }
            if ($filterProductType !== null && $filterProductType !== '' && $productType !== $filterProductType) {
                continue;
            }
            $list[] = [
                'slug' => $slug,
                'name' => $config['name'] ?? $slug,
                'type' => $type,
                'version' => $config['version'] ?? '1.0',
                'product_type' => $productType,
            ];
        }
        return response()->json(['tools' => $list]);
    }

    /**
     * POST /api/verify – Verify BD token. Body: bd_base_url, bd_api_key.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'bd_base_url' => 'required|url',
            'bd_api_key' => 'required|string',
        ]);
        $baseUrl = rtrim($request->input('bd_base_url'), '/');
        $apiKey = $request->input('bd_api_key');
        $bd = new BDApiService($baseUrl, $apiKey);
        $result = $bd->verifyToken();
        if ($result['success']) {
            return response()->json(['success' => true, 'message' => 'Token valid.']);
        }
        return response()->json([
            'success' => false,
            'message' => $result['body']['message'] ?? 'Token verification failed.',
        ], 400);
    }

    /**
     * POST /api/install – Install tool to BD. Body: license_token, bd_base_url, bd_api_key, tool_slug, install_domain?.
     * For use by BDGS after purchase; can be called manually or from BDGS server.
     */
    public function install(Request $request): JsonResponse
    {
        $request->validate([
            'bd_base_url' => 'required|url',
            'bd_api_key' => 'required|string',
            'tool_slug' => 'required|string|in:' . implode(',', array_keys(config('tools.registry', []))),
        ]);
        $toolSlug = $request->input('tool_slug');
        $registry = config("tools.registry.{$toolSlug}");
        if (!$registry) {
            return response()->json(['success' => false, 'message' => 'Unknown tool.'], 400);
        }
        $isService = ($registry['type'] ?? '') === 'service';
        $plainInstall = $request->boolean('plain_install');
        $enforceLicense = $request->boolean('enforce_license');
        $license = null;
        $licenseToken = $request->input('license_token');
        if ($isService && !$plainInstall) {
            $request->validate(['license_token' => 'required|string']);
            $validation = $this->licenseService->validate($licenseToken, $request->input('install_domain'));
            if (!$validation['valid']) {
                return response()->json(['success' => false, 'message' => $validation['message']], 400);
            }
            $license = $validation['license'] ?? null;
        } elseif ($plainInstall && $enforceLicense) {
            $request->validate(['license_token' => 'required|string']);
            $validation = $this->licenseService->validate($licenseToken, $request->input('install_domain'));
            if (!$validation['valid']) {
                return response()->json(['success' => false, 'message' => $validation['message']], 400);
            }
            $license = $validation['license'] ?? null;
        }
        $baseUrl = rtrim($request->input('bd_base_url'), '/');
        $apiKey = $request->input('bd_api_key');
        $bd = new BDApiService($baseUrl, $apiKey);
        $verify = $bd->verifyToken();
        if (!$verify['success']) {
            return response()->json(['success' => false, 'message' => 'BD token verification failed.'], 400);
        }
        $assets = $this->getToolAssets($toolSlug, $isService);
        if (empty($assets)) {
            return response()->json(['success' => false, 'message' => 'No tool assets available.'], 500);
        }
        $deliveryMode = $registry['delivery_mode'] ?? 'default';
        if ($isService && $deliveryMode === 'server_fetch' && !$plainInstall) {
            $serverBaseUrl = ToolServerAsset::getBaseUrlForTool($toolSlug);
            $serverFileNames = ToolServerAsset::getFileNamesForTool($toolSlug);
            $assets = $this->serverFetchPreparer->prepare(
                $assets,
                $licenseToken,
                $serverBaseUrl,
                $toolSlug,
                $serverFileNames
            );
        }
        if ($plainInstall && $enforceLicense && $licenseToken) {
            $checkUrl = rtrim(config('app.url', $request->getSchemeAndHttpHost()), '/') . '/api/license/check';
            $assets = $this->plainLicenseEnforcer->wrapPhpAssetsWithLicenseCheck($assets, $checkUrl, $licenseToken);
        }
        $payloads = $this->payloadBuilder->buildWidgetPayloads($toolSlug, $assets);
        if (empty($payloads)) {
            return response()->json(['success' => false, 'message' => 'No widgets defined for this tool.'], 500);
        }
        $results = [];
        foreach ($payloads as $payload) {
            $create = $bd->createWidget($payload);
            if ($create['success']) {
                $results[] = ['widget_name' => $payload['widget_name'], 'ok' => true, 'widget_id' => $create['widget_id'] ?? null];
            } else {
                $results[] = ['widget_name' => $payload['widget_name'], 'ok' => false, 'message' => $create['body']['message'] ?? 'Failed'];
            }
        }
        $allOk = collect($results)->every(fn ($r) => $r['ok']);

        InstallationHistory::create([
            'license_id' => $license?->id,
            'tool_slug' => $toolSlug,
            'install_domain' => $request->input('install_domain') ?: null,
            'bd_base_url' => $baseUrl,
            'source' => 'api',
            'success' => $allOk,
            'details' => [
                'widgets' => $results,
                'widget_count' => count($results),
            ],
        ]);

        return response()->json([
            'success' => $allOk,
            'message' => $allOk ? 'Install completed.' : 'Some widgets failed.',
            'results' => $results,
        ], $allOk ? 200 : 207);
    }

    protected function getToolAssets(string $toolSlug, bool $isService): array
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
}

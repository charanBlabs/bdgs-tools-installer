<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ToolDocumentation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolCatalogController extends Controller
{
    /**
     * List all tools from config as image cards. Product type filter.
     */
    public function index(Request $request): View
    {
        $registry = config('tools.registry', []);
        $productType = $request->query('product_type');
        if ($productType !== null && $productType !== '') {
            $registry = array_filter($registry, fn ($c) => ($c['product_type'] ?? 'tool') === $productType);
        }
        $docs = ToolDocumentation::whereIn('tool_slug', array_keys($registry))->get()->keyBy('tool_slug');
        return view('admin.tools.index', [
            'tools' => $registry,
            'docs' => $docs,
        ]);
    }

    /**
     * Show single tool: large image, carousel, versions, install guide, features, etc.
     */
    public function show(string $toolSlug): View
    {
        $config = config("tools.registry.{$toolSlug}");
        if (!$config) {
            abort(404, 'Tool not found.');
        }
        $doc = ToolDocumentation::firstOrCreate(
            ['tool_slug' => $toolSlug],
            ['installation_type' => 'quick_install']
        );
        $widgetCount = count($config['widgets'] ?? []);
        $filesUsed = $doc->files_used ?? $doc->getFilesUsedFromConfig();
        if (empty($filesUsed)) {
            $filesUsed = $doc->getFilesUsedFromConfig();
        }
        return view('admin.tools.show', [
            'toolSlug' => $toolSlug,
            'config' => $config,
            'doc' => $doc,
            'widgetCount' => $widgetCount,
            'filesUsed' => $filesUsed,
        ]);
    }

    /**
     * Edit tool documentation (installation steps, feature notes, images, etc.)
     */
    public function edit(string $toolSlug): View
    {
        $config = config("tools.registry.{$toolSlug}");
        if (!$config) {
            abort(404, 'Tool not found.');
        }
        $doc = ToolDocumentation::firstOrCreate(
            ['tool_slug' => $toolSlug],
            ['installation_type' => 'quick_install']
        );
        return view('admin.tools.edit', [
            'toolSlug' => $toolSlug,
            'config' => $config,
            'doc' => $doc,
        ]);
    }

    public function update(Request $request, string $toolSlug)
    {
        $config = config("tools.registry.{$toolSlug}");
        if (!$config) {
            abort(404, 'Tool not found.');
        }
        $doc = ToolDocumentation::firstOrCreate(
            ['tool_slug' => $toolSlug],
            ['installation_type' => 'quick_install']
        );
        $request->validate([
            'image_url' => 'nullable|string|max:500',
            'screenshots' => 'nullable|string', // JSON string or newline-separated URLs
            'installation_type' => 'required|in:quick_install,installer_plus_manual,manual_only',
            'installation_steps' => 'nullable|string|max:10000',
            'manual_steps' => 'nullable|string|max:10000',
            'features' => 'nullable|string|max:5000',
            'ui_description' => 'nullable|string|max:5000',
            'feature_notes' => 'nullable|string|max:10000',
            'files_used' => 'nullable|string|max:2000',
            'version_notes' => 'nullable|string|max:5000',
        ]);
        $screenshots = $request->input('screenshots');
        if (is_string($screenshots)) {
            $screenshots = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $screenshots))));
        }
        $features = $request->input('features');
        if (is_string($features)) {
            $features = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $features))));
        }
        $filesUsed = $request->input('files_used');
        if (is_string($filesUsed)) {
            $filesUsed = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $filesUsed))));
        }
        $doc->image_url = $request->input('image_url') ?: null;
        $doc->screenshots = $screenshots ?: null;
        $doc->installation_type = $request->input('installation_type');
        $doc->installation_steps = $request->input('installation_steps') ?: null;
        $doc->manual_steps = $request->input('manual_steps') ?: null;
        $doc->features = $features ?: null;
        $doc->ui_description = $request->input('ui_description') ?: null;
        $doc->feature_notes = $request->input('feature_notes') ?: null;
        $doc->files_used = $filesUsed ?: null;
        $doc->version_notes = $request->input('version_notes') ?: null;
        $doc->save();
        return redirect()->route('admin.tools.show', $toolSlug)->with('success', 'Documentation updated.');
    }
}

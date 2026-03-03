<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class ToolPayloadBuilder
{
    /**
     * Build BD widget payloads for a tool from plugin-assets (plain files) or from decrypted payload.
     * @param array<string, string> $assets map of asset key => content (e.g. admin.php => content)
     * @return array<int, array<string, mixed>>
     */
    public function buildWidgetPayloads(string $toolSlug, array $assets): array
    {
        $registry = config("tools.registry.{$toolSlug}");
        if (!$registry || empty($registry['widgets'])) {
            return [];
        }
        $payloads = [];
        foreach ($registry['widgets'] as $widget) {
            $widgetName = $widget['widget_name'];
            $data = $assets[$widget['widget_data_key']] ?? '';
            $style = $widget['widget_style_key'] ? ($assets[$widget['widget_style_key']] ?? '') : '';
            $javascript = $widget['widget_javascript_key'] ? ($assets[$widget['widget_javascript_key']] ?? '') : '';
            $payloads[] = [
                'widget_name' => $widgetName,
                'widget_data' => $data,
                'widget_style' => $style,
                'widget_javascript' => $javascript,
                'widget_viewport' => $widget['widget_viewport'] ?? 'front',
            ];
        }
        return $payloads;
    }

    /**
     * Read plugin-assets from disk (for direct tools or when not using encrypted storage).
     */
    public function readAssetsFromPath(string $path): array
    {
        $assets = [];
        if (!File::isDirectory($path)) {
            return $assets;
        }
        foreach (File::files($path) as $file) {
            $key = $file->getFilename();
            $assets[$key] = File::get($file->getPathname());
        }
        return $assets;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\ToolPayloadBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Prepares FAQ tool files in encrypted/prepared format:
 * - PHP files (admin.php, global-renderer.php) as base64-encoded .b64 files
 * - CSS and JS as separate plain files
 * Output: storage/app/faq-prepared/
 */
class PrepareFaqAssetsCommand extends Command
{
    protected $signature = 'tools:prepare-faq
                            {--assets= : Path to plugin-assets (default: plugin-assets)}
                            {--output= : Output directory (default: storage/app/faq-prepared)}';

    protected $description = 'Prepare FAQ tool: base64 PHP files and separate CSS/JS for encrypted delivery.';

    public function handle(ToolPayloadBuilder $payloadBuilder): int
    {
        $path = $this->option('assets') ?? base_path('plugin-assets');
        if ($path !== '' && !preg_match('#^([A-Za-z]:\\\\|/)#', $path)) {
            $path = base_path($path);
        }
        if (!File::isDirectory($path)) {
            $this->error("Assets path not found: {$path}");
            return self::FAILURE;
        }

        $assets = $payloadBuilder->readAssetsFromPath($path);
        if (empty($assets)) {
            $this->error('No files found in ' . $path);
            return self::FAILURE;
        }

        $outDir = $this->option('output') ?? storage_path('app/faq-prepared');
        if (!File::isDirectory($outDir)) {
            File::makeDirectory($outDir, 0755, true);
        }

        $registry = config('tools.registry.faq');
        if (!$registry || empty($registry['widgets'])) {
            $this->warn('FAQ registry not found; processing all assets as PHP base64 + CSS/JS separate.');
        }

        $phpKeys = [];
        $styleKeys = [];
        $jsKeys = [];
        if (!empty($registry['widgets'])) {
            foreach ($registry['widgets'] as $w) {
                if (!empty($w['widget_data_key'])) {
                    $phpKeys[$w['widget_data_key']] = true;
                }
                if (!empty($w['widget_style_key'])) {
                    $styleKeys[$w['widget_style_key']] = true;
                }
                if (!empty($w['widget_javascript_key'])) {
                    $jsKeys[$w['widget_javascript_key']] = true;
                }
            }
        }

        foreach ($assets as $key => $content) {
            $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
            $isPhp = $ext === 'php' || isset($phpKeys[$key]);
            $isCss = $ext === 'css' || isset($styleKeys[$key]);
            $isJs = $ext === 'js' || isset($jsKeys[$key]);

            if ($isPhp) {
                $outFile = $outDir . '/' . $key . '.b64';
                File::put($outFile, base64_encode($content));
                $this->line("  base64: {$key} -> " . basename($outFile));
            } elseif ($isCss || $isJs) {
                $outFile = $outDir . '/' . $key;
                File::put($outFile, $content);
                $this->line("  separate: {$key}");
            } else {
                $outFile = $outDir . '/' . $key . '.b64';
                File::put($outFile, base64_encode($content));
                $this->line("  base64: {$key} -> " . basename($outFile));
            }
        }

        $manifest = [
            'generated' => now()->toIso8601String(),
            'source_path' => $path,
            'php_base64' => array_keys(array_filter($assets, fn ($_, $k) => str_ends_with(strtolower($k), '.php'), ARRAY_FILTER_USE_BOTH)),
            'css_plain' => array_keys(array_filter($assets, fn ($_, $k) => str_ends_with(strtolower($k), '.css'), ARRAY_FILTER_USE_BOTH)),
            'js_plain' => array_keys(array_filter($assets, fn ($_, $k) => str_ends_with(strtolower($k), '.js'), ARRAY_FILTER_USE_BOTH)),
        ];
        File::put($outDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        $this->info("Prepared FAQ assets in: {$outDir}");
        return self::SUCCESS;
    }
}

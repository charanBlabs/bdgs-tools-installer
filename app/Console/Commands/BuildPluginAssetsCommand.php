<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BuildPluginAssetsCommand extends Command
{
    protected $signature = 'tools:build-assets
                            {--source= : Path to FAQ Tool repo root (default: parent of this app)}
                            {--output= : Output directory (default: plugin-assets inside this app)}';

    protected $description = 'Copy FAQ plugin files (admin PHP/CSS, frontend PHP) into plugin-assets for install.';

    public function handle(): int
    {
        $source = $this->option('source');
        if ($source === null) {
            // FAQ Management Plugin is in sibling folder when Tool-Installer is at repo root
            $sibling = base_path('../FAQ Management Plugin');
            if (File::isDirectory($sibling . DIRECTORY_SEPARATOR . 'admin') && File::isDirectory($sibling . DIRECTORY_SEPARATOR . 'frontend')) {
                $source = realpath($sibling) ?: $sibling;
            } else {
                $candidate = base_path('../../');
                if (File::isDirectory($candidate . 'admin') && File::isDirectory($candidate . 'frontend')) {
                    $source = realpath($candidate) ?: $candidate;
                } else {
                    $source = base_path('..');
                }
            }
        }
        if (!File::isDirectory($source)) {
            $this->error("Source directory not found: {$source}");
            return self::FAILURE;
        }
        $out = $this->option('output') ?? base_path('plugin-assets');
        if (!File::isDirectory($out)) {
            File::makeDirectory($out, 0755, true);
        }
        $maps = [
            ['admin/FAQ Management Plugin.php', 'admin.php'],
            ['admin/FAQ Management Plugin.css', 'admin.css'],
            ['frontend/FAQ Global Renderer.php', 'global-renderer.php'],
        ];
        foreach ($maps as [$srcRel, $destName]) {
            $srcPath = rtrim($source, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $srcRel);
            if (File::exists($srcPath)) {
                File::copy($srcPath, $out . DIRECTORY_SEPARATOR . $destName);
                $this->line("Copied {$srcRel} -> " . $destName);
            } else {
                $this->warn("Skip (not found): {$srcPath}");
            }
        }
        $this->info("Done. Plugin assets in: {$out}");
        return self::SUCCESS;
    }
}

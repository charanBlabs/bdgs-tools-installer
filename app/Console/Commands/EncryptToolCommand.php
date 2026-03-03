<?php

namespace App\Console\Commands;

use App\Models\EncryptedTool;
use App\Services\ToolEncryptionService;
use App\Services\ToolPayloadBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EncryptToolCommand extends Command
{
    protected $signature = 'tools:encrypt {slug=faq : Tool slug to encrypt}
                            {--assets= : Path to plugin-assets (default: ../plugin-assets)}';

    protected $description = 'Encrypt a service-based tool and store in DB (for license-gated install).';

    public function handle(ToolEncryptionService $encryption, ToolPayloadBuilder $payloadBuilder): int
    {
        $slug = $this->argument('slug');
        if (!config("tools.registry.{$slug}") || (config("tools.registry.{$slug}.type") ?? '') !== 'service') {
            $this->warn("Tool {$slug} is not registered as service-based.");
        }
        $path = $this->option('assets') ?? base_path('plugin-assets');
        // Resolve relative paths against project root
        if ($path !== '' && !preg_match('#^([A-Za-z]:\\\\|/)#', $path)) {
            $path = base_path($path);
        }
        if (!File::isDirectory($path)) {
            $path = base_path('../plugin-assets');
        }
        if (!File::isDirectory($path)) {
            $path = storage_path('app/plugin-assets');
        }
        if (!File::isDirectory($path)) {
            $this->error("Plugin assets path not found. Run php artisan tools:build-assets first.");
            return self::FAILURE;
        }
        $assets = $payloadBuilder->readAssetsFromPath($path);
        if (empty($assets)) {
            $this->error("No files found in {$path}");
            return self::FAILURE;
        }
        $encrypted = $encryption->encrypt($assets);
        EncryptedTool::updateOrCreate(
            ['tool_slug' => $slug],
            ['encrypted_payload' => $encrypted]
        );
        $this->info("Encrypted tool '{$slug}' and stored in encrypted_tools.");
        return self::SUCCESS;
    }
}

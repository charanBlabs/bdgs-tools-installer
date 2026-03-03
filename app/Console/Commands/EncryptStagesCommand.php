<?php

namespace App\Console\Commands;

use App\Models\EncryptedTool;
use App\Models\ToolServerAsset;
use App\Services\ServerFetchPayloadPreparer;
use App\Services\ToolEncryptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EncryptStagesCommand extends Command
{
    protected $signature = 'tools:encrypt-stages {slug=faq : Tool slug}
        {--token= : License token (required for stage 2/3 output)}
        {--output= : Output directory (default: storage/app/debug)}';

    protected $description = 'Export encryption stage payloads (stage 1, 2, 3) for step-by-step debugging.';

    public function handle(
        ToolEncryptionService $encryption,
        ServerFetchPayloadPreparer $preparer
    ): int {
        $slug = $this->argument('slug');
        $registry = config("tools.registry.{$slug}");
        if (!$registry || empty($registry['widgets'])) {
            $this->error("Unknown tool or no widgets: {$slug}");
            return self::FAILURE;
        }
        if (($registry['delivery_mode'] ?? '') !== 'server_fetch') {
            $this->error("Tool {$slug} is not server_fetch. This command is for server_fetch tools only.");
            return self::FAILURE;
        }

        $encrypted = EncryptedTool::where('tool_slug', $slug)->first();
        if (!$encrypted) {
            $this->error("No encrypted payload for {$slug}. Run php artisan tools:encrypt {$slug} first.");
            return self::FAILURE;
        }

        $assets = $encryption->decrypt($encrypted->encrypted_payload);
        if (empty($assets)) {
            $this->error("Decrypted assets empty for {$slug}.");
            return self::FAILURE;
        }

        $token = $this->option('token') ?: '';
        $serverBaseUrl = ToolServerAsset::getBaseUrlForTool($slug);
        $serverFileNames = ToolServerAsset::getFileNamesForTool($slug);

        $outDir = $this->option('output') ?: storage_path('app/debug');
        if (!preg_match('#^([A-Za-z]:\\\\|/)#', $outDir)) {
            $outDir = base_path($outDir);
        }
        if (!File::isDirectory($outDir)) {
            File::makeDirectory($outDir, 0755, true);
        }

        $written = [];
        foreach ([1, 2, 3] as $stage) {
            if ($stage !== 1 && $token === '') {
                $this->warn("Skipping stage {$stage} (no --token provided).");
                continue;
            }
            $modified = $preparer->prepare(
                $assets,
                $token,
                $serverBaseUrl,
                $slug,
                $serverFileNames,
                $stage,
                false
            );
            foreach ($registry['widgets'] as $widget) {
                $dataKey = $widget['widget_data_key'] ?? null;
                if (!$dataKey || !isset($modified[$dataKey])) {
                    continue;
                }
                $baseName = pathinfo($dataKey, PATHINFO_FILENAME);
                $safe = preg_replace('/[^a-z0-9_-]/i', '-', $baseName);
                $fileName = "{$slug}-{$safe}-stage{$stage}.php";
                $path = $outDir . DIRECTORY_SEPARATOR . $fileName;
                File::put($path, $modified[$dataKey]);
                $written[] = $path;
            }
        }

        $this->info('Wrote ' . count($written) . ' file(s) to ' . $outDir);
        foreach ($written as $p) {
            $this->line('  ' . $p);
        }
        return self::SUCCESS;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ToolServerAsset;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Serves uploaded tool server assets. API route validates license token.
 */
class ToolAssetController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    /**
     * Serve a tool asset. Validates license_token query param so only licensed clients can fetch.
     */
    public function serve(Request $request, string $toolSlug, string $fileName): Response|JsonResponse
    {
        $token = $request->query('license_token');
        if (!$token) {
            return response()->json(['error' => 'Missing license_token'], 400);
        }
        $validation = $this->licenseService->validate($token, $request->query('install_domain'));
        if (!$validation['valid']) {
            return response()->json(['error' => 'Invalid or expired license'], 403);
        }
        $asset = ToolServerAsset::where('tool_slug', $toolSlug)
            ->where('file_name', $fileName)
            ->where('file_name', '!=', ToolServerAsset::BASE_URL_KEY)
            ->first();
        if (!$asset || !$asset->storage_path) {
            return response()->json(['error' => 'Not found'], 404);
        }
        if (!Storage::disk('local')->exists($asset->storage_path)) {
            return response()->json(['error' => 'File missing'], 404);
        }
        $mime = $this->mimeForExtension(pathinfo($fileName, PATHINFO_EXTENSION));
        $content = Storage::disk('local')->get($asset->storage_path);
        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
        ]);
    }

    private function mimeForExtension(string $ext): string
    {
        return match (strtolower($ext)) {
            'js' => 'application/javascript',
            'json' => 'application/json',
            'css' => 'text/css',
            'html', 'htm' => 'text/html',
            default => 'application/octet-stream',
        };
    }
}

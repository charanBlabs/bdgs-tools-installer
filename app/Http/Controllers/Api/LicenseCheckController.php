<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public API for widgets to check license validity (e.g. plain-code installs with license enforcement).
 * No auth required; the license token is the credential.
 */
class LicenseCheckController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    /**
     * GET or POST /api/license/check
     * GET: ?license_token=...&domain=... (widget uses GET for maximum compatibility)
     * POST: Body or form: license_token (required), domain (optional).
     * Returns: valid, message, expires_at (if set).
     */
    public function check(Request $request): JsonResponse
    {
        $token = $request->input('license_token') ?? $request->query('license_token');
        if (empty($token) || !is_string($token)) {
            return response()->json(['valid' => false, 'message' => 'license_token required.', 'expires_at' => null], 400);
        }
        $domain = $request->input('domain') ?: $request->query('domain');
        if ($domain !== null && !is_string($domain)) {
            $domain = null;
        }

        $result = $this->licenseService->validate($token, $domain);
        $license = $result['license'];

        $payload = [
            'valid' => $result['valid'],
            'message' => $result['message'],
        ];
        $untilUtc = $license ? $license->getValidUntilUtc() : null;
        if ($untilUtc) {
            $payload['expires_at'] = $untilUtc->format('Y-m-d H:i:s');
        } else {
            $payload['expires_at'] = null;
        }

        return response()->json($payload);
    }
}

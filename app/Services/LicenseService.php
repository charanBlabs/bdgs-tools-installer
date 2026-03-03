<?php

namespace App\Services;

use App\Models\License;

class LicenseService
{
    /**
     * Validate a license token (and optional domain). Returns validation result.
     * @return array{valid: bool, license: License|null, message: string}
     */
    public function validate(string $token, ?string $domain = null): array
    {
        $license = License::where('token', $token)->first();
        if (!$license) {
            return ['valid' => false, 'license' => null, 'message' => 'License not found.'];
        }
        $reason = $license->getInvalidReason($domain);
        if ($reason !== null) {
            return ['valid' => false, 'license' => $license, 'message' => $reason];
        }
        return ['valid' => true, 'license' => $license, 'message' => 'Valid.'];
    }

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}

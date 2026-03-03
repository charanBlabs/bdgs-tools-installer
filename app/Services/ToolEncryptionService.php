<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class ToolEncryptionService
{
    public function __construct(
        protected ?string $encryptionKey = null
    ) {
        $this->encryptionKey = $encryptionKey ?? config('app.key');
    }

    /**
     * Encrypt a payload (e.g. JSON of widget contents). Uses Laravel's encryption.
     * @param array<string, string> $payload e.g. ['admin.php' => '...', 'admin.css' => '...']
     */
    public function encrypt(array $payload): string
    {
        return Crypt::encryptString(json_encode($payload));
    }

    /**
     * Decrypt and return the payload array.
     * @return array<string, string>
     */
    public function decrypt(string $encrypted): array
    {
        $json = Crypt::decryptString($encrypted);
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}

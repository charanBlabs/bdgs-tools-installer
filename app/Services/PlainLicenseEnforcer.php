<?php

namespace App\Services;

/**
 * Wraps plain PHP widget assets with a runtime license check.
 * Used when installing with "plain code" + "enforce license".
 */
class PlainLicenseEnforcer
{
    /**
     * Wrap PHP assets with a license check preamble. Non-PHP keys are left unchanged.
     *
     * @param array<string, string> $assets Map of asset key => content (e.g. admin.php => content)
     * @param string $checkUrl Full URL to POST to (e.g. https://installer.example.com/api/license/check)
     * @param string $licenseToken License token to send
     * @return array<string, string>
     */
    public function wrapPhpAssetsWithLicenseCheck(array $assets, string $checkUrl, string $licenseToken): array
    {
        $preamble = $this->buildPreamble($checkUrl, $licenseToken);

        $out = [];
        foreach ($assets as $key => $content) {
            if ($this->isPhpAsset($key)) {
                $out[$key] = $preamble . "\n" . ltrim($content);
            } else {
                $out[$key] = $content;
            }
        }
        return $out;
    }

    private function isPhpAsset(string $key): bool
    {
        return str_ends_with(strtolower($key), '.php');
    }

    private function buildPreamble(string $checkUrl, string $licenseToken): string
    {
        $checkUrl = rtrim($checkUrl, '/');
        $tokenEscaped = addslashes($licenseToken);
        $timeout = 8;

        return <<<PHP
<?php
\$__lic_base = '{$checkUrl}';
\$__lic_token = '{$tokenEscaped}';
\$__lic_domain = isset(\$_SERVER['HTTP_HOST']) ? \$_SERVER['HTTP_HOST'] : '';
\$__lic_url = \$__lic_base . '?license_token=' . urlencode(\$__lic_token) . '&domain=' . urlencode(\$__lic_domain);
\$__lic_raw = false;
if (function_exists('curl_init')) {
    \$__lic_ch = curl_init(\$__lic_url);
    if (\$__lic_ch) {
        curl_setopt_array(\$__lic_ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => {$timeout},
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'ngrok-skip-browser-warning: 1',
                'Accept: application/json',
            ],
        ]);
        \$__lic_raw = curl_exec(\$__lic_ch);
        curl_close(\$__lic_ch);
    }
}
if (\$__lic_raw === false && function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
    \$__lic_opts = ['http' => ['method' => 'GET', 'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\\r\\nngrok-skip-browser-warning: 1\\r\\nAccept: application/json", 'timeout' => {$timeout}]];
    \$__lic_raw = @file_get_contents(\$__lic_url, false, stream_context_create(\$__lic_opts));
}
\$__lic_ok = false;
\$__lic_r = null;
if (\$__lic_raw !== false && \$__lic_raw !== '') {
    \$__lic_raw = trim(\$__lic_raw);
    \$__lic_start = strpos(\$__lic_raw, '{');
    if (\$__lic_start !== false) {
        \$__lic_end = strrpos(\$__lic_raw, '}');
        if (\$__lic_end !== false && \$__lic_end >= \$__lic_start) {
            \$__lic_json = substr(\$__lic_raw, \$__lic_start, \$__lic_end - \$__lic_start + 1);
            \$__lic_r = @json_decode(\$__lic_json, true);
            \$__lic_ok = !empty(\$__lic_r['valid']);
        }
    }
    if (!\$__lic_ok) {
        \$__lic_r = @json_decode(\$__lic_raw, true);
        \$__lic_ok = !empty(\$__lic_r['valid']);
    }
}
if (!\$__lic_ok) {
    if (!empty(\$_GET['license_debug'])) {
        echo '<div class="tool-license-notice" style="padding:1em;background:#e0f2fe;border:1px solid #0284c7;border-radius:6px;margin:0.5em 0;font-family:monospace;font-size:12px;white-space:pre-wrap;word-break:break-all;">';
        echo 'License check debug. Response received: ' . (\$__lic_raw === false ? 'FALSE (request failed)' : 'length ' . strlen(\$__lic_raw) . '. First 600 chars: ' . htmlspecialchars(substr(\$__lic_raw, 0, 600)));
        echo '</div>';
        return;
    }
    \$__lic_msg = 'Your license has expired or is invalid. Please renew to continue using this feature.';
    if (\$__lic_raw === false) {
        \$__lic_msg = 'License could not be verified: the license server is unreachable from this site. Use a public URL for your installer (set APP_URL), not localhost.';
    }
    echo '<div class="tool-license-notice" style="padding:1em;background:#fef3c7;border:1px solid #f59e0b;border-radius:6px;margin:0.5em 0;">' . htmlspecialchars(\$__lic_msg) . '</div>';
    return;
}
?>
PHP;
    }
}

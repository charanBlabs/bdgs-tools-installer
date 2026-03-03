<?php

namespace App\Services;

/**
 * Prepares assets for server_fetch delivery: base64 main PHP, encrypted helper, plain or PHP-wrapped CSS, empty JS.
 * Used at install time after decrypting from EncryptedTool.
 * Supports staged encryption (1=no encrypt, 2=encrypt minimal main, 3=full) for step-by-step debugging.
 */
class ServerFetchPayloadPreparer
{
    public const DATA_SEPARATOR = "\n---DATA---\n";

    /** Minimal main PHP used in Stage 2 to verify decrypt + helper + eval(main) path without real widget code. */
    public const MINIMAL_MAIN_STAGE2 = "<?php echo '<!-- Stage 2 main OK -->';";

    public function __construct(
        protected LicenseBoundEncryption $licenseEncryption
    ) {}

    /**
     * Produce modified assets: widget_data keys get encrypted(helper + base64(main PHP)); style plain or PHP-wrapped; JS empty.
     *
     * @param array<string, string> $assets Raw decrypted assets (filename => content)
     * @param string $licenseToken License token for encryption
     * @param string $serverBaseUrl Base URL for fetching HTML/JS (no trailing slash)
     * @param string $toolSlug Tool slug for config lookup
     * @param array<int, string> $serverFileNames Optional list of file names to fetch (e.g. ['faq-render.html', 'faq-render.js'])
     * @param int $encryptionStage 1=no encrypt (plain base64), 2=encrypt with minimal main, 3=full (default)
     * @param bool $emitStepMarkers When true, runner outputs HTML comments so you can see which step ran (e.g. STEP 1: token_ok)
     * @return array<string, string> Modified assets to pass to buildWidgetPayloads
     */
    public function prepare(
        array $assets,
        string $licenseToken,
        string $serverBaseUrl,
        string $toolSlug,
        array $serverFileNames = [],
        int $encryptionStage = 3,
        bool $emitStepMarkers = false
    ): array {
        $registry = config("tools.registry.{$toolSlug}");
        if (!$registry || empty($registry['widgets'])) {
            return $assets;
        }
        $cssPlain = $registry['server_fetch']['css_plain'] ?? true;
        $modified = $assets;

        foreach ($registry['widgets'] as $widget) {
            $dataKey = $widget['widget_data_key'] ?? null;
            $styleKey = $widget['widget_style_key'] ?? null;
            $jsKey = $widget['widget_javascript_key'] ?? null;

            if ($dataKey && isset($assets[$dataKey])) {
                $mainPhp = $assets[$dataKey];
                $splitLine = isset($widget['php_only_until_line']) ? (int) $widget['php_only_until_line'] : 0;
                if ($splitLine > 0) {
                    $lines = explode("\n", $mainPhp);
                    $phpPart = implode("\n", array_slice($lines, 0, $splitLine));
                    $remainingPart = implode("\n", array_slice($lines, $splitLine));
                    $mainPhp = null; // use phpPart + remainingPart for blob
                }
                $helper = $this->buildHelperPhp($serverBaseUrl, $serverFileNames, $emitStepMarkers, $splitLine > 0);

                if ($splitLine > 0) {
                    $blob = $helper . self::DATA_SEPARATOR . base64_encode($phpPart) . self::DATA_SEPARATOR . base64_encode($remainingPart);
                } else {
                    $blob = $helper . self::DATA_SEPARATOR . base64_encode($mainPhp);
                }

                if ($encryptionStage === 1) {
                    $payloadB64 = base64_encode($blob);
                    $modified[$dataKey] = $this->buildWidgetRunnerPhpStage1($payloadB64, $toolSlug, $emitStepMarkers, $splitLine > 0);
                } else {
                    if ($splitLine <= 0) {
                        $mainForStage = ($encryptionStage === 2) ? self::MINIMAL_MAIN_STAGE2 : $mainPhp;
                        $blob = $helper . self::DATA_SEPARATOR . base64_encode($mainForStage);
                    }
                    $encryptedB64 = $this->licenseEncryption->encryptForClient($blob, $licenseToken);
                    $modified[$dataKey] = $this->buildWidgetRunnerPhp($encryptedB64, $toolSlug, $emitStepMarkers, $splitLine > 0);
                }
            }

            if ($styleKey && isset($assets[$styleKey])) {
                if (!$cssPlain) {
                    $modified[$styleKey] = "<?php echo base64_decode('" . base64_encode($assets[$styleKey]) . "'); ?>";
                }
            }

            if ($jsKey) {
                $modified[$jsKey] = '';
            }
        }

        return $modified;
    }

    /**
     * Build the PHP script sent to BD: contains <?php, decrypts with token, then evals helper (which runs main PHP).
     * BD will execute this as PHP so the widget works.
     */
    protected function buildWidgetRunnerPhp(string $encryptedB64, string $toolSlug, bool $emitStepMarkers = false): string
    {
        $salt = addslashes(LicenseBoundEncryption::CLIENT_SALT);
        $encryptedEscaped = addslashes($encryptedB64);
        $sepB64 = base64_encode(self::DATA_SEPARATOR);
        $constName = strtoupper(str_replace('-', '_', $toolSlug)) . '_LICENSE_TOKEN';
        $optKey = str_replace('-', '_', $toolSlug) . '_license_token';
        $optKeyEscaped = addslashes($optKey);
        $constNameEscaped = addslashes($constName);
        $toolLabel = addslashes(ucfirst(str_replace('-', ' ', $toolSlug)));
        $s1 = $emitStepMarkers ? "echo '<!-- STEP 1: token_ok -->';" : '';
        $s2 = $emitStepMarkers ? "echo '<!-- STEP 2: decrypt_ok -->';" : '';
        $s3 = $emitStepMarkers ? "echo '<!-- STEP 3: helper_eval_ok -->';" : '';
        return <<<PHP
<?php
\$encrypted_b64 = '{$encryptedEscaped}';
\$token = (function () {
    if (defined('{$constNameEscaped}')) return constant('{$constNameEscaped}');
    \$k = '{$optKeyEscaped}';
    if (isset(\$GLOBALS[\$k]) && \$GLOBALS[\$k] !== '') return \$GLOBALS[\$k];
    if (function_exists('get_option')) { \$t = get_option(\$k, ''); if (\$t !== '') return \$t; }
    if (function_exists('get_site_option')) { \$t = get_site_option(\$k, ''); if (\$t !== '') return \$t; }
    return '';
})();
if (\$token === '') { echo '<!-- {$toolLabel}: Set license token (define {$constNameEscaped} or get_option(\'{$optKeyEscaped}\')) -->'; return; }
{$s1}
\$key = hash_hmac('sha256', \$token, '{$salt}', true);
\$raw = base64_decode(\$encrypted_b64, true);
if (\$raw === false || strlen(\$raw) < 17) { echo '<!-- {$toolLabel}: Invalid payload -->'; return; }
\$iv = substr(\$raw, 0, 16);
\$ciphertext = substr(\$raw, 16);
\$payload = openssl_decrypt(\$ciphertext, 'aes-256-cbc', \$key, OPENSSL_RAW_DATA, \$iv);
if (\$payload === false) { echo '<!-- {$toolLabel}: Decrypt failed (wrong token?) -->'; return; }
{$s2}
\$sep = base64_decode('{$sepB64}');
\$parts = explode(\$sep, \$payload, 3);
\$main_b64 = isset(\$parts[1]) ? \$parts[1] : '';
\$remaining_b64 = isset(\$parts[2]) ? \$parts[2] : '';
\$helper = isset(\$parts[0]) ? \$parts[0] : '';
if (\$helper !== '') { \$helper = preg_replace('/^\\s*<\\?php\\s*/', '', \$helper); eval(\$helper); }
{$s3}
PHP;
    }

    /**
     * Stage 1 runner: no encryption, no token. Payload is literal base64(helper + SEP + base64(main) [or + SEP + base64(remaining)]).
     * Use to verify runner + helper + main run without decrypt.
     */
    protected function buildWidgetRunnerPhpStage1(string $payloadB64, string $toolSlug, bool $emitStepMarkers = false): string
    {
        $payloadEscaped = addslashes($payloadB64);
        $sepB64 = base64_encode(self::DATA_SEPARATOR);
        $toolLabel = addslashes(ucfirst(str_replace('-', ' ', $toolSlug)));
        $s0 = $emitStepMarkers ? "echo '<!-- STEP 0: stage1_plain -->';" : '';
        $s3 = $emitStepMarkers ? "echo '<!-- STEP 3: helper_eval_ok -->';" : '';
        return <<<PHP
<?php
{$s0}
\$payload_b64 = '{$payloadEscaped}';
\$raw = base64_decode(\$payload_b64, true);
if (\$raw === false) { echo '<!-- {$toolLabel}: Stage 1 invalid payload -->'; return; }
\$sep = base64_decode('{$sepB64}');
\$parts = explode(\$sep, \$raw, 3);
\$main_b64 = isset(\$parts[1]) ? \$parts[1] : '';
\$remaining_b64 = isset(\$parts[2]) ? \$parts[2] : '';
\$helper = isset(\$parts[0]) ? \$parts[0] : '';
if (\$helper !== '') { \$helper = preg_replace('/^\\s*<\\?php\\s*/', '', \$helper); eval(\$helper); }
{$s3}
PHP;
    }

    /**
     * Build minimal helper PHP: defines base URL and file list; client runner will set $main_b64 (and optionally $remaining_b64) and eval this.
     */
    protected function buildHelperPhp(string $serverBaseUrl, array $serverFileNames, bool $emitStepMarkers = false, bool $hasRemainingPart = false): string
    {
        $baseEscaped = addslashes(rtrim($serverBaseUrl, '/'));
        $filesJson = json_encode($serverFileNames);
        $s4 = $emitStepMarkers ? "echo '<!-- STEP 4: main_eval -->';" : '';
        $outputRemaining = $hasRemainingPart
            ? "if (isset(\$remaining_b64) && \$remaining_b64 !== '') { echo base64_decode(\$remaining_b64, true) ?: ''; }"
            : '';
        return <<<PHP
<?php
\$__base = '{$baseEscaped}';
\$__files = {$filesJson};
if (isset(\$main_b64) && \$main_b64 !== '') {
    \$__code = base64_decode(\$main_b64, true) ?: '';
    \$__code = preg_replace('/^\\s*<\\?php\\s*/', '', \$__code);
    if (\$__code !== '') { {$s4} eval(\$__code); }
}
{$outputRemaining}
PHP;
    }
}

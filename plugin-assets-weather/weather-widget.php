<?php
/**
 * Weather Widget - Brilliant Directories
 * Frontend widget: displays current weather via Open-Meteo (free, no API key).
 * License-protected; decrypted by Tool Installer when valid license is used.
 */
// Output a unique container ID so multiple instances don't clash
$weather_widget_id = 'bd-weather-' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
?>
<div id="<?php echo htmlspecialchars($weather_widget_id); ?>" class="bd-weather-widget" data-weather-widget>
    <div class="bd-weather-inner">
        <div class="bd-weather-search">
            <input type="text" class="bd-weather-input" placeholder="City or place (e.g. London)" value="" aria-label="Search city">
            <button type="button" class="bd-weather-btn-search" aria-label="Get weather">Get weather</button>
        </div>
        <div class="bd-weather-loading" aria-hidden="true" style="display:none;">Loading weather…</div>
        <div class="bd-weather-error" role="alert" aria-live="polite" style="display:none;"></div>
        <div class="bd-weather-result" style="display:none;">
            <div class="bd-weather-location"></div>
            <div class="bd-weather-temp"></div>
            <div class="bd-weather-desc"></div>
            <div class="bd-weather-meta"></div>
        </div>
    </div>
</div>

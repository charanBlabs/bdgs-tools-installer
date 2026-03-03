# Weather Widget – Test with license and encryption

The **Weather Widget** is a small, license-protected tool that shows current weather on a Brilliant Directories site using the free [Open-Meteo](https://open-meteo.com/) API (no API key, CORS-enabled). Use it to test the full flow: **encryption → license → install**.

## What’s included

- **PHP**: `weather-widget.php` – markup for search + result container.
- **CSS**: `weather-widget.css` – styling for the widget.
- **JS**: `weather-widget.js` – geocoding + Open-Meteo fetch, no API key.

Assets live in **`plugin-assets-weather/`**. The tool is registered as a **service** (license required, encrypted payload).

## 1. Encrypt the weather tool

From the Tool-Installer project root:

```bash
php artisan tools:encrypt weather --assets=plugin-assets-weather
```

This reads all files in `plugin-assets-weather/`, encrypts them with `APP_KEY`, and stores the payload in the `encrypted_tools` table for slug `weather`.

## 2. Create a license (admin)

1. Open **Admin** → **Licenses** → **Add license**.
2. **Tool**: choose **weather**.
3. Optionally set **Valid from** / **Valid until** and **Allowed domain**.
4. Save. Copy the generated **license token** (you’ll use it in the installer).

## 3. Install on your BD site

**Option A – Public installer (browser)**

1. Go to the **Public Installer** (e.g. `http://your-tool-installer.test/`).
2. **Step 1**: Enter your BD Base URL and BD API Key → **Verify token**.
3. **Step 2**: Choose **Weather Widget (service)**, paste the **license token**, optional **Install domain** → **Install**.

**Option B – API (e.g. from BDGS)**

```bash
curl -X POST http://your-tool-installer.test/api/install \
  -H "Content-Type: application/json" \
  -d '{
    "bd_base_url": "https://yoursite.directoryup.com",
    "bd_api_key": "YOUR_BD_API_KEY",
    "tool_slug": "weather",
    "license_token": "YOUR_LICENSE_TOKEN"
  }'
```

## 4. Use the widget on BD

After install, the **Weather Widget** is a front-end widget on your BD site. Add it to a page (or use BD’s widget/shortcode placement). It will:

- Show weather for **London** on first load (or the first city you search).
- Let users type a city and click **Get weather** (geocoding + current conditions via Open-Meteo).

No API key is required for Open-Meteo; the widget runs in the browser and calls Open-Meteo directly (CORS is supported).

## License and encryption behavior

- **Encryption:** Tool assets are encrypted in the Tool Installer DB. When you install with a valid license, the installer decrypts the payload and sends it to the BD API. The **license only gates the install step** (who can push the code to BD).
- **After install:** The widget code is stored on the BD site in plain form. Once installed, the widget will keep running on BD even if the license later expires or is revoked. The license does not “phone home” at runtime.
- **To enforce expiry on the live site** you would need the widget to call a license-check endpoint (e.g. on your installer or BDGS) and hide or disable itself when the license is invalid. That is not implemented in this sample widget.

## Troubleshooting

- **“No tool assets available”** → Run `php artisan tools:encrypt weather --assets=plugin-assets-weather` and ensure `encrypted_tools` has a row for `weather`.
- **License invalid** → Create a license for tool **weather** and use that token; check Valid from/until and allowed domain if set.
- **Widget not showing on BD** → Confirm the widget is assigned to the viewport (e.g. front) and the page in BD’s widget/shortcode settings.

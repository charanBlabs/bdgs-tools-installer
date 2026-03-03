# Tool Installer

Multi-tool installer (Laravel) for deploying tools (e.g. FAQ Management Plugin) to Brilliant Directories via the BD API. This project is **separate** from the FAQ Management Plugin; they sit as sibling folders under the same parent.

## Layout

- **Parent folder** (e.g. `FAQ Tool`) contains:
  - **FAQ Management Plugin/** – FAQ plugin source (admin, frontend, css, docs, etc.).
  - **Tool-Installer/** – This Laravel app and its `plugin-assets/`.

- **Here (Tool-Installer):**
  - Laravel app (install UI, verify token, install widgets, admin license CRUD, encryption).
  - **plugin-assets/** – Built plugin files used for install. Populated by the build script or Artisan command.
  - **BD_API_REFERENCE.md** – BD API endpoints and `data_widgets` mapping.

## Build plugin assets

From this folder (Tool-Installer):

- **PowerShell:** `.\build-plugin-assets.ps1` (copies from `../FAQ Management Plugin` into `plugin-assets/`).
- **Artisan:** `php artisan tools:build-assets` (same; default source is `../FAQ Management Plugin`, output is `plugin-assets/`).

Optional: `--source=/path/to/FAQ-Management-Plugin` and `--output=/path/to/plugin-assets`.

## Run the installer

1. Copy `.env.example` to `.env`, set `APP_KEY` (`php artisan key:generate`), and optionally `APP_ADMIN_PASSWORD`, `BD_BASE_URL`, `BD_API_KEY`, `LICENSE_SECRET`, `TOOL_ENCRYPTION_KEY`.
2. Run migrations: `php artisan migrate`.
3. Serve: `php artisan serve` → open http://localhost:8000.
4. On the form: BD URL + API key → **Verify token** → choose tool (e.g. FAQ) → for service-based enter **license token** → **Install**.

## Service-based (FAQ) vs direct tools

- **Service-based (FAQ):** Requires a valid license. Run `php artisan tools:build-assets` then `php artisan tools:encrypt faq` to populate encrypted storage. Add licenses in **Admin** (`/admin/login`).
- **Direct tools:** No license; install uses plain files from `plugin-assets/`.

## Admin

- **URL:** `/admin/login` (password from `APP_ADMIN_PASSWORD` in `.env`).
- **Licenses:** List, add, revoke (subscription_id, valid_from/until, allowed_domain, tool_slug).

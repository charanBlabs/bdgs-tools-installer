# Brilliant Directories API Reference (Website API 2.0.0)

This document summarizes the BD API endpoints used by the Tool Installer for token verification and data_widgets management. Derived from the plan and Swagger documentation.

**Base:** Website API 2.0.0 (OAS3)  
**Spec URL (from doc):** `//ww2.managemydirectory.com/directory/dev_refactor_oop_3.0/views/admin/bdapi/swagger/dist/documentation.yaml`

---

## Authentication

- **GET** `/api/v2/token/verify` – Check if the API key is valid.
- **Usage:** Call before any install step. Pass API key via header.
- **Common patterns:** `Authorization: Bearer {api_key}` or `X-API-Key: {api_key}`. Confirm exact header name from the YAML or BD admin docs.

---

## Widgets (data_widgets)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/v2/data_widgets/get/{widget_id}` | Read one widget by ID |
| POST | `/api/v2/data_widgets/create` | Create a new widget |
| PUT | `/api/v2/data_widgets/update` | Update an existing widget |
| DELETE | `/api/v2/data_widgets/delete` | Delete a widget |
| POST | `/api/v2/data_widgets/render` | Render widget content |

There is no "list" or "search widgets" endpoint in the snippet; to update an existing install you may need to store `widget_id` after create or have the user provide it.

---

## data_widgets table shape

| Column | Type | Notes |
|--------|------|-------|
| widget_id | int(5) | PK, auto; omit on create |
| widget_name | varchar(255) | e.g. "FAQ Management Plugin", "FAQ Global Renderer" |
| widget_type | varchar(255) | Default 'Widget' |
| widget_data | longtext | HTML tab – PHP, HTML, inline JS/CSS allowed |
| widget_style | longtext | CSS |
| widget_class | varchar(255) | Often '' |
| widget_viewport | varchar(12) | e.g. 'front' |
| date_updated | varchar(255) | Use defaults if API allows |
| updated_by | varchar(255) | Often '' |
| short_code | varchar(255) | Often '' |
| div_id | varchar(255) | Often '' |
| bootstrap_enabled | int(1) | Default 1 |
| ssl_enabled | int(1) | Default 1 |
| mobile_enabled | int(1) | Default 1 |
| widget_html_element | varchar(10) | Default 'div' |
| widget_javascript | longtext | JS |
| file_type | varchar(255) | Often '' |
| widget_settings | longtext | Often '' |
| widget_values | longtext | Often '' |

---

## Plugin → widget mapping (FAQ Tool)

- **Admin:** One widget (e.g. "FAQ Management Plugin"): `widget_data` = admin PHP contents, `widget_style` = admin CSS, `widget_javascript` = inline/admin JS if extracted (or keep in widget_data).
- **Frontend:** One widget (e.g. "FAQ Global Renderer"): `widget_data` = frontend PHP. Optional separate widgets for FAQ Slug Renderer / other frontend files.

---

## Clarifications to confirm

- **Auth header:** Confirm with BD whether the key is sent as `Authorization: Bearer {key}`, `X-API-Key: {key}`, or query param.
- **Create response:** Does `POST /api/v2/data_widgets/create` return the created `widget_id`?
- **Widget list/search:** If BD has a "list widgets" or "search by name" endpoint, use it to resolve widget_id by name for re-installs.

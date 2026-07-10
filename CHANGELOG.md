# AnyAPI Changelog

All notable changes to this plugin will be documented in this file.

---

## [Unreleased]

### Architecture

### Added

### Changed

### Removed

### Fixed

---

## [2.0.3] (2026-07-10)

### Architecture

### Added

- (T-1) UTM tracking on Starter outbound links to anyapiplugin.com — central `anyapi_utm_url()` helper tags PlanHelper's shared upgrade URL (inherited site-wide by the Order API upgrade badge and Upgrade submenu link), the Settings page docs link, and the dashboard.js hardcoded fallback; readme.txt homepage/docs/pricing links tagged; new `settings` UTM medium added. WordPress.org listing links, `admin_url()` links, and remote server calls are excluded by design; plugin-list screen and changelog links deferred to a follow-up.

### Changed

- (U-7) Review banner stat now shows successful automations in the last 7 days instead of cumulative log count.
- (U-7) `shouldShowBanner()` reads the precomputed `should_show` condition from `anyapi_review_conditions` instead of recomputing on every check.
- (U-7) Review banner layout: added top spacing, aligned left/right edges with the header.

### Removed

### Fixed

- (U-7) Review banner now gates on recent success (last 7 days) and a healthy last-24h window, instead of cumulative log count; days-used counts from the first successful call, not plugin activation.
- (U-7) Review banner close button no longer overlaps banner action buttons.
- (U-9) Success Rate Today card shows a neutral state when there are no calls yet today, instead of a false "danger"/red "Check errors" state.

---

## [2.0.2] (2026-06-26)

### Added

- (F-7/P1) `inc/AnyapiDebug.php` — static debug logger class, guarded by `anyapi_debug_mode` option
- (F-7/P1) Settings page: Debug Mode toggle with save/load via `update_option()`
- (F-7/P2) L1–L4 debug log points in `OrderIntegrations::onStatusChange()` (Starter+)
- (F-7/P3) L5–L8 debug log points in `OrderIntegrations::fireIntegration()` (Lite+)
- (F-8) Order API: static payload override — empty payload field sends full WooCommerce order data; a populated payload field sends it as a static JSON string (no variable substitution).

### Changed

- Stripped internal task ID tags ([NEW], [F-7]) from inline comments per Comment Rules.
- Removed wp_options option name references from comments (Rule 1 compliance).

### Fixed

- Order API: page scrolls to top after save; JSON preview refreshes on mode switch.
- Order API: API Logs breadcrumb link corrected to use the right page slug.

### Architecture

- Debug log scope: Starter L1–L4 (trigger/gate/usage); Lite+ adds L5–L10 (payload/filter/HTTP)
- `AnyapiDebug::log( $context, $message, $data )` — token/password excluded; payload/response body truncated 300–500 chars
- F-8 Starter payload override (Design A): empty payload field triggers full WooCommerce order data send; populated payload is sent as static JSON without {{variable}} interpolation. Affects `OrderIntegrations::fireIntegration()` Basic mode path.

---

## [2.0.1] (2026-06-08)

### Added

- Debug Mode toggle in Settings — enable detailed logging for troubleshooting API integrations.
- Debug log points covering the full integration lifecycle: status change detection, trigger matching, authentication, payload building, JSON filtering, and HTTP response.

---

## [2.0.0] (2026-05-03)

### Architecture

- Scheme C separation: Starter PlanHelper = stub (no HMAC); Lite = full PlanHelper + LiteIntegrations filter hooks
- Filter hook system: `anyapi_allowed_triggers`, `anyapi_apply_json_filter`, `anyapi_json_filter_enabled`, `anyapi_render_log_ui`
- PlanHelper load order: Lite priority 5 → Starter priority 10 (class_exists guard)
- Dark mode applies `body.dark-mode` class via `dashboard.js` toggle; all pages share `--anyapi-*` design tokens defined in `dashboard.scss :root`
- `order-api.scss` inherits `--anyapi-*` tokens from `dashboard.scss` only — no local `:root` redeclaration (ensures dark mode propagates when `body.dark-mode` is set)
- Throttle alert bar uses violet/indigo to convey "still running via WP Cron", not orange which implies an error state
- `restapi.js` v2 and `settings.js` v3 had no PHP or JS logic changes; dark mode is CSS-only via `body.dark-mode`
- `apikey.scss` v2 complete token migration — all legacy colour tokens removed, fully `--anyapi-*`
- `settings.scss` v3 complete token migration to `--anyapi-*` system; added `is-throttling` meter state

### Breaking Changes

- `api_key` → `api_key_id` (stores `ak_xxx` reference, NOT raw token)
- Log table unified: `wp_anyapi_log_lite` → `wp_anyapi_log_anyapi`

### Added

- (T1) WP Cron throttle retry: `PlanHelper::canFire()` schedules `anyapi_throttled_fire` when monthly cap exceeded
- (T4c) `isProActivate()` detects both anyapi-lite and anyapi-plus (dual-plugin architecture)
- (T8a) `getRecentLogs()`: Starter static 10-row direct DB query, no anyapi-lite class dependency
- (T8c) `enqueueFiles()`: `anyapi-logs.min.js` enqueued by Lite+ only; Starter uses static PHP log render
- (T8d) `ajaxGetLogs()`: Starter gate returns `upgrade_required`
- (ApiKeyHandler v2) `updated_at` field on all API key records
- (ApiKeyHandler v2) `ajaxList()` — returns safe key list for Order wizard dropdown
- (OrderIntegrationHandler v3) `name`, `http_method`, `headers` fields on integration records
- (OrderIntegrationHandler v3) `migrateRecord()` — auto-upgrades v1 records (`api_key` → `api_key_id`) on read
- (OrderIntegrationHandler v3) `ajaxList()` returns `api_key_id` instead of `api_key`
- (PlanHelper v5) `canFire()` WP Cron throttle, `getJsData()`
- (PlanHelper v6) `allPlans()`, `usedApiKeys()`, `canAccessLogs()`, `canUseJsonFilter()`, `canAddApiKey()`

### Changed

- (T1) `handleOrderStatus()`: per-integration loop delegates monthly cap check to `PlanHelper::canFire()`
- (T1) Throttle loop: `return` → `continue` so remaining integrations are still evaluated independently
- (T1) Removed inline batch throttle block from `handleOrderStatus()`
- (Dashboard v3) `versionCard()` reworked to full-width inline bar (sidebar removed); shown only when new version detected
- (dashboard.js v4) `tableBody` guard moved after dark mode and banner init sections
- (order-api.js v3) Config object renamed: `anyapiIntegration` → `anyapiOrder`
- (order-api.js v3) Form field `api_key` renamed to `api_key_id`
- (order-api.js v3) `state.apiKeyId` now tracks `ak_xxx` reference

### Removed

- (Anyapi.php v2) `setSections()`, `setSettings()`, `loadAddOns()`, `keyInput()`, `restApiInput()`, `restApiRespond()`, `executeRequest()` — legacy methods with no callers
- (Anyapi.php v2) `Views\OrderApi`, `Views\RestApi`, `Views\ApiLog` use statements — classes do not exist in codebase
- (Anyapi.php v2) `$orderApi`, `$restApi`, `$apiLog` instance vars — Views no longer instantiated in Anyapi
- (Anyapi.php v2) Demo sub-pages: Settings UI DEMO, API Key UI DEMO, Order API UI Demo, REST API UI Demo, API Log UI Demo
- (Admin.php) `addAdminNotice()` / `adminMessage()` — decommissioned
- (Admin.php) `getConfig()` message array — only served `addAdminNotice()`, removed with it
- (Dashboard.php v3) `dashboardSideBar()` / `quickLinksCard()` / `documentCard()` / `upgradeCard()` — right column removed
- (Dashboard.php v3) `bottomCard()` / `featureCard()` / `setupKeyTab()` / `keySetting()` / `inputFields()` / `apiKeyTab()` / `orderAPIContent()` / `apiLogContent()` — deprecated methods removed

### Fixed

- (Admin.php) `anyapiReview` nonce moved to `anyapi-dashboard` handle (`anyapi-admin` was not enqueued)
- (Admin.php) `check_ajax_referer`: added required second argument `'nonce'`
- (Anyapi.php v2) `setSubPages()`: removed duplicate `menu_slug` collisions (`_restapi`, `_apilog` were registered twice)
- (Anyapi.php v2) `setSubPages()`: Settings page now uses `'_settings'` slug (was `'_settingspage'`)
- (dashboard.js v4) Dark mode broken on Starter — `tableBody` guard was before dark mode init; Starter has no `#anyapi-logs-body` in DOM
- (OrderIntegrations.php) `handleOrderStatus()`: defensive `WC_Order` instance check for WC versions that may not pass the order object

---

## File-level Version History

### inc/Admin.php

| Tag | Description                                                                               |
| --- | ----------------------------------------------------------------------------------------- |
| T1  | `throttledFire()` — WP Cron hook for throttle retry; `canFire()` queues when cap exceeded |
| T4c | `isProActivate()` — detects anyapi-lite AND anyapi-plus                                   |
| T8a | `getRecentLogs()` — Starter 10-row direct DB query (no anyapi-lite dependency)            |
| T8c | `enqueueFiles()` — `anyapi-logs.min.js` enqueued by Lite+ only                            |
| T8d | `ajaxGetLogs()` — Starter gate returns `upgrade_required`                                 |
| FIX | `anyapiReview` nonce moved to `anyapi-dashboard` handle                                   |
| FIX | `check_ajax_referer` second param `'nonce'` added                                         |
| DEL | `addAdminNotice()` / `adminMessage()` removed                                             |
| DEL | `getConfig()` message array removed                                                       |

### inc/PlanHelper.php (Starter Stub)

**v6** (2026-04-26): Added `allPlans()`, `usedApiKeys()` to fix fatal errors on Settings, API Keys, and Order API pages.

**v5**: Added `canFire()` WP Cron throttle, `getJsData()`.

Stub behavior:

- `currentPlan()` → always `'starter'`
- `currentLimits()` → hardcoded starter limits
- `usedCallsThisMonth()` → DB count from log table
- `setPlan()` / `clearPlan()` → no-op (Lite handles these)
- `canFire()` → monthly limit check only

Filter hooks (registered by Lite when active):

- `anyapi_allowed_triggers` — extends trigger whitelist
- `anyapi_apply_json_filter` — applies advanced/expert filter
- `anyapi_json_filter_enabled` — enables advanced/expert save
- `anyapi_render_log_ui` — renders full AJAX log UI

### inc/AnyapiDebug.php

**v1** (2026-06-05, F-7):

- Static debug logger: `AnyapiDebug::log( $context, $message, $data )`
- Guarded by `get_option( 'anyapi_debug_mode' )` — disabled by default
- L1–L4 log points in `OrderIntegrations::onStatusChange()` (Starter+)
- L5–L8 log points in `OrderIntegrations::fireIntegration()` (Lite+)
- Token/password excluded from log output; payload/response body truncated 300–500 chars

### inc/Anyapi.php

**v2** deletions:

| Method                               | Reason                                                                      |
| ------------------------------------ | --------------------------------------------------------------------------- |
| `setSections()`                      | All callbacks pointed to legacy View methods that no longer exist           |
| `setSettings()`                      | `register_setting()` no longer used; templates use direct PHP render + AJAX |
| `loadAddOns()`                       | Callback for deleted `setSettings()`                                        |
| `keyInput()`                         | Fully commented-out in original; no callers                                 |
| `restApiInput()`                     | Fully commented-out in original; no callers                                 |
| `restApiRespond()`                   | Superseded by `RestApiHandler::ajaxRequest()`                               |
| `executeRequest()`                   | Private helper for `restApiRespond()`; removed with it                      |
| Views `use` statements               | `Anyapi\Views\OrderApi` / `RestApi` / `ApiLog` do not exist in codebase     |
| `$orderApi` / `$restApi` / `$apiLog` | Views no longer instantiated in Anyapi                                      |
| Demo sub-pages                       | 5 demo pages removed from production menu                                   |

Fixes:

- `setSubPages()`: removed duplicate `menu_slug` collisions (`_restapi`, `_apilog` were registered twice)
- `setSubPages()`: Settings now uses `'_settings'` slug (was `'_settingspage'`)

### inc/Controller/ApiKeyHandler.php

**v2** additions vs v1:

- `updated_at` field on every record
- `ajaxList()` — returns safe key list for Order wizard dropdown
- `ajaxSave()` saves `updated_at` on update

Key data structure (`anyapi_wc_apikey`, keyed by `ak_xxx`):

| Field        | Type   | Notes                         |
| ------------ | ------ | ----------------------------- |
| `id`         | string | `'ak_' + uniqid()`            |
| `name`       | string |                               |
| `type`       | string | `'bearer'` \| `'basic'`       |
| `key`        | string | Bearer token; empty for basic |
| `username`   | string | Basic auth only               |
| `password`   | string | Basic auth only               |
| `status`     | string | `'active'` \| `'inactive'`    |
| `created_at` | string | MySQL datetime UTC            |
| `updated_at` | string | MySQL datetime UTC            |

### inc/Controller/OrderIntegrationHandler.php

**v3** breaking change: `api_key` → `api_key_id` (stores `ak_xxx` reference, NOT raw token).

New fields added in v3:

- `name` — integration display name
- `http_method` — `POST` | `PUT` | `PATCH` (default `POST`)
- `headers` — custom header array `[{key, value}]`

New methods in v3:

- `migrateRecord()` — auto-upgrades old records on read
- `ajaxList()` now returns `api_key_id` (not `api_key`)

Integration data structure (`anyapi_wc_orderapi`, keyed by int ID):

| Field               | Type   | Notes                                          |
| ------------------- | ------ | ---------------------------------------------- |
| `id`                | int    | auto-incremented                               |
| `name`              | string | display name                                   |
| `api_url`           | string | HTTPS endpoint                                 |
| `api_key_id`        | string | `ak_xxx` reference or empty                    |
| `payload`           | string | JSON template with `{{variable}}` placeholders |
| `trigger`           | string | watch_orders \| watch_new_orders \| etc.       |
| `http_method`       | string | `POST` \| `PUT` \| `PATCH`                     |
| `headers`           | array  | `[{key: string, value: string}]`               |
| `filter_mode`       | string | `'basic'` \| `'advanced'` \| `'expert'`        |
| `selected_fields`   | array  | field paths for advanced filter                |
| `field_order`       | array  | field display order for advanced filter        |
| `raw_json_override` | string | raw JSON template for expert mode              |
| `status`            | string | `'active'` \| `'inactive'`                     |
| `created_at`        | string | MySQL datetime UTC                             |
| `updated_at`        | string | MySQL datetime UTC                             |

Plan gates:

- Starter: 3 triggers only · 500 calls/month · no JSON filter
- Lite+: all triggers · unlimited · JSON filter OK

### inc/Controller/OrderIntegrations.php

**Changes (T1 task)**:

- `handleOrderStatus()` — removed inline batch throttle block (was lines 95–105)
- `handleOrderStatus()` — per-integration loop delegates to `PlanHelper::canFire()` which handles cap check and Cron retry scheduling
- Loop throttle changed from `return` to `continue` so remaining integrations are still evaluated independently

Supported triggers (9 total):

| Trigger                  | WC Status         | Starter |
| ------------------------ | ----------------- | ------- |
| `watch_orders`           | any status change | ✓       |
| `watch_new_orders`       | pending           | ✓       |
| `watch_pending_order`    | pending (alias)   | —       |
| `watch_processing_order` | processing        | ✓       |
| `watch_on_hold_order`    | on-hold           | —       |
| `watch_completed_order`  | completed         | —       |
| `watch_cancelled_order`  | cancelled         | —       |
| `watch_refunded_order`   | refunded          | —       |
| `watch_failed_order`     | failed            | —       |

### inc/Views/Dashboard.php

**v3** changes:

- (T8b) `logSection()`: Starter static 10 rows + stats blur + Upgrade CTA; Lite+ AJAX UI unchanged
- (FIX) `versionCard()`: full-width inline bar (sidebar removed); shown only when new version detected

Removals:

- `dashboardSideBar()` / `quickLinksCard()` / `documentCard()` / `upgradeCard()` — right column removed
- `bottomCard()` / `featureCard()` / `setupKeyTab()` / `keySetting()` / `inputFields()` / `apiKeyTab()` / `orderAPIContent()` / `apiLogContent()` — deprecated

Retained: `fields()` / `getAuth()` — still called by `settings-apikey.php`

### src/js/dashboard.js

**v4** fix: Dark mode broken on Starter — `if (!tableBody) return` was before dark mode init. Starter's static PHP render has no `#anyapi-logs-body` in DOM, so entire JS bailed out. Fixed by moving `tableBody` guard after dark mode + banner sections.

**v3.1** (T8 Starter Gate): Search/filter disabled for Starter, inline upgrade CTA shown. Backend `ajaxGetLogs()` also gates (double protection).

**v3** core features:

- Dark Mode Toggle — pill v2, `localStorage` persist, `body.dark-mode` class
- Banner Dismiss — close/later/review, AJAX `anyapi_dismiss_review`
- Last Updated — `#update-time` stamp refreshed after every `fetchLogs()`

### src/js/order-api.js

**v4**: Dark mode is CSS-only (`body.dark-mode`), no JS changes. Version bump to track `ui-orderapi.scss` v4.

**v3** changes vs v2:

- Config object: `anyapiIntegration` → `anyapiOrder`
- `api-key-select` value is now `ak_xxx` ID (not raw name string)
- FormData field: `api_key` → `api_key_id`
- Edit mode: `loadIntegration()` fills all form fields back
- Integration list: toggle, delete, edit wired up
- Wizard open/close with cancel guard
- `renderSummary()` shows key name (looked up from `anyapiOrder.api_keys`)
- `state.apiKeyId` tracks selected `ak_xxx` ID

### src/js/apikey.js

**v2**: Dark mode is CSS-only (`body.dark-mode`), no JS changes. Version bumped to track scss v2 update.

### src/js/api-logs.js

**v2.1**: Starter guard in place (`wrap.dataset.plan === 'starter'` returns early). Dark mode is CSS-only. Version bump to track `apilog-v2.php` T8 update.

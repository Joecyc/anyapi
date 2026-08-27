=== AnyAPI – WooCommerce Orders to Any REST API, Webhook & Email ===
Contributors: anyapi
Donate link:
Tags: woocommerce, order notification, email notification, google sheets, erp
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WooCommerce order details anywhere it needs to go — an API, a webhook, or email — automatically. No code, every call logged. Start from a template.

== Description ==

**AnyAPI** watches your WooCommerce orders. The moment one comes in, it sends the order details where they need to go — a REST API, a webhook, or an email address.

That replaces the copying by hand: new orders into your ERP or CRM, a ship notice to your fulfilment partner, a running record of the day's orders, or an alert to the team the moment an order is paid.

Every call is logged, so you can see it arrived — and a temporary failure on the other end is retried on its own. No code, nothing to maintain.

🔗 [Official Website](https://anyapiplugin.com?utm_source=starter&utm_medium=readme&utm_campaign=home) | 📖 [Documentation](https://anyapiplugin.com/documentation/?utm_source=starter&utm_medium=readme&utm_campaign=docs) | 💬 [Support](https://wordpress.org/support/plugin/anyapi/) | 💻 [Source Code](https://github.com/Joecyc/anyapi)

#### Why AnyAPI?

AnyAPI does one thing and does it precisely: it takes the order data WooCommerce already has, shapes it the way the receiving system expects, and delivers it the moment the order event fires — then shows you that it arrived.

* **Point it at a recipient and it runs** — set who should receive the order, and from then on every new order goes out on its own.
* **One order, wherever it needs to go** — the same details can be sent to a REST API, a webhook, or an email address, so changing or adding a destination later isn't a rebuild.
* **It leaves the moment the order does** — a new order, a payment, or a status you choose is enough to trigger it; you don't press anything.
* **You can see it arrived** — every attempt is recorded with the response it got, shown in your dashboard, and a momentary failure on the other end is retried on its own.

### Use Cases — What You Can Connect

#### Send every order into a Google Sheet

Keeping a spreadsheet of your orders usually means exporting a CSV every few days, or copying orders across by hand — and it falls out of date the moment the next order arrives. AnyAPI adds each order to a Google Sheet as it happens, so the sheet keeps itself current. Every order becomes a new row you can sort, filter, or hand to someone who doesn't have access to WooCommerce — a bookkeeper, a supplier, a partner.

It works with a webhook and a short Google Apps Script, both covered step by step in the guide.

→ [Send WooCommerce orders to Google Sheets](https://anyapiplugin.com/woocommerce-google-sheets/?utm_source=starter&utm_medium=readme&utm_campaign=usecases)

#### Tell your team the moment an order comes in

Nobody wants to sit watching the orders screen — but for a busy store, knowing an order landed the second it happens changes how fast you can act on it. AnyAPI posts each new order straight into a Slack channel, so the whole team sees it in the place they're already working. Order number, total, and the details that matter, dropped in as it happens — no one has to log in to WooCommerce to find out.

It works with a Slack incoming webhook, set up in a few minutes.

→ [Quick start: WooCommerce orders to Slack](https://anyapiplugin.com/docs/quick-start-slack-notification/?utm_source=starter&utm_medium=readme&utm_campaign=usecases)

#### Email the order details automatically

When an order reaches the status you choose, AnyAPI emails the full order details to whoever needs them — your fulfilment partner, your warehouse, your accountant, or your own inbox. Line items, quantities, totals, weight and dimensions are already laid out in the message, so the recipient can act on it without opening WooCommerce.

Choose the recipient, choose the order status, and it runs from the next order. No API, no setup research, and it works on the free version.

#### Get orders into the systems that run your business

The order doesn't stop being useful once it's in WooCommerce — your ERP still needs it for stock and invoicing, your CRM needs it against the customer's record, and your fulfilment provider needs it to ship. Normally that means someone re-entering the same order into each one. AnyAPI sends it to them directly, the moment the order is placed, so the systems you already run stay in step with your store without anyone keying orders in twice.

If a system exposes a REST API or accepts a webhook, AnyAPI can reach it — whether it's a large platform or an in-house tool built just for your business.

#### Works with almost anything that has an API

AnyAPI isn't tied to a fixed list of services. If a system has a REST API or accepts a webhook, your orders can reach it — accounting tools, CRMs, ERPs, messaging apps, shipping and courier services, or an internal system your team built in-house. You're not limited to a menu someone else decided on.

The same order can log itself to Google Sheets, post to Slack, or send a WhatsApp message through Woztell. It can also push order, inventory, and customer data into Oracle NetSuite, or adjust stock on a second WooCommerce store — from a simple spreadsheet log to a full ERP sync.

### AnyAPI Lite: order integrations for WooCommerce

AnyAPI Lite builds on the free version with the controls a store needs once orders are flowing to real systems.

**What it helps you tackle:**

* Sending only the fields the receiving system expects, not the whole order.
* Finding an order that failed days or weeks ago, instead of only seeing the latest calls.
* Knowing how many of your integrations are succeeding.
* Reaching more than one recipient, on more of your order events.

**Lite highlights:**

* **JSON Filter** — choose exactly which order fields to send, rename them, or set fixed values.
* **Full order log** — keep every call you've ever made, and search your history by order ID, URL, or payload to open any call and see the exact data that was sent.
* **More order triggers** — fire on more of WooCommerce's order events, not just the main three.
* **Multiple email recipients** — send the same order to several addresses.
* **Direct email support** — email help from Author.

== Frequently Asked Questions ==

= Is AnyAPI free to use? =

Yes. The free version on WordPress.org includes 1 API Key, the three main order triggers, unlimited API calls (throttled after 500/month), and a real-time API Log of your last 10 calls.

= Do I need coding skills to use AnyAPI? =

No. You configure everything through a visual interface inside your WordPress admin — endpoints, authentication, triggers, and payload filtering. No code at any point.

= Can AnyAPI email order details automatically? =

Yes. AnyAPI can email the full order details to any address the moment an order reaches the status you choose — useful for sending new orders to a fulfilment partner, a warehouse, or your own inbox. The recipient gets the order laid out and ready to act on, without logging into WooCommerce. This works on the free version.

= Does the free version include ready-made templates? =

Yes. The free version includes a ready-made Email template — pick who should receive the order, choose the trigger status, and it runs on the next matching order. There's nothing to design from scratch and no API to configure first.

= Can I connect WooCommerce to any REST API? =

Yes. If your external system has a REST API that accepts HTTP requests, AnyAPI can send WooCommerce order data to it. This includes ERPs, CRMs, messaging platforms, spreadsheets, fulfillment systems, and any custom API endpoint.

= Can AnyAPI send data to a webhook URL? =

Yes. AnyAPI can send WooCommerce order data to any webhook URL — including Slack Incoming Webhooks, Zapier Webhooks, Make Webhooks, and custom webhook endpoints. Configure the URL, authentication, and payload just like any other REST API integration.

= How does AnyAPI automate WooCommerce orders? =

AnyAPI sends WooCommerce order data to your REST API when an order event fires — a new order, payment completion, or a status change you choose. No manual action needed; it runs in the background.

= How is AnyAPI different from WooCommerce's built-in webhooks? =

WooCommerce's built-in webhooks send the full order payload with no filtering, no visual configuration, and no logs. AnyAPI adds a no-code visual UI, JSON Filter to shape payloads, real-time API Logs for debugging, and authentication helpers — making integrations far easier to set up, monitor, and maintain.

= What is the JSON Filter and why does it matter? =

The JSON Filter lets you choose exactly which WooCommerce order fields to send in your API request payload. Instead of sending the full order object (100+ fields), you select only what your external system needs — reducing errors, improving security, and making debugging easier.

= How do API Logs help me debug failed integrations? =

Every API call is recorded in AnyAPI's API Log with its HTTP status code, the API response, and latency, so you can see whether each call went through and what came back. The free version keeps your last 10 calls. AnyAPI Lite keeps your full history and adds the full request payload, search by order ID, URL, or payload, and filtering by status (2xx / 4xx / 5xx).

= What happens if the receiving API is temporarily down? =

If the receiving service returns a temporary error — such as 429, 502, 503, or 504 — AnyAPI retries the call once on its own, following the service's Retry-After header where one is provided. That way a brief outage on the other end doesn't quietly cost you the order. The retry runs in the background and never blocks the order from being saved.

= Does AnyAPI support WhatsApp Business API? =

Yes. If you have access to the WhatsApp Business API — for example through a provider like Woztell — AnyAPI can send a POST request to it whenever a WooCommerce order is placed or updated.

= Can I send WooCommerce orders to LINE Messaging API or Slack? =

Yes. AnyAPI works with any REST API that accepts HTTP requests — including LINE Messaging API, Slack Incoming Webhooks, Telegram Bot API, Discord Webhooks, and more.

= Can AnyAPI connect WooCommerce to an ERP system? =

Yes. If your ERP system (SAP, Oracle NetSuite, Microsoft Dynamics, or a custom ERP) has a REST API, AnyAPI can push WooCommerce order data to it automatically on any order event.

= What authentication methods does AnyAPI support? =

AnyAPI supports Basic Auth (username + password) and Bearer Token authentication. Compatible with any REST API that uses standard HTTP authentication methods.

= Is AnyAPI a Zapier or Make alternative for WooCommerce? =

For sending WooCommerce order data to external APIs, you can start on the free version. AnyAPI runs entirely inside your WordPress installation, with no per-task pricing, no external platform dependency, and your order data never leaves your server. It's built to do order-to-API automation well, rather than to be a general-purpose automation platform.

== Screenshots ==

1. **Integration Templates** — Send order details by email in minutes, straight from a ready-made template
2. **Google Sheets** — Log every WooCommerce order to a Google Sheet as it happens
3. **Slack** — Post each new order straight into a Slack channel
4. **Order API Automation** — Configure WooCommerce order triggers and connect to any REST API endpoint
5. **Dashboard** — Overview of API activity, integration status, and quick stats
6. **API Logs** — Search your full history, check success rates, and open any call to see the exact payload that was sent (AnyAPI Lite)
7. **JSON Filter** — Select exactly which WooCommerce order fields to include in your API payload (AnyAPI Lite)
8. **Dark mode** — The full AnyAPI admin in dark mode

== Changelog ==

= 2.0.5 (2026-08-22) =
* Added    - Integration Templates: a new Templates page lets you open a prefilled setup wizard in one click. The Email template is ready to use on every plan.
* Added    - Failed order integrations now retry automatically once after a temporary error (HTTP 429/502/503/504 or a connection failure), respecting Retry-After when the server sends it.
* Added    - Order Integrations list now has an expandable row showing each integration's method, auth, full endpoint and filter, plus a Key column, creation time, and a label for integrations created from a template.
* Added    - The API Key form now warns if you paste something that looks like a license key into the token field, and points you to the License page.
* Improved - Clearer upgrade messaging across Order Integrations, API Keys, Integration Templates, the JSON Filter and the API Log.
* Improved - New installs now see a "Set up your first integration" panel with a template shortcut when no integrations exist yet.
* Improved - The branded header and the Plugin Info card (now showing AnyAPI Lite's version and update status) appear across all admin pages.
* Changed  - Email destinations now use tokens ({{order_summary}}, {{order_id}}) in the message body. If you already have an email integration, add {{order_summary}} to its body to keep the order details — they are no longer added automatically.
* Fixed    - Dark mode no longer flashes the light theme on load, and Dashboard and Templates page titles are now readable.
* Fixed    - Expert-mode integrations no longer send an empty payload when an advanced filter fails to apply; those attempts are now logged as errors.
* Fixed    - Corrected several admin links (API Keys page slug, documentation redirect loop, dead changelog link) and the plugins-list links.

= 2.0.4 (2026-07-27) =
* Added    - Email destination for order integrations: send order notifications by email (recipient, subject, intro message) with an automatic order summary including per-item weight and dimensions. {{order_id}} is supported in the To and Subject fields.
* Added    - API Log now records and shows the response body for each call, so failed integrations are self-diagnosable.
* Fixed    - Order integrations now follow HTTP 3xx redirects correctly (301/302/303 convert to GET), fixing false 400 errors with endpoints like Google Apps Script.
* Fixed    - API Log now shows the real request method (GET/POST/Email) instead of always POST.

= 2.0.3 (2026-07-10) =
* Improved - Review prompt now appears only after recent successful automations, not during error troubleshooting
* Fixed    - Dashboard success rate no longer shows a red "0%" when there are simply no orders yet today
* Tweak    - Refined the review banner layout for a cleaner dashboard header

= 2.0.2 (2026-06-26) =
* Added    - Custom static payload override for Starter integrations: send a fixed JSON body instead of full order data.
* Improved - JSON preview now refreshes correctly when switching filter modes.
* Fixed    - Editor scrolls to top after saving an integration.
* Fixed    - API Logs page link corrected.

= 2.0.1 (2026-06-08) =
* Added: Debug Mode toggle in Settings — enable detailed logging for troubleshooting API integrations
* Added: Debug log points covering the full integration lifecycle: status change detection, trigger matching, authentication, payload building, JSON filtering, and HTTP response

= 2.0.0 (2026-05-04) =
* Redesign - Complete UI/UX overhaul across all admin pages
* Redesign - New dashboard with dark mode toggle and usage statistics
* Redesign - Order API wizard with step-by-step integration setup
* Redesign - API Key management with secure credential storage
* Redesign - API Logs with real-time status cards and latency tracking
* Redesign - Settings page with plan comparison and license management
* Added    - Multiple API Key support with Bearer Token and Basic Auth
* Added    - Custom HTTP headers per integration
* Added    - HTTP method selection (POST, PUT, PATCH) per integration
* Added    - Integration naming for easier management
* Added    - WP Cron throttle retry when monthly call limit is reached
* Added    - Dark mode across all admin pages
* Added    - Version update notification bar on dashboard
* Improved - API Key credentials now stored centrally and referenced by ID
* Improved - Starter plan includes real-time log of last 10 API calls
* Improved - SCSS design token system for consistent theming
* Fixed    - Plugin Check (PCP) compliance: escaping, sanitization, i18n
* Fixed    - Dark mode initialization on Starter plan
* Fixed    - Duplicate menu slug registrations removed

= 1.1.5 (2026-01-21) =
* Update  - Dashboard real-time API log section
* Added   - Search field for endpoint, payload, and order ID filtering
* Added   - HTTP status code filter (2xx / 4xx / 5xx)
* Tweak   - Improved loading experience for Real-Time API Log

= 1.1.4 (2025-12-08) =
* Added   - Dashboard page
* Added   - Real-time API log viewer
* Tweak   - API monitoring improvements

= 1.1.3 (2025-11-12) =
* Added   - JSON field filter and JSON preview in advanced mode
* Added   - API log notice
* Tweak   - Tooltips, feature descriptions, and Order API UI improvements

= 1.1.2 (2025-10-11) =
* Fix     - API Development Tools request timeout issue

= 1.1.1 (2025-08-21) =
* Tweak   - Feature content and UI improvements
* Added   - Links to documentation on feature cards
* Added   - Notice when Order API status is toggled OFF

= 1.1.0 (2025-07-07) =
* Added   - API Logs for all API integrations

= 1.0.0 (2025-05-27) =
* Initial release

== Installation ==

Requires WooCommerce 6.0 or higher.

**Setup**

1. Upload the `anyapi` folder to `/wp-content/plugins/`, or install directly from the WordPress plugin directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to **AnyAPI** in the WordPress admin sidebar.

**Fastest start — the Email template**

1. Open the Templates page and choose the Email template.
2. Enter who should receive the order and pick the trigger status.
3. Place a test WooCommerce order, then check the API Log to confirm it was sent.

**Sending to a REST API or webhook instead**

1. Add an API Key (the credentials for your external API).
2. Create an Order API integration — choose a trigger, set your endpoint URL, and configure authentication.
3. Place a test order and check the API Log to confirm your data arrived.

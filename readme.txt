=== AnyAPI – Order to API Automation, Webhooks & No-Code REST API Integration for WooCommerce ===
Contributors: anyapi
Donate link:
Tags: woocommerce, webhooks, api, automation, rest api
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WooCommerce orders to any REST API automatically — no code required. Built-in JSON Filter, real-time API Logs, and webhook automation in minutes.

== Description ==

**[AnyAPI](https://www.anyapiplugin.com)** is the easiest no-code way to send WooCommerce orders to any REST API or webhook endpoint — with built-in JSON Filter and real-time API Logs.

When an order happens in WooCommerce, AnyAPI sends it anywhere: your ERP, CRM, fulfillment system, accounting software, messaging service, or any custom REST API. No coding required, no expensive monthly SaaS fees.

🔗 [Official Website](https://anyapiplugin.com) | 📖 [Documentation](https://anyapiplugin.com/documentation/) | 💬 [Support](https://wordpress.org/support/plugin/anyapi/)

= Why AnyAPI? =

Most API integration tools are either too complex for store owners or too expensive with per-task pricing. AnyAPI is purpose-built for WooCommerce — it does one thing exceptionally well: **push your order data to any REST API automatically**.

- **Truly no-code** — built for WooCommerce store owners, not developers. Configure everything through a visual interface in your WordPress admin.
- **Built-in JSON Filter** — shape your API payload visually. Select only the WooCommerce order fields your external system needs — no PHP, no expensive add-ons.
- **Built-in API Logs** — see every request, response, HTTP status code, and latency right in your dashboard. Debug failed integrations in seconds.

= WooCommerce Order Automation =

Trigger outgoing API calls automatically on any WooCommerce order event — new order, payment complete, order status change (processing, completed, refunded, and more). AnyAPI pushes your WooCommerce order data instantly to any REST API endpoint the moment an event fires.

Configure multiple integrations, each with its own endpoint, authentication, and trigger rules. Enable or disable any integration with a single click.

= REST API & Webhook Integration =

AnyAPI connects your WooCommerce store to any external REST API that accepts standard HTTP requests. Send order data as JSON payloads via POST, PUT, or PATCH — with full control over headers, authentication, and request body.

Works with webhooks too. Point AnyAPI at any webhook URL — Slack Incoming Webhooks, Zapier Webhooks, Make (Integromat) Webhooks, or your own custom endpoint — and WooCommerce order data flows automatically.

= JSON Payload Filtering =

The JSON Filter lets you choose exactly which WooCommerce order fields to include in your API request payload. Instead of sending the full order object (100+ fields), select only what your external system expects — customer name, email, order total, product SKU, shipping address, line items, and more.

Clean payloads mean fewer integration errors, faster debugging, and less data exposure. Most competing plugins either lack this feature entirely or gate it behind expensive paid add-ons.

= API Logs & Debugging =

Every outgoing API call is logged automatically with full detail: HTTP status code (2xx, 4xx, 5xx), request payload, API response, latency, and timestamp. Search logs by order ID, endpoint URL, or filter by HTTP status to troubleshoot failed integrations fast.

No more guessing whether your WooCommerce order data actually reached your external system. AnyAPI's API Logs give you complete visibility — included free, not locked behind a paywall.

= ERP, CRM & Fulfillment Connectivity =

Connect WooCommerce orders to enterprise systems without custom development. If your ERP, CRM, or fulfillment platform has a REST API, AnyAPI can push order data to it automatically.

Common use cases include syncing WooCommerce orders to SAP, Oracle, NetSuite, Microsoft Dynamics, QuickBooks, Xero, ShipStation, Salesforce, HubSpot, Pipedrive, and custom in-house systems.

= No-Code for Store Owners =

AnyAPI is designed so that WooCommerce store owners — not just developers — can set up API integrations in minutes. The visual interface guides you through endpoint configuration, authentication setup, trigger selection, and payload filtering. No PHP, no JavaScript, no terminal commands.

= WooCommerce REST API Development Tools =

AnyAPI includes a built-in REST API tester for WooCommerce. Perform GET, POST, PUT, PATCH, and DELETE operations on WooCommerce Orders, Products, and Customers directly inside your WordPress admin dashboard. Useful for developers building or debugging WooCommerce integrations.

= Works With Any REST API =

AnyAPI connects WooCommerce to any REST API including:

- **Messaging & Notifications:** WhatsApp Business API, LINE Messaging API, Slack, Telegram, Discord, Microsoft Teams, Twilio
- **CRM & Marketing:** HubSpot, Salesforce, Pipedrive, ActiveCampaign, Mailchimp, Klaviyo, Brevo, Kit (ConvertKit)
- **ERP & Accounting:** SAP, Oracle NetSuite, Microsoft Dynamics, QuickBooks, Xero, Zoho Books
- **Fulfillment & Shipping:** ShipStation, ShipBob, Shippo, custom 3PL systems
- **Spreadsheets & Data:** Google Sheets (via Apps Script), Airtable, Notion
- **Automation Platforms:** Zapier (via Webhooks), Make / Integromat, Pabbly Connect, n8n
- **Custom Systems:** Any internal or third-party platform with a REST API endpoint

= Who Is AnyAPI For? =

- **WooCommerce store owners** who need order notifications sent to WhatsApp, LINE, Slack, or any messaging API — without hiring a developer
- **Freelancers & agencies** managing multiple WooCommerce stores that need API integrations set up quickly and replicated across clients
- **IT teams** connecting WooCommerce to ERP or fulfillment systems like SAP, NetSuite, or ShipStation — without writing custom PHP
- **Anyone replacing Zapier or Make** for WooCommerce order workflows — AnyAPI runs inside WordPress with a simple annual fee, no per-task pricing

= Free vs. Pro =

AnyAPI's free version on WordPress.org is fully functional — not a crippled demo. You get 1 API integration, 3 order triggers, unlimited API calls (throttled after 500/month), and real-time API Logs for your last 10 calls.

**[AnyAPI Pro](https://anyapiplugin.com/pricing/)** unlocks more API keys, all order triggers, JSON Filter, full API Log search and statistics, integration templates, and priority support. Plans start at $79/year — a fraction of what you'd pay for monthly SaaS automation tools.

== Installation ==

1. Upload the `anyapi` folder to `/wp-content/plugins/`, or install
   directly from the WordPress plugin directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to **AnyAPI** in the WordPress admin sidebar.
4. Add your first API Key (the credentials for your external API).
5. Create an Order API integration — choose a trigger, set your
   endpoint URL, and configure authentication.
6. Place a test WooCommerce order. Check the API Logs to confirm
   your data was sent successfully.

That's it. Your WooCommerce orders are now connected to your
external REST API — no code required.

== Frequently Asked Questions ==

= Is AnyAPI free to use? =

Yes. The free version on WordPress.org includes 1 API integration, 3 order triggers, unlimited API calls (throttled after 500/month), and real-time API Logs for your last 10 calls. No credit card, no trial period.

= Do I need coding skills to use AnyAPI? =

No. AnyAPI is designed to be fully no-code. You configure everything through a visual interface inside your WordPress admin — endpoints, authentication, triggers, and payload filtering. No PHP, no JavaScript, no command line.

= Can I connect WooCommerce to any REST API? =

Yes. If your external system has a REST API that accepts HTTP requests, AnyAPI can send WooCommerce order data to it. This includes ERPs, CRMs, messaging platforms, spreadsheets, fulfillment systems, and any custom API endpoint.

= Does AnyAPI support webhooks? =

Yes. AnyAPI can send WooCommerce order data to any webhook URL — including Slack Incoming Webhooks, Zapier Webhooks, Make Webhooks, and custom webhook endpoints. Configure the URL, authentication, and payload just like any other REST API integration.

= How does AnyAPI automate WooCommerce orders? =

AnyAPI automatically sends WooCommerce order data to any REST API when an order event is triggered — such as a new order, payment completion, or status change. No manual action needed; integrations fire automatically in the background.

= What is the JSON Filter and why does it matter? =

The JSON Filter lets you choose exactly which WooCommerce order fields to send in your API request payload. Instead of sending the full order object (100+ fields), you select only what your external system needs — reducing errors, improving security, and making debugging easier.

= How do API Logs help me debug failed integrations? =

Every API call is recorded in AnyAPI's API Logs with the HTTP status code, full request payload, API response, and latency. You can filter by status (2xx / 4xx / 5xx) and search by order ID or endpoint URL to identify exactly where and why an integration failed.

= Does AnyAPI support WhatsApp Business API? =

Yes. Configure AnyAPI to send a POST request to the WhatsApp Business API (via Meta's Cloud API or any gateway like Twilio or 360dialog) whenever a WooCommerce order is placed or updated.

= Can I send WooCommerce orders to LINE Messaging API or Slack? =

Yes. AnyAPI works with any REST API that accepts HTTP requests — including LINE Messaging API, Slack Incoming Webhooks, Telegram Bot API, Discord Webhooks, and more.

= Can AnyAPI connect WooCommerce to an ERP system? =

Yes. If your ERP system (SAP, Oracle NetSuite, Microsoft Dynamics, or a custom ERP) has a REST API, AnyAPI can push WooCommerce order data to it automatically on any order event.

= What authentication methods does AnyAPI support? =

AnyAPI supports Basic Auth (username + password) and Bearer Token authentication. Compatible with any REST API that uses standard HTTP authentication methods.

= How is AnyAPI different from WooCommerce's built-in webhooks? =

WooCommerce's built-in webhooks send the full order payload with no filtering, no visual configuration, and no logs. AnyAPI adds a no-code visual UI, JSON Filter to shape payloads, real-time API Logs for debugging, and authentication helpers — making integrations far easier to set up, monitor, and maintain.

= Is AnyAPI a Zapier or Make alternative for WooCommerce? =

For WooCommerce order-to-API workflows, yes. AnyAPI runs entirely inside your WordPress installation with a simple annual fee — no per-task pricing, no external platform dependency, and your data stays on your server. For complex multi-step automations across many different plugins, tools like Zapier or Uncanny Automator may be more appropriate.

= How is AnyAPI different from WPGetAPI? =

WPGetAPI is a general-purpose WordPress-to-API connector. AnyAPI is purpose-built for WooCommerce order automation — with dedicated order triggers, a visual JSON Filter for order payloads, and built-in API Logs. If your primary need is pushing WooCommerce order data to external APIs, AnyAPI is designed specifically for that workflow.

= What are the server requirements? =

AnyAPI requires WordPress 6.2, WooCommerce 6.0+, and PHP 7.4+.

== Screenshots ==

1. **Order API Automation** — Configure WooCommerce order triggers and
   connect to any REST API endpoint
2. **Dashboard** — Overview of API activity, integration status, and
   quick stats
3. **Real-Time API Logs** — Monitor HTTP status codes, request payloads,
   API responses, and latency for every outgoing call
4. **JSON Filter** — Select exactly which WooCommerce order fields to
   include in your API payload
5. **API Status Toggle** — Enable or disable any integration with a
   single click

== Changelog ==

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
# AnyAPI — Order-to-API Automation for WooCommerce

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/anyapi)](https://wordpress.org/plugins/anyapi/)
[![Downloads](https://img.shields.io/wordpress/plugin/dt/anyapi)](https://wordpress.org/plugins/anyapi/)
[![Tested up to](https://img.shields.io/wordpress/plugin/tested/anyapi)](https://wordpress.org/plugins/anyapi/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Send WooCommerce orders to any REST API or webhook automatically — no code required. Built-in JSON payload filtering and real-time API logs.

🔗 [Website](https://anyapiplugin.com) · 📖 [Documentation](https://anyapiplugin.com/documentation/) · 🧩 [WordPress.org](https://wordpress.org/plugins/anyapi/) · 💬 [Support](https://wordpress.org/support/plugin/anyapi/)

> This repository mirrors the **free version** of AnyAPI, published on the WordPress.org plugin directory under GPLv2 or later.

## Overview

When an order event fires in WooCommerce — a new order, a payment completion, or a status change — AnyAPI sends the order data to any external REST API endpoint you configure: ERP, CRM, fulfillment, accounting, or a messaging service. Everything is set up through the WordPress admin UI; no PHP and no external SaaS required.

## Features

- **Order-event triggers** — new order, payment complete, status changes (processing, completed, refunded, …)
- **REST API & webhook delivery** — POST / PUT / PATCH, with custom HTTP headers per integration
- **Authentication** — Basic Auth (username + password) and Bearer Token
- **JSON Filter** — pick exactly which order fields go into the payload instead of the full 100+-field order object
- **Real-time API Logs** — HTTP status code, request payload, response, and latency for every call; searchable by order ID, endpoint, or status
- **Built-in WooCommerce REST API tester** — GET / POST / PUT / PATCH / DELETE on Orders, Products, and Customers
- **Multiple stored credentials** — referenced centrally by ID

## How it works

A small pipeline runs on every matching order event:

1. **Event detection** — hooks into WooCommerce order status transitions
2. **Trigger matching** — checks the event against each enabled integration's rules
3. **Payload building** — assembles the order data
4. **JSON filtering** — reduces it to the fields you selected
5. **Authenticated request** — sends it via the configured HTTP method and auth
6. **Logging** — records status code, payload, response, and latency

(This is the same lifecycle exposed by the plugin's Debug Mode logging.)

## Supported targets

Any REST API that accepts HTTP requests, including:

- **Messaging:** WhatsApp Business, LINE, Slack, Telegram, Discord, Microsoft Teams, Twilio
- **CRM / Marketing:** HubSpot, Salesforce, Pipedrive, Mailchimp, Klaviyo, Brevo
- **ERP / Accounting:** SAP, Oracle NetSuite, Microsoft Dynamics, QuickBooks, Xero
- **Fulfillment:** ShipStation, ShipBob, Shippo
- **Automation:** Zapier, Make, n8n (via webhooks)

## Installation

**From WordPress.org (recommended)**

1. In wp-admin, go to _Plugins → Add New_ and search for "AnyAPI"
2. Install and activate

**Manual**

1. Upload the `anyapi` folder to `/wp-content/plugins/`
2. Activate it from the _Plugins_ menu

## Quick start

1. Open **AnyAPI** in the WordPress admin sidebar
2. Add an API credential (Basic Auth or Bearer Token)
3. Create an Order API integration — choose a trigger, set the endpoint URL and HTTP method
4. _(Optional)_ Use the JSON Filter to select which order fields to send
5. Place a test WooCommerce order and check the **API Logs** to confirm delivery

## Free vs Pro

The free version is fully functional — not a crippled demo:

- 1 API integration, 3 order triggers, unlimited API calls (throttled after 500/month), real-time logs for the last 10 calls

[**AnyAPI Pro**](https://anyapiplugin.com/pricing/) (from $79/year) adds more credentials, all order triggers, the JSON Filter, full API Log search and statistics, integration templates, and priority support.

## Requirements

- WordPress 6.2+
- WooCommerce 6.0+
- PHP 7.4+

## Screenshots

![desc](assets/anyapi-dashboard.png)
![desc](assets/order-api.png)
![desc](assets/json-filter.png)

## Changelog

See the [full changelog on WordPress.org](https://wordpress.org/plugins/anyapi/#developers).

## Contributing

Issues and pull requests are welcome. AnyAPI is released under the GPL.

## License

GPLv2 or later. See [LICENSE](LICENSE).

## Author

Built and maintained by **Joey Cheung** — [LinkedIn](https://www.linkedin.com/in/joey-cheung-1784665a/) · [Website](https://anyapiplugin.com)

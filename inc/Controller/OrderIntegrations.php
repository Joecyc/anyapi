<?php
/**
 * OrderIntegrations.php
 *
 * WooCommerce order event listener and API fire engine.
 *
 * @package AnyApi
 */


namespace Anyapi\Controller;

if ( ! defined( 'ABSPATH' ) ) exit;

class OrderIntegrations {

  // Option keys
  const INTEGRATION_KEY = 'anyapi_wc_orderapi';
  const APIKEY_KEY      = 'anyapi_wc_apikey';

  // WC status → trigger slug mapping
  const STATUS_TRIGGER_MAP = array(
    'pending'    => array( 'watch_orders', 'watch_new_orders', 'watch_pending_order' ),
    'processing' => array( 'watch_orders', 'watch_processing_order' ),
    'on-hold'    => array( 'watch_orders', 'watch_on_hold_order' ),
    'completed'  => array( 'watch_orders', 'watch_completed_order' ),
    'cancelled'  => array( 'watch_orders', 'watch_cancelled_order' ),
    'refunded'   => array( 'watch_orders', 'watch_refunded_order' ),
    'failed'     => array( 'watch_orders', 'watch_failed_order' ),
  );

  // =========================================================================
  // Init — register WC hooks
  // =========================================================================

  public function init(): void {

    // Hook all WC status transitions
    foreach ( array_keys( self::STATUS_TRIGGER_MAP ) as $status ) {
      add_action(
        'woocommerce_order_status_' . $status,
        array( $this, 'handleOrderStatus' ),
        10,
        2
      );
    }

    // Listen for throttled-fire events dispatched by Admin::throttledFire()
    // via WP Cron. Bypasses canFire() — the retry already passed plan-gate
    // scheduling; we just execute the deferred HTTP call directly.
    add_action( 'anyapi_fire_integration', array( $this, 'handleThrottledFire' ), 10, 2 );

  }

  // =========================================================================
  // handleOrderStatus
  // Called by WC on every order status change.
  // =========================================================================

  public function handleOrderStatus( int $order_id, $order = null ): void {

    // Defensive: some WC versions or hooks may not pass the order object.
    // Always ensure we have a valid WC_Order instance.
    if ( ! $order instanceof \WC_Order ) {
      $order = wc_get_order( $order_id );
    }
    if ( ! $order instanceof \WC_Order ) {
      return;
    }

    $status = $order->get_status(); // e.g. 'processing' (no 'wc-' prefix)

    if ( ! isset( self::STATUS_TRIGGER_MAP[ $status ] ) ) {
      return;
    }

    $fired_triggers = self::STATUS_TRIGGER_MAP[ $status ];
    $integrations   = get_option( self::INTEGRATION_KEY, array() );

    // [F-7] Debug log — L1: hook fired
    \Anyapi\AnyapiDebug::log( 'trigger', 'Status change detected', array(
      'order_id'   => $order_id,
      'new_status' => $status,
    ) );

    if ( empty( $integrations ) ) {
      return;
    }

    // ── Loop integrations & fire matching ones ─────────────────────────────
    foreach ( $integrations as $integration ) {

      // [F-7] Debug log — L2: integration loop start
      \Anyapi\AnyapiDebug::log( 'trigger', 'Checking integration', array(
        'integration_id'   => $integration['id'] ?? '',
        'integration_name' => $integration['name'] ?? '',
        'trigger'          => $integration['trigger'] ?? '',
        'status'           => $integration['status'] ?? '',
      ) );

      // Skip inactive integrations
      if ( ( $integration['status'] ?? 'active' ) !== 'active' ) {
        // [F-7] Debug log — L3: skip reason
        \Anyapi\AnyapiDebug::log( 'trigger', 'Skipped: status inactive', array(
          'integration_id' => $integration['id'] ?? '',
        ) );
        continue;
      }

      $trigger        = $integration['trigger'] ?? '';
      $integration_id = (string) ( $integration['id'] ?? '' );

      // Check trigger matches this status event
      if ( ! in_array( $trigger, $fired_triggers, true ) ) {
        // [F-7] Debug log — L3: skip reason
        \Anyapi\AnyapiDebug::log( 'trigger', 'Skipped: trigger mismatch (expected one of: ' . implode( ', ', $fired_triggers ) . ', got: ' . $trigger . ')', array(
          'integration_id' => $integration['id'] ?? '',
        ) );
        continue;
      }

      // Lite+ extends starter triggers via anyapi_allowed_triggers filter; no filter = starter only.
      $starter_triggers = array(
        'watch_orders',
        'watch_new_orders',
        'watch_processing_order',
      );
      $allowed_triggers = apply_filters( 'anyapi_allowed_triggers', $starter_triggers );

      // null = all allowed (backward compat); array = whitelist
      if ( is_array( $allowed_triggers ) && empty( $allowed_triggers ) ) {
        $allowed_triggers = $starter_triggers; // Safety fallback
      }

      // Check trigger against allowed list (null = all triggers allowed)
      if ( $allowed_triggers !== null && ! in_array( $trigger, (array) $allowed_triggers, true ) ) {
        // [F-7] Debug log — L3: skip reason
        \Anyapi\AnyapiDebug::log( 'trigger', 'Skipped: trigger not in Starter whitelist', array(
          'integration_id' => $integration['id'] ?? '',
        ) );
        continue;
      }

      // Deferred: PlanHelper::canFire() checks cap and schedules Cron retry if exceeded.
      if ( ! \Anyapi\PlanHelper::canFire( $trigger, $order_id, $integration_id ) ) {
        // [F-7] Debug log — L3: skip reason
        \Anyapi\AnyapiDebug::log( 'trigger', 'Skipped: monthly limit reached', array(
          'integration_id' => $integration['id'] ?? '',
        ) );
        continue;
      }

      // [F-7] Debug log — L4: fire decision
      \Anyapi\AnyapiDebug::log( 'trigger', 'Firing integration', array(
        'integration_id' => $integration['id'] ?? '',
        'order_id'       => $order_id,
      ) );

      $this->fireIntegration( $order, $integration );
    }

  }

  // =========================================================================
  // handleThrottledFire — WP Cron retry entry point (anyapi_fire_integration)
  // =========================================================================

  /**
   * Called by WP Cron after a 30-second delay when the monthly cap was exceeded.
   * Resolves the order and delegates directly to fireIntegration(), skipping
   * canFire() so the deferred call is not blocked a second time.
   *
   * @param int    $order_id    WooCommerce order ID
   * @param array  $integration Integration record array (full record, not just ID)
   */
  public function handleThrottledFire( int $order_id, array $integration ): void {
    $order = wc_get_order( $order_id );
    if ( ! $order instanceof \WC_Order ) {
      return;
    }
    $this->fireIntegration( $order, $integration );
  }

  // =========================================================================
  // fireIntegration — build payload and send HTTP request
  // =========================================================================

  private function fireIntegration( \WC_Order $order, array $integration ): void {

    $order_id = $order->get_id();

    // ── Resolve API key credentials ───────────────────────────────────────
    // Read api_key_id (ak_xxx reference), not old api_key raw string.
    // migrateRecord() in Handler ensures api_key_id exists on all records.
    $api_key_ref = $integration['api_key_id'] ?? $integration['api_key'] ?? '';
    $auth_header = $this->resolveAuth( $api_key_ref );

    // [F-7] Debug log
    \Anyapi\AnyapiDebug::log( 'fire', 'Auth resolved', array(
      'integration_id' => $integration['id'] ?? '',
      'api_key_ref'    => $api_key_ref,
      'auth_type'      => ! empty( $auth_header ) ? 'set' : 'none',
    ) );

    // ── Build payload ─────────────────────────────────────────────────────
    $filter_mode = $integration['filter_mode'] ?? 'basic';
    $raw_payload = trim( (string) ( $integration['payload'] ?? '' ) );
    $order_data  = $order->get_data();

    if ( $filter_mode === 'basic' ) {
      // Basic mode is a static passthrough: no {{variable}} interpolation.
      // Empty or {} sends the full WC order data; a non-empty static JSON is
      // sent exactly as written and overrides the order data.
      $decoded = json_decode( $raw_payload, true );
      if ( '' === $raw_payload || empty( $decoded ) ) {
        $payload_json = wp_json_encode( $order_data );
      } else {
        $payload_json = $raw_payload;
      }
    } else {
      // advanced / expert keep {{variable}} interpolation support.
      $payload_json = $this->interpolatePayload(
        '' !== $raw_payload ? $raw_payload : '{}',
        $order
      );
    }

    // [F-7] Debug log
    \Anyapi\AnyapiDebug::log( 'fire', 'Payload built (pre-filter)', array(
      'integration_id'  => $integration['id'] ?? '',
      'filter_mode'     => $filter_mode,
      'payload_length'  => strlen( $payload_json ),
      'payload_preview' => mb_substr( $payload_json, 0, 300 ),
    ) );

    // ── Apply JSON filter (filter_mode) ───────────────────────────────────
    $filtered_payload = apply_filters(
      'anyapi_apply_json_filter',
      $payload_json,
      $filter_mode,
      $integration['selected_fields']   ?? array(),
      $integration['field_order']       ?? array(),
      $integration['raw_json_override'] ?? '',
      $order_data
    );

    // [F-7] Debug log
    \Anyapi\AnyapiDebug::log( 'fire', 'Payload after filter', array(
      'integration_id'  => $integration['id'] ?? '',
      'filter_mode'     => $filter_mode,
      'payload_length'  => strlen( $filtered_payload ),
      'payload_preview' => mb_substr( $filtered_payload, 0, 300 ),
    ) );

    // Re-interpolate filtered payload — expert mode returns
    // raw_json_override which may contain {{variable}} placeholders.
    if ( $filter_mode === 'expert' && strpos( $filtered_payload, '{{' ) !== false ) {
      $filtered_payload = $this->interpolatePayload( $filtered_payload, $order );
    }

    // ── HTTP request ──────────────────────────────────────────────────────
    $api_url     = $integration['api_url']     ?? '';
    $http_method = $integration['http_method'] ?? 'POST';   // [NEW] default POST
    $custom_hdrs = $integration['headers']     ?? array();  // [NEW] [{key,value},...]

    if ( empty( $api_url ) ) {
      return;
    }

    // Build custom header map from stored array
    $extra_headers = array();
    foreach ( $custom_hdrs as $h ) {
      if ( ! empty( $h['key'] ) ) {
        $extra_headers[ $h['key'] ] = $h['value'] ?? '';
      }
    }

    $start_time = microtime( true );

    // Validate method — only POST / PUT / PATCH allowed
    $allowed_methods = array( 'POST', 'PUT', 'PATCH' );
    if ( ! in_array( strtoupper( $http_method ), $allowed_methods, true ) ) {
      $http_method = 'POST';
    }

    $args = array(
      'method'  => strtoupper( $http_method ),
      'headers' => array_merge(
        array( 'Content-Type' => 'application/json' ),
        $auth_header,
        $extra_headers    // custom headers come last — can override auth if misconfigured
      ),
      'body'    => $filtered_payload,
      'timeout' => 15,
    );

    $response = wp_remote_request( $api_url, $args );  // wp_remote_request supports PUT/PATCH

    $latency_ms = (int) round( ( microtime( true ) - $start_time ) * 1000 );

    // [F-7] Debug log
    if ( is_wp_error( $response ) ) {
      \Anyapi\AnyapiDebug::log( 'fire', 'HTTP error (WP_Error)', array(
        'integration_id' => $integration['id'] ?? '',
        'url'            => $api_url,
        'error_message'  => $response->get_error_message(),
      ) );
    } else {
      $resp_code = wp_remote_retrieve_response_code( $response );
      $resp_body = wp_remote_retrieve_body( $response );
      \Anyapi\AnyapiDebug::log( 'fire', 'HTTP response', array(
        'integration_id' => $integration['id'] ?? '',
        'url'            => $api_url,
        'method'         => $http_method,
        'response_code'  => $resp_code,
        'response_body'  => mb_substr( $resp_body, 0, 500 ),
      ) );
    }

    // ── Log result ────────────────────────────────────────────────────────
    if ( is_wp_error( $response ) ) {
      $this->writeLog( array(
        'order_id'  => $order_id,
        'http_code' => 0,
        'status'    => 'error',
        'trigger'   => $integration['trigger'],
        'method'    => $http_method,
        'api_url'   => $api_url,
        'payload'   => $filtered_payload,
        'latency'   => null,
      ) );
      return;
    }

    $http_code = wp_remote_retrieve_response_code( $response );
    $status    = ( $http_code >= 200 && $http_code < 300 ) ? 'success' : 'error';

    $this->writeLog( array(
      'order_id'  => $order_id,
      'http_code' => $http_code,
      'status'    => $status,
      'trigger'   => $integration['trigger'],
      'method'    => $http_method,
      'api_url'   => $api_url,
      'payload'   => $filtered_payload,
      'latency'   => $latency_ms,
    ) );

  }

  // =========================================================================
  // Resolve auth header from api_key reference or raw token
  // =========================================================================

  /**
   * $api_key_ref can be:
   *   - an API Key ID ('ak_xxx')  → look up stored key
   *   - a raw Bearer token string → send as-is
   */
  private function resolveAuth( string $api_key_ref ): array {

    if ( empty( $api_key_ref ) ) {
      return array();
    }

    $stored_keys = get_option( self::APIKEY_KEY, array() );

    // Look up by ID
    if ( str_starts_with( $api_key_ref, 'ak_' ) && isset( $stored_keys[ $api_key_ref ] ) ) {
      $key = $stored_keys[ $api_key_ref ];

      if ( ( $key['status'] ?? 'active' ) !== 'active' ) {
        return array();  // Key deactivated
      }

      if ( $key['type'] === 'basic' ) {
        $credentials = base64_encode( $key['username'] . ':' . $key['password'] );
        return array( 'Authorization' => 'Basic ' . $credentials );
      }

      if ( $key['type'] === 'bearer' ) {
        return array( 'Authorization' => 'Bearer ' . $key['key'] );
      }
    }

    // Fallback: treat as raw Bearer token
    return array( 'Authorization' => 'Bearer ' . $api_key_ref );
  }

  // =========================================================================
  // Interpolate {{variable}} placeholders in payload template
  // =========================================================================

  private function interpolatePayload( string $template, \WC_Order $order ): string {

    if ( empty( $template ) ) {
      return '';
    }

    $order_data = $order->get_data();
    $replacements = $this->buildReplacements( $order, $order_data );

    $result = $template;
    foreach ( $replacements as $placeholder => $value ) {
      $result = str_replace( '{{' . $placeholder . '}}', $value, $result );
    }

    return $result;
  }

  /**
   * Build flat replacement map from a WC_Order.
   * Keys match {{variable}} placeholders users put in payload templates.
   */
  private function buildReplacements( \WC_Order $order, array $data ): array {

    $billing  = $data['billing']  ?? array();
    $shipping = $data['shipping'] ?? array();

    return array(
      // Order
      'order_id'          => (string) $order->get_id(),
      'order_number'      => $order->get_order_number(),
      'order_status'      => $order->get_status(),
      'order_date'        => $order->get_date_created()?->date( 'Y-m-d H:i:s' ) ?? '',
      'order_total'       => $order->get_total(),
      'order_subtotal'    => $order->get_subtotal(),
      'order_currency'    => $order->get_currency(),
      'payment_method'    => $order->get_payment_method(),
      'payment_title'     => $order->get_payment_method_title(),

      // Billing
      'billing_first_name'  => $billing['first_name'] ?? '',
      'billing_last_name'   => $billing['last_name']  ?? '',
      'billing_email'       => $billing['email']      ?? '',
      'billing_phone'       => $billing['phone']      ?? '',
      'billing_address_1'   => $billing['address_1']  ?? '',
      'billing_address_2'   => $billing['address_2']  ?? '',
      'billing_city'        => $billing['city']       ?? '',
      'billing_state'       => $billing['state']      ?? '',
      'billing_postcode'    => $billing['postcode']   ?? '',
      'billing_country'     => $billing['country']    ?? '',
      'billing_company'     => $billing['company']    ?? '',

      // Shipping
      'shipping_first_name' => $shipping['first_name'] ?? '',
      'shipping_last_name'  => $shipping['last_name']  ?? '',
      'shipping_address_1'  => $shipping['address_1']  ?? '',
      'shipping_address_2'  => $shipping['address_2']  ?? '',
      'shipping_city'       => $shipping['city']       ?? '',
      'shipping_state'      => $shipping['state']      ?? '',
      'shipping_postcode'   => $shipping['postcode']   ?? '',
      'shipping_country'    => $shipping['country']    ?? '',

      // Customer
      'customer_id'         => (string) $order->get_customer_id(),
      'customer_note'       => $order->get_customer_note(),

      // Items (JSON-encoded summary)
      'items_json'          => $this->buildItemsJson( $order ),
      'items_count'         => (string) $order->get_item_count(),

      // Site
      'site_url'            => get_site_url(),
      'site_name'           => get_bloginfo( 'name' ),
    );
  }

  /**
   * Build a JSON array of line items for {{items_json}}.
   */
  private function buildItemsJson( \WC_Order $order ): string {
    $items = array();
    foreach ( $order->get_items() as $item ) {
      $items[] = array(
        'product_id' => $item->get_product_id(),
        'name'       => $item->get_name(),
        'quantity'   => $item->get_quantity(),
        'subtotal'   => $item->get_subtotal(),
        'total'      => $item->get_total(),
        'sku'        => $item->get_product()?->get_sku() ?? '',
      );
    }
    return wp_json_encode( $items );
  }

  // =========================================================================
  // Write to DB log table
  // =========================================================================

  private function writeLog( array $entry ): void {

    global $wpdb;
    $table = $wpdb->prefix . 'anyapi_log_anyapi';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $wpdb->insert(
      $table,
      array(
        'order_id'  => intval( $entry['order_id'] ),
        'http_code' => intval( $entry['http_code'] ),
        'status'    => sanitize_text_field( $entry['status'] ),
        'trigger'   => sanitize_text_field( $entry['trigger'] ),
        'method'    => sanitize_text_field( $entry['method'] ?? 'POST' ),
        'api_url'   => esc_url_raw( $entry['api_url'] ),
        'payload'   => $entry['payload'],
        'latency'   => isset( $entry['latency'] ) ? intval( $entry['latency'] ) : null,
      ),
      array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
    );

    // Invalidate log count cache
    wp_cache_delete( 'anyapi_log_count', 'anyapi_log_cache' );

    // Keep anyapi_log_count option in sync (used by review banner)
    $count = get_option( 'anyapi_log_count', 0 );
    update_option( 'anyapi_log_count', intval( $count ) + 1 );

  }

}
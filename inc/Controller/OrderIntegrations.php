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

    // Debug log — L1: hook fired
    \Anyapi\AnyapiDebug::log( 'trigger', 'Status change detected', array(
      'order_id'   => $order_id,
      'new_status' => $status,
    ) );

    if ( empty( $integrations ) ) {
      return;
    }

    // ── Loop integrations & fire matching ones ─────────────────────────────
    foreach ( $integrations as $integration ) {

      // Debug log — L2: integration loop start
      \Anyapi\AnyapiDebug::log( 'trigger', 'Checking integration', array(
        'integration_id'   => $integration['id'] ?? '',
        'integration_name' => $integration['name'] ?? '',
        'trigger'          => $integration['trigger'] ?? '',
        'status'           => $integration['status'] ?? '',
      ) );

      // Skip inactive integrations
      if ( ( $integration['status'] ?? 'active' ) !== 'active' ) {
        // Debug log — L3: skip reason
        \Anyapi\AnyapiDebug::log( 'trigger', 'Skipped: status inactive', array(
          'integration_id' => $integration['id'] ?? '',
        ) );
        continue;
      }

      $trigger        = $integration['trigger'] ?? '';
      $integration_id = (string) ( $integration['id'] ?? '' );

      // Check trigger matches this status event
      if ( ! in_array( $trigger, $fired_triggers, true ) ) {
        // Debug log — L3: skip reason
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
        // Debug log — L3: skip reason
        \Anyapi\AnyapiDebug::log( 'trigger', 'Skipped: trigger not in Starter whitelist', array(
          'integration_id' => $integration['id'] ?? '',
        ) );
        continue;
      }

      // Deferred: PlanHelper::canFire() checks cap and schedules Cron retry if exceeded.
      if ( ! \Anyapi\PlanHelper::canFire( $trigger, $order_id, $integration_id ) ) {
        // Debug log — L3: skip reason
        \Anyapi\AnyapiDebug::log( 'trigger', 'Skipped: monthly limit reached', array(
          'integration_id' => $integration['id'] ?? '',
        ) );
        continue;
      }

      // Debug log — L4: fire decision
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
  // requestFollowingRedirects — manual 3xx follower with RFC 7231 method rewriting
  // =========================================================================

  /**
   * wp_remote_request() with redirection > 0 would reuse the original method on
   * 3xx, breaking endpoints (e.g. Google Apps Script) that redirect POST to a
   * GET-only URL. We disable WP's auto-follow and rewrite methods ourselves.
   */
  private function requestFollowingRedirects( string $url, array $args, int $max_hops = 3 ): array {

    $current_url    = $url;
    $current_args   = $args;
    $current_method = $args['method'] ?? 'GET';
    $hop_count      = 0;

    while ( true ) {

      $current_args['redirection'] = 0;
      $current_args['method']      = $current_method;

      $response = wp_remote_request( $current_url, $current_args );

      if ( is_wp_error( $response ) ) {
        return array(
          'response'     => $response,
          'final_url'    => $current_url,
          'final_method' => $current_method,
          'hops'         => $hop_count,
        );
      }

      $code = wp_remote_retrieve_response_code( $response );

      if ( ! in_array( $code, array( 301, 302, 303, 307, 308 ), true ) ) {
        return array(
          'response'     => $response,
          'final_url'    => $current_url,
          'final_method' => $current_method,
          'hops'         => $hop_count,
        );
      }

      $location = wp_remote_retrieve_header( $response, 'location' );
      if ( empty( $location ) ) {
        return array(
          'response'     => $response,
          'final_url'    => $current_url,
          'final_method' => $current_method,
          'hops'         => $hop_count,
        );
      }

      if ( $hop_count >= $max_hops ) {
        return array(
          'response'     => $response,
          'final_url'    => $current_url,
          'final_method' => $current_method,
          'hops'         => $hop_count,
        );
      }

      $next_url    = \WP_Http::make_absolute_url( $location, $current_url );
      $next_method = $current_method;
      $next_args   = $current_args;

      if ( in_array( $code, array( 301, 302, 303 ), true ) ) {
        $next_method = 'GET';
        unset( $next_args['body'] );
        foreach ( array_keys( $next_args['headers'] ?? array() ) as $header_key ) {
          if ( strtolower( $header_key ) === 'content-type' ) {
            unset( $next_args['headers'][ $header_key ] );
          }
        }
      }

      \Anyapi\AnyapiDebug::log( 'fire', 'Redirect followed', array(
        'from_url'    => $current_url,
        'to_url'      => $next_url,
        'status_code' => $code,
        'from_method' => $current_method,
        'to_method'   => $next_method,
      ) );

      $current_url    = $next_url;
      $current_method = $next_method;
      $current_args   = $next_args;
      $hop_count++;

    }
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

    // Debug log
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
      // Basic mode: empty payload sends full order data; static JSON sent as-is.
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

    // Debug log
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

    // Debug log
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

    // ── Email destination ───────────────────────────────────────────────────
    $destination_type = $integration['destination_type'] ?? 'url';
    if ( 'email' === $destination_type ) {
      $to      = $this->interpolateOrderId( $integration['email_to'] ?? '', $order_id );
      $subject = $this->interpolateOrderId( $integration['email_subject'] ?? '', $order_id );
      $body    = trim( (string) ( $integration['email_preamble'] ?? '' ) );
      $summary = $this->buildOrderSummary( $order_id );
      $body    = ( '' !== $body ) ? $body . "\n\n" . $summary : $summary;

      $start   = microtime( true );
      $sent    = wp_mail( $to, $subject, $body );
      $latency = (int) round( ( microtime( true ) - $start ) * 1000 );

      $this->writeLog( array(
        'order_id'  => $order_id,
        'http_code' => $sent ? 200 : 0,
        'status'    => $sent ? 'success' : 'error',
        'trigger'   => $integration['trigger'],
        'method'    => 'EMAIL',
        'api_url'   => 'mailto:' . $to,
        'payload'   => $body,
        'response'  => $sent ? 'Email sent' : 'wp_mail failed',
        'latency'   => $latency,
      ) );
      return;
    }

    // ── HTTP request ──────────────────────────────────────────────────────
    $api_url     = $integration['api_url']     ?? '';
    $http_method = $integration['http_method'] ?? 'POST';   // Default POST
    $custom_hdrs = $integration['headers']     ?? array();  // [{key,value},...]

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

    $result       = $this->requestFollowingRedirects( $api_url, $args );
    $response     = $result['response'];
    $final_url    = $result['final_url'];
    $final_method = $result['final_method'];

    $latency_ms = (int) round( ( microtime( true ) - $start_time ) * 1000 );

    // Debug log
    if ( is_wp_error( $response ) ) {
      \Anyapi\AnyapiDebug::log( 'fire', 'HTTP error (WP_Error)', array(
        'integration_id' => $integration['id'] ?? '',
        'url'            => $final_url,
        'error_message'  => $response->get_error_message(),
      ) );
    } else {
      $resp_code = wp_remote_retrieve_response_code( $response );
      $resp_body = wp_remote_retrieve_body( $response );
      \Anyapi\AnyapiDebug::log( 'fire', 'HTTP response', array(
        'integration_id' => $integration['id'] ?? '',
        'url'            => $final_url,
        'method'         => $final_method,
        'response_code'  => $resp_code,
        'response_body'  => mb_substr( $resp_body, 0, 500 ),
      ) );
    }

    // ── Log result ────────────────────────────────────────────────────────
    // Log the final method/URL after redirects, not the originally configured
    // ones, so the log reflects what actually happened over the wire.
    if ( is_wp_error( $response ) ) {
      $this->writeLog( array(
        'order_id'  => $order_id,
        'http_code' => 0,
        'status'    => 'error',
        'trigger'   => $integration['trigger'],
        'method'    => $final_method,
        'api_url'   => $final_url,
        'payload'   => $filtered_payload,
        'response'  => $response->get_error_message(),
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
      'method'    => $final_method,
      'api_url'   => $final_url,
      'payload'   => $filtered_payload,
      'response'  => wp_remote_retrieve_body( $response ),
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
  // Email destination helpers
  // =========================================================================

  /**
   * Targeted single-token replace for email To/Subject fields.
   * Deliberately does not call interpolatePayload() — the full {{}} engine
   * stays Lite+/Expert-only.
   */
  private function interpolateOrderId( string $text, int $order_id ): string {
    return str_replace( '{{order_id}}', (string) $order_id, $text );
  }

  /**
   * Build a plain-text order summary from the live order object.
   * Reads wc_get_order() directly rather than the stored payload array,
   * since store-api orders serialize line_items to null stubs.
   */
  private function buildOrderSummary( int $order_id ): string {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
      return '';
    }

    $w_unit = get_option( 'woocommerce_weight_unit', '' );
    $lines  = array();
    $lines[] = sprintf( 'Order #%s — %s', $order->get_order_number(), $order->get_status() );
    $lines[] = 'Date: ' . $order->get_date_created()?->date( 'Y-m-d H:i' );
    $lines[] = sprintf(
      'Customer: %s %s <%s>',
      $order->get_billing_first_name(),
      $order->get_billing_last_name(),
      $order->get_billing_email()
    );
    $lines[] = 'Items:';
    foreach ( $order->get_items() as $item ) {
      $product = $item->get_product();
      $name    = $item->get_name();
      $qty     = $item->get_quantity();
      $total   = $order->get_formatted_line_subtotal( $item ); // includes currency
      $lines[] = sprintf( '  - %s × %s — %s', $name, $qty, wp_strip_all_tags( $total ) );

      $weight = ( $product && '' !== $product->get_weight() )
        ? $product->get_weight() . ' ' . $w_unit
        : '—';
      // wc_format_dimensions() already appends the dimension unit.
      $dims = ( $product ) ? wc_format_dimensions( $product->get_dimensions( false ) ) : '';
      if ( '' === $dims || 'N/A' === $dims ) {
        $dims = '—';
      }
      $lines[] = sprintf( '      Weight: %s  |  Dimensions: %s', $weight, $dims );
    }
    $lines[] = 'Total: ' . wp_strip_all_tags( $order->get_formatted_order_total() );

    $summary = implode( "\n", $lines );

    // WooCommerce formatted totals/dimensions return HTML entities; decode for plain-text email.
    return html_entity_decode( $summary, ENT_QUOTES, 'UTF-8' );
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
        // Not sanitize_text_field()'d — raw JSON/HTML is needed for debugging; escape on output instead.
        'response'  => mb_substr( (string) ( $entry['response'] ?? '' ), 0, 2000 ),
        'latency'   => isset( $entry['latency'] ) ? intval( $entry['latency'] ) : null,
      ),
      array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
    );

    // Invalidate log count cache
    wp_cache_delete( 'anyapi_log_count', 'anyapi_log_cache' );

    // Keep log count in sync (used by review banner)
    $count = get_option( 'anyapi_log_count', 0 );
    update_option( 'anyapi_log_count', intval( $count ) + 1 );

  }

}
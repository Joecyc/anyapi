<?php
/**
 * OrderIntegrationHandler.php
 *
 * AJAX handler for Order Integration CRUD.
 *
 * @package AnyApi
 */


namespace Anyapi\Controller;

if ( ! defined( 'ABSPATH' ) ) exit;

class OrderIntegrationHandler {

  const OPTION_KEY = 'anyapi_wc_orderapi';
  const NONCE_KEY  = 'anyapi_integration_nonce';

  const ALLOWED_METHODS = array( 'POST', 'PUT', 'PATCH' );

  // ── Register AJAX hooks ───────────────────────────────────────────────────

  public function init(): void {
    add_action( 'wp_ajax_anyapi_save_integration',   array( $this, 'ajaxSave' ) );
    add_action( 'wp_ajax_anyapi_load_integration',   array( $this, 'ajaxLoad' ) );
    add_action( 'wp_ajax_anyapi_delete_integration', array( $this, 'ajaxDelete' ) );
    add_action( 'wp_ajax_anyapi_list_integrations',  array( $this, 'ajaxList' ) );
    add_action( 'wp_ajax_anyapi_toggle_integration', array( $this, 'ajaxToggle' ) );
  }

  // =========================================================================
  // AJAX: Save (create or update)
  // =========================================================================

  public function ajaxSave(): void {

    check_ajax_referer( self::NONCE_KEY, 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
    }

    // ── Sanitize ─────────────────────────────────────────────────────────

    $integration_id    = intval( $_POST['integration_id'] ?? 0 );
    $name              = sanitize_text_field( wp_unslash( $_POST['name']      ?? '' ) );
    $api_url           = esc_url_raw( wp_unslash( $_POST['api_url']           ?? '' ) );
    $api_key_id        = sanitize_text_field( wp_unslash( $_POST['api_key_id'] ?? '' ) );
    $payload           = sanitize_textarea_field( wp_unslash( $_POST['payload']  ?? '' ) );
    $trigger           = sanitize_key( wp_unslash( $_POST['trigger']             ?? '' ) );

    // HTTP method
    $http_method       = strtoupper( sanitize_key( wp_unslash( $_POST['http_method'] ?? 'POST' ) ) );

    $filter_mode       = sanitize_key( wp_unslash( $_POST['filter_mode']         ?? 'basic' ) );
    $raw_selected      = sanitize_textarea_field( wp_unslash( $_POST['selected_fields'] ?? '[]' ) );
    $raw_field_order   = sanitize_textarea_field( wp_unslash( $_POST['field_order']     ?? '[]' ) );
    $raw_json_override = sanitize_textarea_field( wp_unslash( $_POST['raw_json_override'] ?? '' ) );

    // Custom headers  [{key, value}, ...]
    $raw_headers       = sanitize_textarea_field( wp_unslash( $_POST['headers'] ?? '[]' ) );

    $selected_fields   = $this->sanitizeFieldArray( json_decode( $raw_selected,    true ) ?? array() );
    $field_order       = $this->sanitizeFieldArray( json_decode( $raw_field_order, true ) ?? array() );
    $headers           = $this->sanitizeHeaders(    json_decode( $raw_headers,     true ) ?? array() );

    // ── Plan data ─────────────────────────────────────────────────────────

    $limits = \Anyapi\PlanHelper::currentLimits();
    $is_new = ( $integration_id === 0 );

    // ── Validation ───────────────────────────────────────────────────────

    $errors = array();

    if ( empty( $api_url ) ) {
      $errors[] = __( 'API URL is required.', 'anyapi' );
    } elseif ( ! filter_var( $api_url, FILTER_VALIDATE_URL ) ) {
      $errors[] = __( 'Invalid API URL format.', 'anyapi' );
    } elseif ( strpos( $api_url, 'https://' ) !== 0 ) {
      $errors[] = __( 'API URL must use HTTPS.', 'anyapi' );
    }

    // Payload is optional; validate JSON only when present.
    if ( '' !== trim( $payload ) && json_decode( $payload ) === null ) {
      $errors[] = __( 'Payload is not valid JSON.', 'anyapi' );
    }

    if ( empty( $trigger ) ) {
      $errors[] = __( 'Trigger is required.', 'anyapi' );
    }

    if ( ! in_array( $http_method, self::ALLOWED_METHODS, true ) ) {
      $http_method = 'POST';  // Fallback silently — not user-facing
    }

    if ( ! in_array( $filter_mode, array( 'basic', 'advanced', 'expert' ), true ) ) {
      $errors[] = __( 'Invalid filter mode.', 'anyapi' );
    }

    // Validate api_key_id: must be empty or a valid ak_xxx ID
    if ( ! empty( $api_key_id ) ) {
      if ( ! preg_match( '/^ak_[a-f0-9]+$/i', $api_key_id ) ) {
        $errors[] = __( 'Invalid API Key reference.', 'anyapi' );
      } else {
        // Verify the key actually exists
        $stored_keys = get_option( ApiKeyHandler::OPTION_KEY, array() );
        if ( ! isset( $stored_keys[ $api_key_id ] ) ) {
          $errors[] = __( 'Selected API Key not found. Please re-select.', 'anyapi' );
        }
      }
    }

    if ( ! empty( $errors ) ) {
      wp_send_json_error( array( 'message' => implode( ' ', $errors ) ), 422 );
    }

    // ── Trigger gate via filter hook ──────────────────────────────

    $starter_triggers = array( 'watch_orders', 'watch_new_orders', 'watch_processing_order' );
    $allowed_triggers = apply_filters( 'anyapi_allowed_triggers', $starter_triggers );

    // null = all allowed (Lite active); array = whitelist check
    if ( $allowed_triggers !== null && ! in_array( $trigger, (array) $allowed_triggers, true ) ) {
      wp_send_json_error( array(
        'message'     => __( 'This trigger is available on Lite plan and above.', 'anyapi' ),
        'gate'        => 'trigger',
        'upgrade_url' => $limits['upgrade_url'],
      ), 403 );
    }

    // ── JSON filter gate via filter hook ───────────────────────────

    $json_filter_enabled = apply_filters( 'anyapi_json_filter_enabled', false );
    if ( ! $json_filter_enabled && in_array( $filter_mode, array( 'advanced', 'expert' ), true ) ) {
      wp_send_json_error( array(
        'message'     => __( 'JSON Filter is available on Lite plan and above.', 'anyapi' ),
        'gate'        => 'json_filter',
        'upgrade_url' => $limits['upgrade_url'],
      ), 403 );
    }

    // ── Build & persist ───────────────────────────────────────────────────

    $integrations = get_option( self::OPTION_KEY, array() );
    $now          = current_time( 'mysql' );

    if ( ! $is_new && isset( $integrations[ $integration_id ] ) ) {

      // UPDATE — migrate old record first
      $record                        = $this->migrateRecord( $integrations[ $integration_id ] );
      $record['name']                = $name ?: ( $record['name'] ?? 'Integration #' . $integration_id );
      $record['api_url']             = $api_url;
      $record['api_key_id']          = $api_key_id;
      $record['payload']             = $payload;
      $record['trigger']             = $trigger;
      $record['http_method']         = $http_method;
      $record['headers']             = $headers;
      $record['filter_mode']         = $filter_mode;
      $record['selected_fields']     = $selected_fields;
      $record['field_order']         = $field_order;
      $record['raw_json_override']   = $raw_json_override;
      $record['updated_at']          = $now;

    } else {

      // CREATE
      $integration_id = $this->nextId( $integrations );
      $record = array(
        'id'                 => $integration_id,
        'name'               => $name ?: 'Integration #' . $integration_id,
        'api_url'            => $api_url,
        'api_key_id'         => $api_key_id,
        'payload'            => $payload,
        'trigger'            => $trigger,
        'http_method'        => $http_method,
        'headers'            => $headers,
        'filter_mode'        => $filter_mode,
        'selected_fields'    => $selected_fields,
        'field_order'        => $field_order,
        'raw_json_override'  => $raw_json_override,
        'status'             => 'active',
        'created_at'         => $now,
        'updated_at'         => $now,
      );
    }

    $integrations[ $integration_id ] = $record;
    update_option( self::OPTION_KEY, $integrations );

    wp_send_json_success( array(
      'message'        => $is_new
        ? __( 'Integration created.', 'anyapi' )
        : __( 'Integration updated.', 'anyapi' ),
      'integration_id' => $integration_id,
      'record'         => $this->safeRecord( $record ),
    ) );
  }

  // =========================================================================
  // AJAX: Load single record (for edit form — returns full record)
  // =========================================================================

  public function ajaxLoad(): void {

    check_ajax_referer( self::NONCE_KEY, 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
    }

    $id           = intval( $_POST['integration_id'] ?? 0 );
    $integrations = get_option( self::OPTION_KEY, array() );

    if ( ! isset( $integrations[ $id ] ) ) {
      wp_send_json_error( array( 'message' => 'Integration not found.' ), 404 );
    }

    // Migrate old record schema before returning
    $record = $this->migrateRecord( $integrations[ $id ] );

    // Persist migration if record was updated
    if ( $record !== $integrations[ $id ] ) {
      $integrations[ $id ] = $record;
      update_option( self::OPTION_KEY, $integrations );
    }

    wp_send_json_success( array( 'record' => $record ) );
  }

  // =========================================================================
  // AJAX: Delete
  // =========================================================================

  public function ajaxDelete(): void {

    check_ajax_referer( self::NONCE_KEY, 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
    }

    $id           = intval( $_POST['integration_id'] ?? 0 );
    $integrations = get_option( self::OPTION_KEY, array() );

    if ( ! isset( $integrations[ $id ] ) ) {
      wp_send_json_error( array( 'message' => 'Integration not found.' ), 404 );
    }

    unset( $integrations[ $id ] );
    update_option( self::OPTION_KEY, $integrations );

    wp_send_json_success( array(
      'message'        => __( 'Integration deleted.', 'anyapi' ),
      'integration_id' => $id,
    ) );
  }

  // =========================================================================
  // AJAX: List all (safe subset — no payload / credentials)
  // =========================================================================

  public function ajaxList(): void {

    check_ajax_referer( self::NONCE_KEY, 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
    }

    $integrations = get_option( self::OPTION_KEY, array() );

    wp_send_json_success( array(
      'integrations' => array_map(
        fn( $r ) => $this->safeRecord( $this->migrateRecord( $r ) ),
        array_values( $integrations )
      ),
    ) );
  }

  // =========================================================================
  // AJAX: Toggle active / inactive
  // =========================================================================

  public function ajaxToggle(): void {

    check_ajax_referer( self::NONCE_KEY, 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
    }

    $id     = intval( $_POST['integration_id'] ?? 0 );
    $active = ( sanitize_text_field( wp_unslash( $_POST['active'] ?? '0' ) ) ) === '1';

    $integrations = get_option( self::OPTION_KEY, array() );

    if ( ! isset( $integrations[ $id ] ) ) {
      wp_send_json_error( array( 'message' => 'Integration not found.' ), 404 );
    }

    $integrations[ $id ]['status']     = $active ? 'active' : 'inactive';
    $integrations[ $id ]['updated_at'] = current_time( 'mysql' );
    update_option( self::OPTION_KEY, $integrations );

    wp_send_json_success( array(
      'message'        => $active
        ? __( 'Integration activated.', 'anyapi' )
        : __( 'Integration deactivated.', 'anyapi' ),
      'integration_id' => $id,
      'status'         => $integrations[ $id ]['status'],
    ) );
  }

  // =========================================================================
  // JS data (wp_localize_script)
  // =========================================================================

  public static function getJsData(): array {

    $plan   = \Anyapi\PlanHelper::currentPlan();
    $limits = \Anyapi\PlanHelper::currentLimits();
    $used   = \Anyapi\PlanHelper::usedCallsThisMonth();

    $call_limit = $limits['monthly_calls'] === PHP_INT_MAX ? -1 : $limits['monthly_calls'];

    // Pass safe API Key list so Step 1 can render a dropdown
    $raw_keys   = get_option( ApiKeyHandler::OPTION_KEY, array() );
    $key_list   = array_map(
      fn( $k ) => array(
        'id'     => $k['id'],
        'name'   => $k['name'],
        'type'   => $k['type'],
        'status' => $k['status'] ?? 'active',
      ),
      array_values( $raw_keys )
    );

    return array(
      'ajax_url'          => admin_url( 'admin-ajax.php' ),
      'nonce'             => wp_create_nonce( self::NONCE_KEY ),
      'plan'              => $plan,
      'plan_label'        => $limits['label'],
      'upgrade_url'       => $limits['upgrade_url'],
      'limits'            => array(
        'monthly_calls'     => $call_limit,
        'json_filter'       => $limits['json_filter'],
        'all_triggers'      => $limits['all_triggers'],
        'allowed_triggers'  => $limits['allowed_triggers'],
      ),
      'usage'             => array(
        'calls_this_month'  => $used,
      ),
      'api_keys'          => $key_list,    // for Step 1 dropdown
      'i18n'              => array(
        'save_success'      => __( 'Integration saved successfully!', 'anyapi' ),
        'save_error'        => __( 'Failed to save. Please check your inputs.', 'anyapi' ),
        'delete_confirm'    => __( 'Delete this integration? This cannot be undone.', 'anyapi' ),
        'delete_success'    => __( 'Integration deleted.', 'anyapi' ),
        'toggle_success'    => __( 'Status updated.', 'anyapi' ),
        'trigger_locked'    => __( 'This trigger is available on Lite plan and above.', 'anyapi' ),
        'filter_locked'     => __( 'JSON Filter is available on Lite plan and above.', 'anyapi' ),
        'monthly_limit_hit' => __( 'Monthly call limit reached (500/mo on Free plan). Upgrade to continue.', 'anyapi' ),
        'upgrade_cta'       => __( 'Upgrade Now →', 'anyapi' ),
        'saving'            => __( 'Saving…', 'anyapi' ),
        'deleting'          => __( 'Deleting…', 'anyapi' ),
        'loading'           => __( 'Loading…', 'anyapi' ),
        'no_api_key'        => __( 'No authentication (public API)', 'anyapi' ),
      ),
    );
  }

  // =========================================================================
  // Helpers
  // =========================================================================

  /**
   * Migrate old record schema to v2 without data loss.
   *
   * v1 had:  api_key (raw string)
   * v2 uses: api_key_id (ak_xxx reference)
   *
   * If old api_key was already an ak_xxx ID → move it to api_key_id.
   * If old api_key was a raw token → discard (cannot recover mapping).
   */
  private function migrateRecord( array $r ): array {

    // api_key → api_key_id
    if ( isset( $r['api_key'] ) && ! isset( $r['api_key_id'] ) ) {
      $old_key = $r['api_key'];
      $r['api_key_id'] = preg_match( '/^ak_[a-f0-9]+$/i', $old_key ) ? $old_key : '';
      unset( $r['api_key'] );
    }

    // Backfill new fields with defaults
    $r['name']        ??= 'Integration #' . ( $r['id'] ?? '' );
    $r['http_method'] ??= 'POST';
    $r['headers']     ??= array();
    $r['updated_at']  ??= $r['created_at'] ?? current_time( 'mysql' );

    return $r;
  }

  /**
   * Return safe subset of a record for JS (no payload, no credentials).
   */
  private function safeRecord( array $r ): array {
    return array(
      'id'          => $r['id'],
      'name'        => $r['name']        ?? 'Integration #' . $r['id'],
      'api_url'     => $r['api_url'],
      'api_key_id'  => $r['api_key_id']  ?? '',  // [CHANGED from api_key]
      'trigger'     => $r['trigger'],
      'http_method' => $r['http_method'] ?? 'POST',
      'filter_mode' => $r['filter_mode'],
      'status'      => $r['status'],
      'created_at'  => $r['created_at'],
      'updated_at'  => $r['updated_at'],
    );
  }

  /**
   * Sanitize field path strings.
   * Allows: a-z A-Z 0-9 _ .  (e.g. "billing.email")
   */
  private function sanitizeFieldArray( array $fields ): array {
    return array_values(
      array_filter(
        array_map(
          fn( $f ) => preg_replace( '/[^a-zA-Z0-9_.]/', '', (string) $f ),
          $fields
        ),
        fn( $f ) => ! empty( $f )
      )
    );
  }

  /**
   * Sanitize custom headers array.
   * Input:  [['key' => 'X-Foo', 'value' => 'bar'], ...]
   * Output: cleaned array, max 20 headers, key restricted to safe chars
   */
  private function sanitizeHeaders( array $headers ): array {
    $clean = array();
    foreach ( array_slice( $headers, 0, 20 ) as $h ) {
      if ( ! is_array( $h ) ) continue;
      $key   = sanitize_text_field( $h['key']   ?? '' );
      $value = sanitize_text_field( $h['value'] ?? '' );
      if ( empty( $key ) ) continue;
      // Header key: only RFC 7230-safe chars (no colon, no space)
      $key = preg_replace( '/[^A-Za-z0-9\-_]/', '', $key );
      if ( ! empty( $key ) ) {
        $clean[] = array( 'key' => $key, 'value' => $value );
      }
    }
    return $clean;
  }

  /**
   * Next available integer ID.
   */
  private function nextId( array $integrations ): int {
    return empty( $integrations ) ? 1 : max( array_keys( $integrations ) ) + 1;
  }

}

<?php
/**
 * ApiKeyHandler.php
 *
 * AJAX handler for API Key CRUD.
 *
 * @package AnyApi
 */


namespace Anyapi\Controller;

if ( ! defined( 'ABSPATH' ) ) exit;

class ApiKeyHandler {

  const OPTION_KEY = 'anyapi_wc_apikey';
  const NONCE_KEY  = 'anyapi_apikey_nonce';

  public function init(): void {
    add_action( 'wp_ajax_anyapi_save_apikey',   array( $this, 'ajaxSave' ) );
    add_action( 'wp_ajax_anyapi_delete_apikey', array( $this, 'ajaxDelete' ) );
    add_action( 'wp_ajax_anyapi_toggle_apikey', array( $this, 'ajaxToggle' ) );
    add_action( 'wp_ajax_anyapi_list_apikeys',  array( $this, 'ajaxList' ) );
  }

  // ── Save (create or update) ───────────────────────────────────────────────

  public function ajaxSave(): void {

    check_ajax_referer( self::NONCE_KEY, 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
    }

    $edit_id  = sanitize_text_field( wp_unslash( $_POST['id']     ?? '' ) );
    $name     = sanitize_text_field( wp_unslash( $_POST['name']   ?? '' ) );
    $type     = sanitize_key( wp_unslash( $_POST['type']          ?? 'bearer' ) );
    $status   = sanitize_key( wp_unslash( $_POST['status']        ?? 'active' ) );
    $token    = sanitize_textarea_field( wp_unslash( $_POST['key']      ?? '' ) );
    $username = sanitize_text_field(     wp_unslash( $_POST['username'] ?? '' ) );
    $password = sanitize_textarea_field( wp_unslash( $_POST['password'] ?? '' ) );

    // ── Validation ────────────────────────────────────────────────────────

    $errors = array();

    if ( empty( $name ) ) {
      $errors['name'] = __( 'Key name is required.', 'anyapi' );
    }

    if ( ! in_array( $type, array( 'bearer', 'basic' ), true ) ) {
      $errors['type'] = __( 'Invalid authentication type.', 'anyapi' );
    }

    if ( $type === 'bearer' && empty( $edit_id ) && empty( $token ) ) {
      $errors['key'] = __( 'Bearer token is required.', 'anyapi' );
    }

    if ( $type === 'basic' && empty( $edit_id ) ) {
      if ( empty( $username ) ) $errors['username'] = __( 'Username is required.', 'anyapi' );
      if ( empty( $password ) ) $errors['password'] = __( 'Password is required.', 'anyapi' );
    }

    if ( ! empty( $errors ) ) {
      wp_send_json_error( array( 'message' => 'Validation failed.', 'fields' => $errors ), 422 );
    }

    // ── Plan gate — key count limit ────────────────────────────────────────

    $keys      = get_option( self::OPTION_KEY, array() );
    $limits    = \Anyapi\PlanHelper::currentLimits();
    $key_limit = $limits['api_keys_limit'];
    $is_new    = empty( $edit_id );

    if ( $is_new && $key_limit !== PHP_INT_MAX && count( $keys ) >= $key_limit ) {
      wp_send_json_error( array(
        'message'     => __( 'API Key limit reached for your current plan.', 'anyapi' ),
        'gate'        => 'key_limit',
        'upgrade_url' => $limits['upgrade_url'],
      ), 403 );
    }

    // ── Build record ───────────────────────────────────────────────────────

    $now = current_time( 'mysql' );

    if ( ! $is_new && isset( $keys[ $edit_id ] ) ) {
      $record                 = $keys[ $edit_id ];
      $record['name']         = $name;
      $record['type']         = $type;
      $record['status']       = ( $status === 'active' ) ? 'active' : 'inactive';
      $record['updated_at']   = $now;

      if ( $type === 'bearer' && ! empty( $token ) ) {
        $record['key']      = $token;
        $record['username'] = '';
        $record['password'] = '';
      } elseif ( $type === 'basic' ) {
        $record['key']      = '';
        if ( ! empty( $username ) ) $record['username'] = $username;
        if ( ! empty( $password ) ) $record['password'] = $password;
      }

    } else {
      $new_id = 'ak_' . uniqid();
      $record = array(
        'id'         => $new_id,
        'name'       => $name,
        'type'       => $type,
        'key'        => $type === 'bearer' ? $token    : '',
        'username'   => $type === 'basic'  ? $username : '',
        'password'   => $type === 'basic'  ? $password : '',
        'status'     => ( $status === 'active' ) ? 'active' : 'inactive',
        'created_at' => $now,
        'updated_at' => $now,
      );
      $edit_id = $new_id;
    }

    $keys[ $edit_id ] = $record;
    update_option( self::OPTION_KEY, $keys );

    wp_send_json_success( array(
      'message' => $is_new
        ? __( 'API Key created.', 'anyapi' )
        : __( 'API Key updated.', 'anyapi' ),
      'key'     => $this->safeRecord( $record ),
      'count'   => count( $keys ),
    ) );
  }

  // ── Delete ────────────────────────────────────────────────────────────────

  public function ajaxDelete(): void {

    check_ajax_referer( self::NONCE_KEY, 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
    }

    $id   = sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) );
    $keys = get_option( self::OPTION_KEY, array() );

    if ( empty( $id ) || ! isset( $keys[ $id ] ) ) {
      wp_send_json_error( array( 'message' => 'Key not found.' ), 404 );
    }

    // Safety: check if any integration references this key
    $integrations = get_option( 'anyapi_wc_orderapi', array() );
    $in_use       = array_filter( $integrations, fn( $r ) => ( $r['api_key_id'] ?? '' ) === $id );
    if ( ! empty( $in_use ) ) {
      wp_send_json_error( array(
        'message' => sprintf(
          // translators: %d is the number of integrations currently using this key
          __( 'Cannot delete: this key is used by %d integration(s). Remove or reassign them first.', 'anyapi' ),
          count( $in_use )
        ),
        'in_use'  => array_keys( $in_use ),
      ), 409 );
    }

    unset( $keys[ $id ] );
    update_option( self::OPTION_KEY, $keys );

    wp_send_json_success( array(
      'message' => __( 'API Key deleted.', 'anyapi' ),
      'id'      => $id,
      'count'   => count( $keys ),
    ) );
  }

  // ── Toggle status ─────────────────────────────────────────────────────────

  public function ajaxToggle(): void {

    check_ajax_referer( self::NONCE_KEY, 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
    }

    $id     = sanitize_text_field( wp_unslash( $_POST['id']     ?? '' ) );
    $active = ( sanitize_text_field( wp_unslash( $_POST['active'] ?? '0' ) ) ) === '1';
    $keys   = get_option( self::OPTION_KEY, array() );

    if ( empty( $id ) || ! isset( $keys[ $id ] ) ) {
      wp_send_json_error( array( 'message' => 'Key not found.' ), 404 );
    }

    $keys[ $id ]['status']     = $active ? 'active' : 'inactive';
    $keys[ $id ]['updated_at'] = current_time( 'mysql' );
    update_option( self::OPTION_KEY, $keys );

    wp_send_json_success( array(
      'message' => $active
        ? __( 'Key activated.', 'anyapi' )
        : __( 'Key deactivated.', 'anyapi' ),
      'id'      => $id,
      'status'  => $keys[ $id ]['status'],
    ) );
  }

  // ── List all keys ───────────────────────────

  public function ajaxList(): void {

    check_ajax_referer( self::NONCE_KEY, 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
    }

    $keys = get_option( self::OPTION_KEY, array() );

    wp_send_json_success( array(
      'keys' => array_map(
        fn( $k ) => $this->safeRecord( $k ),
        array_values( $keys )
      ),
    ) );
  }

  // ── Helpers ───────────────────────────────────────────────────────────────

  /**
   * Safe record for JS — no raw credentials.
   */
  private function safeRecord( array $record ): array {
    return array(
      'id'             => $record['id'],
      'name'           => $record['name'],
      'type'           => $record['type'],
      'status'         => $record['status'],
      'created_at'     => $record['created_at']  ?? '',
      'updated_at'     => $record['updated_at']  ?? '',
      'has_credentials'=> ! empty( $record['key'] )
                          || ( ! empty( $record['username'] ) && ! empty( $record['password'] ) ),
    );
  }

  /**
   * Data passed via wp_localize_script.
   */
  public static function getJsData(): array {

    $limits  = \Anyapi\PlanHelper::currentLimits();
    $keys    = get_option( self::OPTION_KEY, array() );
    $limit   = $limits['api_keys_limit'];

    return array(
      'ajax_url'    => admin_url( 'admin-ajax.php' ),
      'nonce'       => wp_create_nonce( self::NONCE_KEY ),
      'plan'        => \Anyapi\PlanHelper::currentPlan(),
      'key_limit'   => $limit === PHP_INT_MAX ? -1 : $limit,
      'key_count'   => count( $keys ),
      'upgrade_url' => $limits['upgrade_url'],
      'keys_meta'   => array_map(
        fn( $k ) => array(
          'id'     => $k['id'],
          'name'   => $k['name'],
          'type'   => $k['type'],
          'status' => $k['status'] ?? 'active',
        ),
        array_values( $keys )
      ),
      'i18n' => array(
        'save_success'   => __( 'API Key saved successfully.', 'anyapi' ),
        'delete_success' => __( 'API Key deleted.', 'anyapi' ),
        'toggle_success' => __( 'Status updated.', 'anyapi' ),
        'save_error'     => __( 'Failed to save. Please check your inputs.', 'anyapi' ),
        'limit_reached'  => __( 'API Key limit reached. Upgrade to add more.', 'anyapi' ),
        'confirm_delete' => __( 'Delete this key? Integrations using it will stop working.', 'anyapi' ),
        'in_use_error'   => __( 'Cannot delete: this key is in use by active integrations.', 'anyapi' ),
        'new_key_title'  => __( 'New API Key', 'anyapi' ),
        'edit_key_title' => __( 'Edit API Key', 'anyapi' ),
        'saving'         => __( 'Saving…', 'anyapi' ),
        'deleting'       => __( 'Deleting…', 'anyapi' ),
      ),
    );
  }

}

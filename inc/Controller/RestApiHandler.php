<?php
/**
 * RestApiHandler.php
 *
 * AJAX proxy for WC REST API Tester.
 * Receives request params from JS, executes wp_remote_request(),
 * and returns { http_code, body, headers } to the browser.
 *
 * Registered action: wp_ajax_anyapi_rest_request
 */

namespace Anyapi\Controller;

if ( ! defined( 'ABSPATH' ) ) exit;

class RestApiHandler {

  const NONCE_KEY = 'anyapi_rest_request_nonce';

  public function init(): void {
    add_action( 'wp_ajax_anyapi_rest_request', array( $this, 'ajaxRequest' ) );
  }

  // ── AJAX handler ─────────────────────────────────────────────────────────

  public function ajaxRequest(): void {

    check_ajax_referer( self::NONCE_KEY, 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
    }

    // ── Inputs ──────────────────────────────────────────────────────────────
    $method   = strtoupper( sanitize_text_field( wp_unslash( $_POST['method']  ?? 'GET' ) ) );
    $path     = sanitize_text_field( wp_unslash( $_POST['url']     ?? '' ) );
    $raw_body = sanitize_textarea_field( wp_unslash( $_POST['body']    ?? '' ) );
    $auth_raw = sanitize_textarea_field( wp_unslash( $_POST['auth']    ?? '{}' ) );
    $hdrs_raw = sanitize_textarea_field( wp_unslash( $_POST['headers'] ?? '{}' ) );

    // Validate method
    $allowed_methods = array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' );
    if ( ! in_array( $method, $allowed_methods, true ) ) {
      wp_send_json_error( array( 'message' => 'Invalid HTTP method.' ), 400 );
    }

    // Validate path
    if ( empty( $path ) ) {
      wp_send_json_error( array( 'message' => 'Endpoint URL is required.' ), 400 );
    }

    // ── Build full URL ───────────────────────────────────────────────────────
    // Accept absolute URL or path relative to site
    if ( str_starts_with( $path, 'http://' ) || str_starts_with( $path, 'https://' ) ) {
      $full_url = $path;
    } else {
      $full_url = get_site_url() . '/' . ltrim( $path, '/' );
    }

    if ( ! filter_var( $full_url, FILTER_VALIDATE_URL ) ) {
      wp_send_json_error( array( 'message' => 'Invalid URL: ' . esc_html( $full_url ) ), 400 );
    }

    // ── Auth ─────────────────────────────────────────────────────────────────
    $auth      = json_decode( $auth_raw, true ) ?: array();
    $auth_type = $auth['type'] ?? 'none';
    $auth_header = '';

    switch ( $auth_type ) {

      case 'saved':
        $key_id = sanitize_text_field( $auth['key_id'] ?? '' );
        $keys   = get_option( 'anyapi_wc_apikey', array() );
        $key    = $keys[ $key_id ] ?? null;
        if ( $key ) {
          if ( ( $key['type'] ?? 'bearer' ) === 'basic' ) {
            $encoded      = base64_encode( $key['username'] . ':' . $key['password'] );
            $auth_header  = 'Basic ' . $encoded;
          } else {
            $auth_header  = 'Bearer ' . $key['key'];
          }
        }
        break;

      case 'wc_basic':
        $ck          = sanitize_text_field( $auth['consumer_key']    ?? '' );
        $cs          = sanitize_text_field( $auth['consumer_secret'] ?? '' );
        $auth_header = 'Basic ' . base64_encode( $ck . ':' . $cs );
        break;

      case 'bearer':
        $token       = sanitize_text_field( $auth['token'] ?? '' );
        $auth_header = 'Bearer ' . $token;
        break;
    }

    // ── Headers ───────────────────────────────────────────────────────────────
    $custom_headers = json_decode( $hdrs_raw, true ) ?: array();
    $headers        = array(
      'Content-Type' => 'application/json',
    );
    if ( $auth_header ) {
      $headers['Authorization'] = $auth_header;
    }
    // Merge custom headers (allow overriding Content-Type etc.)
    foreach ( $custom_headers as $k => $v ) {
      $headers[ sanitize_text_field( $k ) ] = sanitize_text_field( $v );
    }

    // ── Request args ─────────────────────────────────────────────────────────
    $args = array(
      'method'             => $method,
      'headers'            => $headers,
      'timeout'            => 30,
      // Allow requests to own site (loopback)
      'reject_unsafe_urls' => false,
      // Disable SSL verify for loopback / self-signed certs on shared hosting.
      'sslverify'          => apply_filters( 'anyapi_rest_sslverify', ! $this->isLoopback( $full_url ) ),
    );

    // Body for POST/PUT/PATCH
    if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) && ! empty( $raw_body ) ) {
      // Validate JSON
      json_decode( $raw_body );
      if ( json_last_error() !== JSON_ERROR_NONE ) {
        wp_send_json_error( array( 'message' => 'Invalid JSON in request body.' ), 400 );
      }
      $args['body'] = $raw_body;
    }

    // ── Execute ───────────────────────────────────────────────────────────────
    $response = wp_remote_request( $full_url, $args );

    if ( is_wp_error( $response ) ) {
      $error_msg = $response->get_error_message();
      // Return actual WP_Error message so JS can display the real reason.
      wp_send_json_error( array(
        'message'   => $error_msg,
        'http_code' => 0,
        'body'      => $error_msg,
        'headers'   => array(),
      ) );
      return;
    }

    $http_code        = wp_remote_retrieve_response_code( $response );
    $body             = wp_remote_retrieve_body( $response );
    $response_headers = wp_remote_retrieve_headers( $response );

    // Convert headers object to plain array
    $headers_out = array();
    foreach ( $response_headers as $k => $v ) {
      $headers_out[ $k ] = $v;
    }

    wp_send_json_success( array(
      'http_code' => $http_code,
      'body'      => $body,
      'headers'   => $headers_out,
    ) );
  }

  // ── JS localize data ─────────────────────────────────────────────────────

  public static function getJsData(): array {
    return array(
      'ajax_url' => admin_url( 'admin-ajax.php' ),
      'nonce'    => wp_create_nonce( self::NONCE_KEY ),
      'site_url' => get_site_url(),
    );
  }

  // ── Helpers ───────────────────────────────────────────────────────────────

  /**
   * Check if a URL points to the same site (loopback).
   * Loopback requests on shared hosting often fail SSL verify.
   * We disable sslverify for loopback to allow WC REST API self-calls.
   */
  private function isLoopback( string $url ): bool {
    $site_host    = wp_parse_url( get_site_url(), PHP_URL_HOST );
    $request_host = wp_parse_url( $url,           PHP_URL_HOST );
    return $site_host && $request_host && ( $site_host === $request_host );
  }

}

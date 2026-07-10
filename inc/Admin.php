<?php
/**
 * Admin.php
 *
 * Core admin class: menus, enqueue, AJAX handlers, chart data.
 *
 * @package AnyApi
 */


namespace Anyapi;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin {

  public $plugin_path;
  public $plugin_url;
  public $admin_pages    = array();
  public $admin_subpages = array();
  public $settings       = array();
  public $sections       = array();
  public $fields         = array();

  // ── Hooks ──────────────────────────────────────────────────────────────────

  public function init() {

    $this->plugin_path = plugin_dir_path( dirname( __FILE__, 1 ) );
    $this->plugin_url  = plugin_dir_url( dirname( __FILE__, 1 ) );

    if ( ! empty( $this->admin_pages ) || ! empty( $this->admin_subpages ) ) {
      add_action( 'admin_menu', array( $this, 'addAdminMenu' ) );
    }
    if ( ! empty( $this->settings ) ) {
      add_action( 'admin_init', array( $this, 'registerCustomFields' ) );
    }

    add_action( 'admin_init',            array( $this, 'anyapiInit' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueueFiles' ) );

    add_action( 'wp_ajax_anyapi_save_general_settings', array( $this, 'ajaxSaveGeneralSettings' ) );
    add_action( 'wp_ajax_anyapi_dismiss_review',        array( $this, 'ajaxDismissReview' ) );

    add_action( 'anyapi_throttled_fire', array( $this, 'throttledFire' ), 10, 2 );

    add_filter(
      'plugin_action_links_' . plugin_basename( dirname( __FILE__, 2 ) ) . '/anyapi.php',
      array( $this, 'settingsLinks' ), 10, 2
    );
    add_filter( 'plugin_row_meta', array( $this, 'customLinks' ), 10, 2 );

  }

  // ── Admin Menu ─────────────────────────────────────────────────────────────

  public function addPages( array $pages ) {
    $this->admin_pages = $pages;
    return $this;
  }

  public function withSubPage( string $title = null ) {
    if ( empty( $this->admin_pages ) ) return $this;
    $p = $this->admin_pages[0];
    $this->admin_subpages = array( array(
      'parent_slug' => $p['menu_slug'],
      'page_title'  => $p['page_title'],
      'menu_title'  => $title ?: $p['menu_title'],
      'capability'  => $p['capability'],
      'menu_slug'   => $p['menu_slug'],
      'callback'    => $p['callback'],
    ) );
    return $this;
  }

  public function addSubPages( array $pages ) {
    $this->admin_subpages = array_merge( $this->admin_subpages, $pages );
    return $this;
  }

  public function addAdminMenu() {
    foreach ( $this->admin_pages as $p ) {
      add_menu_page( $p['page_title'], $p['menu_title'], $p['capability'], $p['menu_slug'], $p['callback'], $p['icon_url'], $p['position'] );
    }
    foreach ( $this->admin_subpages as $p ) {
      add_submenu_page( $p['parent_slug'], $p['page_title'], $p['menu_title'], $p['capability'], $p['menu_slug'], $p['callback'] );
    }
  }

  // ── Settings Registration ──────────────────────────────────────────────────

  public function setSettings( array $settings ) { $this->settings = $settings; return $this; }
  public function setSections( array $sections ) { $this->sections = $sections; return $this; }
  public function setFields( array $fields )     { $this->fields   = $fields;   return $this; }

  public function registerCustomFields() {
    foreach ( $this->settings as $s ) {
      register_setting( $s['anyapi_option_group'], $s['anyapi_option_name'], array(
        'type'              => 'array',
        'sanitize_callback' => $s['callback'] ?? 'sanitize_text_field',
      ) );
    }
    foreach ( $this->sections as $s ) {
      add_settings_section( $s['id'], $s['title'], $s['callback'] ?? '', $s['page'], $s['args'] ?? '' );
    }
    foreach ( $this->fields as $f ) {
      add_settings_field( $f['id'], $f['title'], $f['callback'] ?? '', $f['page'], $f['section'], $f['args'] ?? '' );
    }
  }

  // ── Plugin Init & DB ───────────────────────────────────────────────────────

  public function anyapiInit() {

    $this->updateStatus();

    if ( ! get_option( 'anyapi_activated_time' ) ) return;

    global $wpdb;
    $table          = $wpdb->prefix . 'anyapi_log_anyapi';
    $charsetCollate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
      `id`        mediumint(9)  NOT NULL AUTO_INCREMENT,
      `order_id`  bigint(20)    NOT NULL,
      `http_code` int(3)        NOT NULL,
      `status`    text          NOT NULL,
      `trigger`   varchar(100)  NOT NULL,
      `method`    varchar(10)   NOT NULL DEFAULT 'POST',
      `api_url`   text          NOT NULL,
      `payload`   longtext      NOT NULL,
      `latency`   int           NULL,
      `timestamp` datetime      DEFAULT CURRENT_TIMESTAMP NOT NULL,
      PRIMARY KEY (`id`)
    ) {$charsetCollate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

  }

  // ── Review Banner Status ───────────────────────────────────────────────────

  public function updateStatus() {

    $logCount      = self::getLog( 'count' );
    $activatedTime = get_option( 'anyapi_activated_time' );

    if ( ! $activatedTime ) {
      $activatedTime = time();
      update_option( 'anyapi_activated_time', $activatedTime );
    }

    // Days used now counts from first successful call, not activation
    $firstSuccessDays = self::getLog( 'first_success_days' );
    $daysUsed = ( $firstSuccessDays === null ) ? 0 : (int) $firstSuccessDays;

    $dismissed   = get_option( 'anyapi_review_dismissed' );
    $dismissTime = get_option( 'anyapi_review_dismiss_time' );
    $reviewed    = get_option( 'anyapi_review_given' );

    // Gate on recent success and a healthy last-24h window, not cumulative log count
    $recentSuccess  = (int) self::getLog( 'recent_success' );
    $last24hTotal   = (int) self::getLog( 'last24h_total' );
    $last24hSuccess = (int) self::getLog( 'last24h_success' );
    $last24hOk      = ( $last24hTotal === 0 || $last24hSuccess > 0 );

    $shouldShowBanner = (
      $recentSuccess >= 3 && $daysUsed >= 7 &&
      $last24hOk &&
      ! $dismissed && ! $reviewed &&
      ( ! $dismissTime || time() > $dismissTime )
    );

    update_option( 'anyapi_version',   ANYAPI_VERSION );
    update_option( 'anyapi_log_count', $logCount );
    update_option( 'anyapi_review_conditions', array(
      'log_count'      => $logCount,
      'recent_success' => $recentSuccess,
      'days_used'      => $daysUsed,
      'should_show'    => $shouldShowBanner,
    ) );

    if ( $shouldShowBanner ) {
      update_option( 'anyapi_review_dismissed', false );
    }

  }

  public static function activatedDay(): int {
    $t = get_option( 'anyapi_activated_time' );
    return $t ? (int) floor( ( time() - $t ) / DAY_IN_SECONDS ) : 0;
  }

  public static function getLogCount() {
    return get_option( 'anyapi_log_count' );
  }

  // Read the precomputed condition instead of recomputing, single source of truth
  public static function shouldShowBanner(): bool {
    $cond = get_option( 'anyapi_review_conditions' );
    return is_array( $cond ) && ! empty( $cond['should_show'] );
  }

  // ── Enqueue ────────────────────────────────────────────────────────────────

  public function enqueueFiles( $hook ) {

    if ( strpos( $hook, 'anyapi' ) === false ) return;

    wp_enqueue_style( 'pluginstyle', $this->plugin_url . 'assets/css/style.css', array(), ANYAPI_ASSETS );

    // Scripts always loaded on AnyAPI pages
    foreach ( array(
      'anyapi-dashboard' => 'assets/js/dashboard.min.js',
      'anyapi-order'     => 'assets/js/order-api.min.js',
      'settings'         => 'assets/js/settings.min.js',
      'settings-apikey'  => 'assets/js/apikey.min.js',
      'restapi'          => 'assets/js/rest-api.min.js',
    ) as $handle => $path ) {
      wp_enqueue_script( $handle, $this->plugin_url . $path, array(), ANYAPI_ASSETS, true );
    }

    // Localize JS data
    wp_localize_script( 'anyapi-order',    'anyapiOrder',    Controller\OrderIntegrationHandler::getJsData() );
    wp_localize_script( 'settings-apikey', 'anyapiApiKey',   Controller\ApiKeyHandler::getJsData() );
    wp_localize_script( 'restapi',         'anyapiRestTool', Controller\RestApiHandler::getJsData() );

    wp_localize_script( 'settings', 'anyapiSettings', array(
      'ajax_url' => admin_url( 'admin-ajax.php' ),
      'nonce'    => wp_create_nonce( 'anyapi_general_settings' ),
      'i18n'     => array(
        'saved'    => __( 'Settings saved.', 'anyapi' ),
        'save_btn' => __( 'Save Settings', 'anyapi' ),
      ),
    ) );

    wp_localize_script( 'anyapi-dashboard', 'anyapiDashboard', array(
      'ajax_url'    => admin_url( 'admin-ajax.php' ),
      'nonce'       => wp_create_nonce( 'anyapi_logs_nonce' ),
      'plan'        => PlanHelper::currentPlan(),
      'upgrade_url' => admin_url( 'admin.php?page=anyapi_settings#plan' ),
      'i18n'        => array(
        'no_logs'       => __( 'No API logs found.', 'anyapi' ),
        'error'         => __( 'Failed to load logs. Please try again.', 'anyapi' ),
        'loading'       => __( 'Loading logs…', 'anyapi' ),
        'prev'          => __( 'Previous', 'anyapi' ),
        'next'          => __( 'Next', 'anyapi' ),
        'search_locked' => __( 'Log search requires Lite — $79/yr', 'anyapi' ),
        'upgrade_cta'   => __( 'Upgrade to Lite →', 'anyapi' ),
      ),
    ) );

    wp_localize_script( 'anyapi-dashboard', 'anyapiReview', array(
      'ajax_url'    => admin_url( 'admin-ajax.php' ),
      'nonce'       => wp_create_nonce( 'anyapi_review_nonce' ),
      'show_banner' => self::shouldShowBanner(),
      'review_url'  => 'https://wordpress.org/support/plugin/anyapi/reviews/#new-post',
      'i18n'        => array(
        'thanks'    => __( 'Thank you for support!', 'anyapi' ),
        'dismissed' => __( 'Got it! You can re-enable this in settings anytime.', 'anyapi' ),
      ),
    ) );

  }

  // ── AJAX Handlers ──────────────────────────────────────────────────────────

  public function ajaxDismissReview(): void {
    check_ajax_referer( 'anyapi_review_nonce', 'nonce' );
    if ( isset( $_POST['reviewed'] ) && $_POST['reviewed'] == 1 ) {
      update_option( 'anyapi_review_dismissed', true );
      update_option( 'anyapi_review_given',     true );
    } else {
      update_option( 'anyapi_review_dismissed',   true );
      update_option( 'anyapi_review_dismiss_time', time() + ( 14 * DAY_IN_SECONDS ) );
    }
    wp_send_json_success();
  }

  public function ajaxSaveGeneralSettings(): void {
    check_ajax_referer( 'anyapi_general_settings', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
    }
    update_option( 'anyapi_debug_mode',      ( isset( $_POST['anyapi_debug_mode'] )      && $_POST['anyapi_debug_mode']      === '1' ) ? '1' : '0' );
    update_option( 'anyapi_clean_uninstall', ( isset( $_POST['anyapi_clean_uninstall'] ) && $_POST['anyapi_clean_uninstall'] === '1' ) ? '1' : '0' );
    wp_send_json_success( array( 'message' => __( 'Settings saved.', 'anyapi' ) ) );
  }

  // ── DB Helpers ─────────────────────────────────────────────────────────────

  public static function getLog( string $cache ) {
    global $wpdb;
    $table   = $wpdb->prefix . 'anyapi_log_anyapi';
    $cacheKey = 'anyapi_log_' . $cache;
    $cached  = wp_cache_get( $cacheKey, 'anyapi_log_cache' );
    if ( false !== $cached ) return $cached;

    $result = match ( $cache ) {
      // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
      'count'     => $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) ),
      'todaycall' => $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE DATE(timestamp) = CURDATE()', $table ) ),
      'rate'      => $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE DATE(timestamp) = CURDATE() AND http_code >= %d AND http_code < %d', $table, 200, 300 ) ),
      'latency'   => $wpdb->get_var( $wpdb->prepare( 'SELECT AVG(latency) FROM %i WHERE DATE(timestamp) = CURDATE() AND latency IS NOT NULL', $table ) ),
      'endpoints' => $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(DISTINCT api_url) FROM %i', $table ) ),
      'ytdcall'   => $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE DATE(timestamp) = CURDATE() - INTERVAL 1 DAY', $table ) ),
      // Success-based keys to gate review banner on recent healthy usage
      'recent_success'     => $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND http_code >= %d AND http_code < %d', $table, 200, 300 ) ),
      'first_success_days' => $wpdb->get_var( $wpdb->prepare( 'SELECT DATEDIFF(NOW(), MIN(timestamp)) FROM %i WHERE http_code >= %d AND http_code < %d', $table, 200, 300 ) ),
      'last24h_total'      => $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)', $table ) ),
      'last24h_success'    => $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND http_code >= %d AND http_code < %d', $table, 200, 300 ) ),
      // phpcs:enable
      default     => 0,
    };

    wp_cache_set( $cacheKey, $result, 'anyapi_log_cache', 300 );
    return $result;
  }

  public static function getLogInfo( string $cache, string $where, array $params, int $perPage, int $offset ) {
    global $wpdb;
    $table   = $wpdb->prefix . 'anyapi_log_anyapi';
    $cacheKey = 'anyapi_log_' . md5( $cache . serialize( $params ) . $where . $perPage . $offset );
    $cached  = wp_cache_get( $cacheKey, 'anyapi_log_cache' );
    if ( false !== $cached ) return $cached;

    if ( $cache === 'total' ) {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
      $result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where}", ...$params ) );
    } elseif ( $cache === 'logs' ) {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
      $result = $wpdb->get_results(
        $wpdb->prepare( "SELECT id, order_id, http_code, status, `trigger`, method, api_url, payload, latency, timestamp FROM {$table} {$where} ORDER BY timestamp DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
          ...array_merge( $params, array( $perPage, $offset ) )
        ), ARRAY_A
      );
    } else {
      $result = null;
    }

    wp_cache_set( $cacheKey, $result, 'anyapi_log_cache', 300 );
    return $result;
  }

  public static function getRecentLogs( int $limit = 10 ): array {
    global $wpdb;
    $table = $wpdb->prefix . 'anyapi_log_anyapi';
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
      return array();
    }
    return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
      $wpdb->prepare( 'SELECT id, order_id, http_code, status, `trigger`, method, api_url, latency, timestamp FROM %i ORDER BY timestamp DESC LIMIT %d', $table, $limit ),
      ARRAY_A
    ) ?: array();
  }

// ── Chart Data Methods ────────────────────────────────────────────────────

  /**
   * Returns call counts for the past $days days, keyed by date string Y-m-d.
   * Used by Dashboard chartCard() — PHP SVG bar chart.
   *
   * @param  int   $days  Number of days (default 7)
   * @return array<string, int>  [ '2026-03-04' => 12, '2026-03-05' => 0, … ]
   */
  public static function getWeeklyCallData( int $days = 7 ): array {
    global $wpdb;
    $table = $wpdb->prefix . 'anyapi_log_anyapi';

    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
      return array();
    }

    // Build zeroed date map for the past $days days (today inclusive)
    $map = array();
    for ( $i = $days - 1; $i >= 0; $i-- ) {
      $map[ gmdate( 'Y-m-d', strtotime( "-{$i} days" ) ) ] = 0;
    }

    $rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
      $wpdb->prepare(
        'SELECT DATE(timestamp) AS day, COUNT(*) AS cnt
           FROM %i
          WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
          GROUP BY day
          ORDER BY day ASC',
        $table,
        $days - 1
      ),
      ARRAY_A
    ) ?: array();

    foreach ( $rows as $row ) {
      if ( isset( $map[ $row['day'] ] ) ) {
        $map[ $row['day'] ] = (int) $row['cnt'];
      }
    }

    return $map;
  }

  /**
   * Returns error code distribution for all-time (or last N days).
   * Used by Dashboard chartCard() donut SVG.
   *
   * @return array{ '2xx': int, '4xx': int, '5xx': int, 'other': int, 'total': int }
   */
  public static function getErrorDistribution(): array {
    global $wpdb;
    $table = $wpdb->prefix . 'anyapi_log_anyapi';

    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
      return array( '2xx' => 0, '4xx' => 0, '5xx' => 0, 'other' => 0, 'total' => 0 );
    }

    $row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
      $wpdb->prepare(
        'SELECT
           COUNT(*)                                                                                       AS total,
           SUM( CASE WHEN http_code >= 200 AND http_code < 300 THEN 1 ELSE 0 END )                      AS ok,
           SUM( CASE WHEN http_code >= 400 AND http_code < 500 THEN 1 ELSE 0 END )                      AS c4xx,
           SUM( CASE WHEN http_code >= 500                     THEN 1 ELSE 0 END )                      AS c5xx,
           SUM( CASE WHEN http_code < 200 OR ( http_code >= 300 AND http_code < 400 ) THEN 1 ELSE 0 END ) AS other
         FROM %i',
        $table
      ),
      ARRAY_A
    ) ?: array( 'total' => 0, 'ok' => 0, 'c4xx' => 0, 'c5xx' => 0, 'other' => 0 );

    return array(
      '2xx'   => (int) $row['ok'],
      '4xx'   => (int) $row['c4xx'],
      '5xx'   => (int) $row['c5xx'],
      'other' => (int) $row['other'],
      'total' => (int) $row['total'],
    );
  }

  // ── Version Check ──────────────────────────────────────────────────────────

  public static function pluginVersionCheck( string $currentVer ) {
    $latest = self::getLatestVersion();
    if ( ! $latest ) return null;
    return version_compare( $currentVer, $latest['version'], '<' );
  }

  public static function getLatestVersion() {
    $cacheKey = 'anyapi_latest_version_wporg';
    $cached   = get_transient( $cacheKey );
    if ( false !== $cached ) return $cached;

    $response = wp_remote_get( 'https://plugins.svn.wordpress.org/anyapi/trunk/readme.txt', array( 'timeout' => 10, 'sslverify' => true ) );
    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) return false;

    $body = wp_remote_retrieve_body( $response );
    if ( ! preg_match( '/^Stable tag:\s*([0-9.]+)/mi', $body, $m ) ) return false;
    $v = trim( $m[1] );

    $date = ''; $changes = array();
    if ( preg_match( '/=\s*' . preg_quote( $v, '/' ) . '\s*\(([\d-]+)\)\s*=\s*\n(.*?)(?:\n\n=|$)/s', $body, $m ) ) {
      $date  = $m[1];
      foreach ( array_filter( array_map( 'trim', explode( "\n", trim( $m[2] ) ) ) ) as $line ) {
        $line = preg_replace( '/^\*\s*/', '', $line );
        if ( $line ) $changes[] = $line;
      }
    }

    $result = array( 'version' => $v, 'date' => $date ?: gmdate( 'Y-m-d' ), 'changes' => $changes ?: array( 'Changelog not found' ) );
    set_transient( $cacheKey, $result, 12 * HOUR_IN_SECONDS );
    return $result;
  }

  public static function pluginUpdateUrl(): string {
    $dir = 'anyapi/anyapi.php';
    return add_query_arg( 'return', urlencode( admin_url( 'admin.php?page=anyapi-settings' ) ),
      wp_nonce_url( admin_url( 'update.php?action=upgrade-plugin&plugin=' . $dir ), 'upgrade-plugin_' . $dir )
    );
  }

  // ── Plan / Plugin Detection ────────────────────────────────────────────────

  public static function isProActivate(): bool {
    $active = get_option( 'active_plugins', array() );
    if ( in_array( 'anyapi-lite/anyapi-lite.php', $active, true ) ) {
      return true;
    }
    if ( in_array( 'anyapi-plus/anyapi-plus.php', $active, true ) ) {
      return true;
    }
    return false;
  }

  // ── Plugin Links ───────────────────────────────────────────────────────────

  public function settingsLinks( $links ) {
    $links[] = '<a href="admin.php?page=anyapi">Settings</a>';
    if ( ! self::isProActivate() ) {
      $links[] = '<a style="color:#f34a4a;" href="https://anyapiplugin.com/pricing">Get AnyAPI Lite</a>';
    }
    return $links;
  }

  public function customLinks( $links ) {
    foreach ( $links as $key ) {
      if ( strpos( $key, 'AnyAPI' ) !== false ) {
        $links[] = '<a style="color:#f34a4a;" href="https://anyapiplugin.com/documentation">Docs</a>';
        break;
      }
    }
    return $links;
  }

  // ── Page Callbacks ─────────────────────────────────────────────────────────

  public function pageDashboard()  { return require_once "{$this->plugin_path}/templates/dashboard.php"; }
  public function pageSettings()   { return require_once "{$this->plugin_path}/templates/settings.php"; }
  public function pageApiKey()     { return require_once "{$this->plugin_path}/templates/apikey.php"; }
  public function pageOrderApi()   { return require_once "{$this->plugin_path}/templates/order-api.php"; }
  public function pageRestApi()    { return require_once "{$this->plugin_path}/templates/rest-api.php"; }
  public function pageApiLog()     { return require_once "{$this->plugin_path}/templates/api-log.php"; }

  // ── Lifecycle ──────────────────────────────────────────────────────────────

  public static function activate() {
    flush_rewrite_rules();
    update_option( 'anyapi_wc_orderapi_integration', true );
    update_option( 'anyapi_wc_restapi_function',     true );
    update_option( 'anyapi_wc_apilog_function',      true );
    foreach ( array( 'anyapi_wc_apikey', 'anyapi_wc_orderapi', 'anyapi_wc_restapi' ) as $opt ) {
      if ( ! get_option( $opt ) ) update_option( $opt, array() );
    }
  }

  public static function deactivate() {
    flush_rewrite_rules();
  }

  // ── WP Cron ────────────────────────────────────────────────────────────────

  public function throttledFire( int $order_id, string $integration_id ): void {
    $integrations = get_option( Controller\OrderIntegrationHandler::OPTION_KEY, array() );
    if ( isset( $integrations[ $integration_id ] ) ) {
      do_action( 'anyapi_fire_integration', $order_id, $integrations[ $integration_id ] );
    }
  }

  // ── Service Container ──────────────────────────────────────────────────────

  public static function getServices(): array {
    return array(
      Anyapi::class,
      Controller\ApiKeyHandler::class,
      Controller\RestApiHandler::class,
      Controller\OrderIntegrations::class,
      Controller\OrderIntegrationHandler::class,
    );
  }

  public static function registerServices(): void {
    foreach ( self::getServices() as $class ) {
      $service = new $class();
      if ( method_exists( $service, 'init' ) ) $service->init();
    }
  }

}
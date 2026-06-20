<?php
/**
 * Anyapi.php
 *
 * Plugin bootstrap: registers menus, sub-pages, and service instances.
 *
 * @package AnyApi
 */

namespace Anyapi;

if ( ! defined( 'ABSPATH' ) ) exit;

use Anyapi\Views\Dashboard;

class Anyapi {

  const MENU_SLUG = 'anyapi';

  // ── Instance vars ─────────────────────────────────────────────────────────
  // Only Admin (settings framework) and Dashboard (page callbacks) are needed.
  // OrderApi / RestApi / ApiLog Views never existed as Anyapi\Views classes.

  public $settings;
  public $dashboard;

  public $pages    = array();
  public $subpages = array();
  public $option;

  // =========================================================================
  // init
  // =========================================================================

  public function init() {

    $this->settings  = new Admin();
    $this->dashboard = new Dashboard();

    $this->setPages();
    $this->setSubPages();

    $this->settings->addPages( $this->pages )->withSubPage( 'Dashboard' )->init();
    $this->settings->addSubPages( $this->subpages )->init();

    $this->option = self::getOption();

  }

  // =========================================================================
  // Menu pages
  // =========================================================================

  /**
   * Register the top-level AnyAPI menu page (Dashboard).
   */
  public function setPages() {

    $this->pages = array(
      array(
        'page_title' => 'AnyAPI - REST API integration for WooCommerce',
        'menu_title' => 'AnyAPI',
        'capability' => 'manage_options',
        'menu_slug'  => self::MENU_SLUG,
        'callback'   => array( $this->settings, 'pageDashboard' ),
        'icon_url'   => 'dashicons-admin-plugins',
        'position'   => 150,
      ),
    );

  }

  /**
   * Register sub-menu pages.
   */
  public function setSubPages() {

    $this->subpages = array();

    // ── Always-visible pages ─────────────────────────────────────────────

    // Settings (plan overview + general settings)
    $this->subpages[] = array(
      'parent_slug' => self::MENU_SLUG,
      'page_title'  => 'AnyAPI - Settings',
      'menu_title'  => 'Settings',
      'capability'  => 'manage_options',
      'menu_slug'   => self::MENU_SLUG . '_settings',
      'callback'    => array( $this->settings, 'pageSettings' ),
    );

    // API Keys
    $this->subpages[] = array(
      'parent_slug' => self::MENU_SLUG,
      'page_title'  => 'AnyAPI - API Keys',
      'menu_title'  => 'API Keys',
      'capability'  => 'manage_options',
      'menu_slug'   => self::MENU_SLUG . '_apikey',
      'callback'    => array( $this->settings, 'pageApiKey' ),
    );

    // ── Feature pages ────────────────────────────────────────────────────

    $this->subpages[] = array(
      'parent_slug' => self::MENU_SLUG,
      'page_title'  => 'AnyAPI - Order API Integration',
      'menu_title'  => 'Order API',
      'capability'  => 'manage_options',
      'menu_slug'   => self::MENU_SLUG . '_orderapi',
      'callback'    => array( $this->settings, 'pageOrderApi' ),
    );

    $this->subpages[] = array(
      'parent_slug' => self::MENU_SLUG,
      'page_title'  => 'AnyAPI - WC REST API Tester',
      'menu_title'  => 'REST API',
      'capability'  => 'manage_options',
      'menu_slug'   => self::MENU_SLUG . '_restapi',
      'callback'    => array( $this->settings, 'pageRestApi' ),
    );

    $this->subpages[] = array(
      'parent_slug' => self::MENU_SLUG,
      'page_title'  => 'AnyAPI - API Logs',
      'menu_title'  => 'API Log',
      'capability'  => 'manage_options',
      'menu_slug'   => self::MENU_SLUG . '_apilog',
      'callback'    => array( $this->settings, 'pageApiLog' ),
    );

    // ── Upgrade link (Starter / Free only) ───────────────────────────────

    if ( ! Admin::isProActivate() ) {
      $this->subpages[] = array(
        'parent_slug' => self::MENU_SLUG,
        'page_title'  => 'AnyAPI Pro',
        'menu_title'  => sprintf(
          '<span class="tag small tag--new">%s</span>',
          esc_html__( 'Upgrade', 'anyapi' )
        ),
        'capability'  => 'manage_options',
        'menu_slug'   => 'https://anyapiplugin.com/pricing',
        'callback'    => array(),
      );
    }

  }

  // =========================================================================
  // Option key map
  // =========================================================================

  /**
   * Returns the canonical option-key alias map.
   *
   * @return array<string,string>
   */
  public static function getOption(): array {
    return array(
      'anyApiVersion'  => 'anyapi_version',
      'anyApiKey'      => 'anyapi_wc_apikey',
      'anyApiOrderApi' => 'anyapi_wc_orderapi',
      'anyApiRestApi'  => 'anyapi_wc_restapi',
    );
  }

  // =========================================================================
  // Asset helpers
  // =========================================================================

  /**
   * Returns the absolute URL to a plugin image asset.
   *
   * @param  string $args  Filename without extension (e.g. 'lite', 'tips').
   * @return string
   */
  public static function getImages( string $args ): string {
    return plugin_dir_url( dirname( __FILE__, 1 ) ) . 'assets/images/' . $args . '.png';
  }

}
<?php
/**
 *
 * @package           AnyApi
 * @author            JC
 * @license           GPL-2.0-or-later
 * 
 * Plugin Name:       AnyAPI
 * Plugin URI:        https://www.anyapiplugin.com
 * Description:       No-Code WooCommerce REST API Integration. Connect Orders to any APIs with Automations, JSON Filter, API logs, and Easy Setup in Minutes.
 * Version:           2.0.3
 * Author:            JC
 * Author URI:        https://www.anyapiplugin.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       anyapi
 *
 */

defined( 'ABSPATH' ) or exit;

if ( ! function_exists( 'add_action' ) ) { exit; }

define( 'ANYAPI_RELEASE_DATE', '2026-07-10' );
define( 'ANYAPI_VERSION', '2.0.3' );
define( 'ANYAPI_ASSETS', '2.0.8' );

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
  require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

if ( ! function_exists( 'anyapi_utm_url' ) ) {
  // Append Starter UTM params to an anyapiplugin.com URL.
  function anyapi_utm_url( string $base, string $medium, string $campaign ): string {
    return add_query_arg( array(
      'utm_source'   => 'starter',
      'utm_medium'   => $medium,
      'utm_campaign' => $campaign,
    ), $base );
  }
}

function anyapiActivate() {
  Anyapi\Admin::activate();
}
register_activation_hook( __FILE__, 'anyapiActivate' );
function anyapiDeactivate() {
  Anyapi\Admin::deactivate();
}
register_deactivation_hook( __FILE__, 'anyapiDeactivate' );

if ( class_exists( 'Anyapi\\Admin' ) ) {
  // Must run after plugins_loaded so WooCommerce hooks are available.
  add_action( 'plugins_loaded', array( 'Anyapi\\Admin', 'registerServices' ) );
}
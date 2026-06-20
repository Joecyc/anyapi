<?php
/**
 * @package AnyApi
 */

namespace Anyapi\Views;

class ApiLog {

  public function apiLog( $args ) {

    global $wpdb;
    if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $args[ 'table' ] ) ) {
      return;
    }

    $results      = wp_cache_get( 'anyapi_logs', 'anyapi_cache' );
    $apiLogTable  = $wpdb->prefix . $args[ 'table' ];

    if ( false === $results ) {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table query required for API logs.
      $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i ORDER BY `timestamp` DESC" , $apiLogTable ) ) ;
      wp_cache_set( 'anyapi_logs', $results, 'anyapi_cache', 300 );
    }

    echo '<section><div class="">'; 
    echo '<pre class="prettyprint scroll pretty sendapi">';
    if ( $results ) {
      foreach ( $results as $row ) {
        echo 'Order ID : '     . esc_html( $row->order_id ) . "\n";
        echo 'HTTP Code : '    . esc_html( $row->http_code ). "\n";
        echo 'Status : '       . esc_html( $row->status ) . "\n";
        echo 'Action : '       . esc_html( $row->trigger ) . "\n";
        echo 'API URL : '      . esc_html( $row->api_url ) . "\n";
        echo 'Payload : '      . esc_html( json_encode( json_decode( $row->payload ), JSON_PRETTY_PRINT ) ) . "\n";
        echo 'Date Created : ' . esc_html( $row->timestamp ) . "\n";
        echo "------------------------------------------------------------------------------------------------\n";
      }
    } else {
      echo 'No logs available.';
    }
    echo '</pre>';
    echo '</div></section>';

  }

}
<?php
/**
 * @package AnyApi
 */

namespace Anyapi\Views;

use Anyapi\Anyapi;

class RestApi {

  private $setId;
  private $getId;

  public function setupRestApiTab( $args ) {

    $options    = get_option( $args[ 'option' ], array() );
    $image      = Anyapi::getImages('tips');
    $fields     = self::fields();

    echo '<hr>';

    foreach ( $fields[ 'dropDown' ] as $key => $value ) {

      $tooltips     = $value[ 'tooltip' ];
      $label        = $value[ 'title' ];
      $input        = $value[ 'option' ];
      $name         = $key;
      $inputValue   = '';

      echo '<div class="api-input-form">';
      echo '<label for="' . esc_attr( $label ) . '" class="api-label">' . esc_attr( $label );
      echo '<span class="image-wrapper">';
      echo '<img src="' . esc_url( $image ) . '" alt="" width="16" height="16">';
      echo '<span class="image-info">'. esc_attr( $tooltips ) .'</span>';
      echo '</span></label>';

      echo '<select class="dropdown" id="' . esc_attr( $name ) . '" name="' . esc_attr( $args[ 'option' ] ) . '[' . esc_attr( $name ) . ']">';

      $type = '';
      foreach ( $options as $option ) {
        $type = $option[ 'type' ];
      }

      foreach ( $input as $key => $value ) {
        echo '<option value="' . esc_attr( $key ) . '" ' . ( $key == $type ? ' selected' : '' ) . '
        >' . esc_attr( $value ) . '</option>';
      }
      echo '</select>';
      echo '</div>';

    }

    echo '<input type="hidden" name="apitools_nonce" value="' . esc_attr( wp_create_nonce( 'apitools_nonce_action' ) ). '">';

    $apiBody = '';
    foreach ( $options as $option ) {
      $apiBody = $option[ 'body' ];
    }

    $textAreaValue = $apiBody;
    echo '<div class="api-input-form">';

    echo '<label id="bodyLabel" hidden >API BODY';
    echo '<span class="image-wrapper">';
    echo '<img src="' . esc_url( $image ) . '" alt="" width="16" height="16">';
    echo '<span class="image-info">'. esc_attr( $tooltips ) .'</span>';
    echo '</span></label>';

    echo '<textarea id="rest-api-body-text" name="' . esc_attr( $args[ 'option' ] ) . '[body]" class="" placeholder="Please input API BODY" rows="6" hidden>' . esc_attr( $textAreaValue ) . '</textarea></div>';

    foreach ( $fields[ 'text' ] as $key => $value ) {

      $tooltips     = $value['tooltip'];
      $label        = $value['title'];
      $name         = $key;
      $placeholder  = $value['placeholder'];
      $inputValue   = '';

      $url          = '';
      $id           = '';
      $key          = '';
      $secret       = '';
  
      foreach ( $options as $option ) {
        $url      = $option[ 'url' ];
        $id       = $option[ 'id' ];
        $key      = $option[ 'key' ];
        $secret   = $option[ 'secret' ];
      }

      if ( isset( $_POST[ "edit_post" ] ) ) {

        if ( isset( $_POST[ 'apitools_nonce' ] ) ) {
          $apiToolsNonce = sanitize_text_field( wp_unslash( $_POST[ 'apitools_nonce' ] ) );
          if ( ! wp_verify_nonce( $apiToolsNonce, 'apitools_nonce_action' ) ) {
            die( 'Nonce verification failed' );
          }
        }
        $editPostKey  = sanitize_text_field( wp_unslash( $_POST[ "edit_post" ] ) );
        $input        = get_option( $args[ 'option' ] );
        $inputValue   = $input[ $editPostKey ][ $name ];
        $this->getId  = $input[ $editPostKey ][ 'url' ];

      } else {

        if ( $name === 'url' ) {
          $this->setId  = $url;
          $trimmedUrl   = str_replace( 'https://', '', $url );
          $trimmedUrl   = strtok( $trimmedUrl, '/' );
          $inputValue   = $trimmedUrl;
        } elseif ( $name === 'id' ) { 
          $inputValue = $id;
        } elseif ( $name === 'key' ) {
          $inputValue = $key;
        } elseif ( $name === 'secret' ) {
          $inputValue = $secret;
        }

      }

      echo '<div class="api-input-form">';
      echo '<label for="' . esc_attr( $label ) . '" class="api-label">' . esc_attr( $label );
      echo '<span class="image-wrapper">';
      echo '<img src="' . esc_url( $image ) . '" alt="" width="16" height="16">';
      echo '<span class="image-info">'. esc_attr( $tooltips ) .'</span>';
      echo '</span></label>';

      echo '<input type="text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $args[ 'option' ] ) . '[' . esc_attr( $name ) . ']" value="' . esc_attr( $inputValue ) . '" placeholder="' . esc_attr( $placeholder ) . '" required>
      </div>';

    }
   
  }

  public function responseJson( $args ) {
    echo '<section><div class="">';
    echo '<pre class="prettyprint scroll pretty sendapi">';
    echo wp_json_encode( Anyapi::restApiRespond(), JSON_PRETTY_PRINT );
    echo '</pre>';
    echo '</div></section>';
  }

  public static function fields() {
    return array(
      'dropDown'  => array(
        'type'      => array(
          'title'   => 'API Type',
          'tooltip' => 'Select the WooCommerce API allows you to  create, view, update, and delete individual',
          'option'  => array(
            'orders'    => 'Orders',
            'customers' => 'Customers',
            'products'  => 'Products',
          )
        ),
        'method'    => array(
          'title'   => 'API Methods',
          'tooltip' => 'Select the API Methods',
          'option'  => array(
            'get'     => 'GET',
            'post'    => 'POST',
            'patch'   => 'PATCH',
            'delete'  => strval('DELETE'),
          )
        ),
      ),
      'text'      => array(
        'url'     => array(
          'title'       => 'Domain Name',
          'placeholder' => 'Enter domain name',
          'tooltip'     => 'URL that request WooCommerce API',
        ),
        'id'      => array(
          'title'       => 'Id',
          'placeholder' => 'Enter ID for WooCommerce API',
          'tooltip'     => 'Enter ID if specified request',
        ),
        'key'     => array(
          'title'       => 'Consumer Key',
          'placeholder' => 'Enter consumer key',
          'tooltip'     => 'WooCommerce REST API Consumer Key',
        ),
        'secret'  => array(
          'title'       => 'Consumer Secret', 
          'placeholder' => 'Enter consumer secret',
          'tooltip'     => 'WooCommerce REST API Consumer Secret',
        ),
      ),
    );
  }

}
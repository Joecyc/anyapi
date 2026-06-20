<?php
/**
 * @package AnyApi
 */

namespace Anyapi\Views;

use Anyapi\Anyapi;

class OrderApi {

  /**
   * Integration Step One
   */

  public function selectApiKey( $args ) {

    $image = Anyapi::getImages( 'tips' );
    echo '<div class="api-input-form">';
    echo '<div class="feature-tags"><span class="tag tag--cap"><b>Create a new API Automation</span></b></div>';
    echo '<div class="feature-tags">';
    echo "<span class=\"tag-key tag large tag--large url-tag\">Enter URL</span></div>";
    echo '<div class="api-input-form">';
    echo '<label for="apiurl" class="api-label">URL';
    echo '<img src="' . esc_url( $image ) . '" alt="" width="16" height="16">';
    echo '<span class="image-info">URL where the payload deliverd (JSON)</span>';
    echo '</span></label>';
    echo '<input type="text" id="api-url" name=""  placeholder="Please input API URL" value=""></div>';
    echo '</div>';

    $options = get_option( $args[ 'anyApiKey' ], array() );

    if ( ! empty( $options ) ) {
      echo '<div class="feature-tags">';
      echo "<span class=\"tag-key tag large tag--large key-tag\">Select API Key</span></div>";
    } else {
      echo '<div class="feature-tags">';
      echo "<span class=\"tag-key tag large tag--large\">API Key not found</span></div>";
      echo '<p class="feature-attribute">Please setup API Key in<a href="admin.php?page=anyapi_settings">Settings</a></p>';
    }

    foreach ( $options as $option ) {

      echo '<div class="feature-tags">';
      echo '<span class="tag-key tag tag--woo step-one-key available" id="selected[' . esc_attr( $option[ 'id' ] ) . ']">' . esc_attr( $option[ 'id' ] ) . '</span></div>';
      echo '<tr id ="tag-header"><th>ID</th><th>Authorization</th><th>Key / Token</th>';
      echo '<tr id="' . esc_attr( $option[ 'id' ] ) . '" style="display: none;">';
      echo "<td><span class=\"tag-id tag tag--woo\">" . esc_attr( $option[ 'id' ] ) . "</span></td>";

      if ( $option[ 'authType' ] === 'basic_auth' ) {
        echo "<td><span class=\"tag-key tag tag--woo\">Basic Auth</span></td>";
        echo "<td><span class=\"tag-secret tag tag--woo\">" . esc_attr( substr( $option[ 'basicKey' ], 0, 6 ) ) . '******' ."</span></td></tr>";
      }

      if ( $option[ 'authType' ] === 'bearer_token' ) {
        echo "<td><span class=\"tag-key tag tag--woo\">Bearer Token</span></td>";
        echo "<td><span class=\"tag-secret tag tag--woo\">" . esc_attr( substr( $option[ 'bearerToken' ], 0, 6 ) ) . '******' ."</span></td></tr>";
      }

      if ( $option[ 'authType' ] === 'oauth_1' ) {
        echo "<td><span class=\"tag-key tag tag--woo\">OAuth 1.0</span></td>";
        echo "<td><span class=\"tag-secret tag tag--woo\">" . esc_attr( substr( $option[ 'oauthKey' ], 0, 6 ) ) . '******' ."</span></td></tr>";
      }

    }

  }

  /**
   * Integration Step Two
   */

  public function selectApiAction() {

    echo '<hr><br>';
    $image    = Anyapi::getImages( 'tips' );
    $image2   = Anyapi::getImages( 'lite' );

    echo '<div class="api-input-form">';
    echo '<div class="feature-tags"><span class="tag tag--cap"><b>Create Order Actions that will trigger in this Automation</b></span></div>';
    echo '<div class="feature-tags">';
    echo "<span class=\"tag-key tag large tag--large action-tag\">Select Actions</span></div>";

    $fields = self::fields();
    echo '<div class="action-tags order-status">';

    foreach ( $fields[ 'action' ] as $key => $value ) {
      $class  = $value[ 'class' ];
      $title  = $value[ 'title'];
      $text   = $value[ 'text'];
      echo '<div class="tag-item">';
      echo '<div class="tag-wrapper">';
      echo '<span class="' . esc_attr( $class ) . ' tag tag--woo">' . esc_html( $title ) . '</span>';
      if ($key !== 'thankyou' && $key !== 'new order') {
        echo '<div class="image-container"><img src="' . esc_url($image2) . '" alt="" width="16" height="16"></div>';
      }
      echo '</div>';
      echo '<p class="tag-desc">'. esc_attr( $text ) .'</p>';
      echo '</div>';
    }
    echo '</div>';

    echo '<div class="api-input-form">';
    echo '<div class="feature-tags">';
    echo "<span class=\"tag-key tag large tag--large body-tag\">Enter API Body</span></div>";
    echo '</div>';

    echo '<div class="api-input-form">';
    echo '<label for="apibody" class="api-label">API BODY';
    echo '<span class="image-wrapper">';
    echo '<img src="' . esc_url( $image ) . '" alt="" width="16" height="16">';
    echo '<span class="image-info">Payload or data you send in the request body to the APIs</span>';
    echo '</span></label>';
    echo '<textarea id="api-body" name="" class="" placeholder="Please input API BODY" value="" rows="6"></textarea></div>';
    echo '</div>';

  }

  /**
   * Integration Step Three
   */

  public function selectApiModes() {

    echo '<hr><br>';
    echo '<div class="api-input-form">';
    echo '<div class="feature-tags"><span class="tag tag--cap"><b>Preview JSON</b></span></div>';
    echo '<div class="feature-tags">';
    echo "<span class=\"tag-key tag large tag--large select-filter-tag\">Select filter mode</span></div>";

    echo '<div class="feature-tags filter-mode">';
    echo '<span class="filter-tag-basic filter-mode tag tag--woo available">Basic</span>';
    echo '<span class="filter-tag-advance filter-mode tag tag--woo available">Advance</span>';
    echo '<span class="filter-tag-expert filter-mode tag tag--woo available">Expert</span>';
    echo '</div></div>';

    // Preview division
    echo '<div class="feature-tags">';
    echo "<span class=\"tag-key tag large tag--large preview-filter-tag\">Preview Json</span></div>";
    echo '<div class="feature-hidden json-pre" hidden>';
    echo '</div>';

    // Basic mode
    echo '<div class="filter-json-basic mode-pre json">';
    echo '<pre class="prettyprint scroll pretty api">';
    self::basicJsonSample();
    echo '</pre></div>';

  }

  public function selectApiFields() {

    $image2   = Anyapi::getImages( 'lite' );

    // Advance mode
    echo '<div class="filter-json-advance mode-pre json">';
    echo '<div class="adv-box-table-container"></div></div>';

    // Advance mode filter
    echo '<div class="filter-json-advance-sample mode-pre json">';

    echo '<div class="feature-tags order-properties">';
    echo '<span class="fields-line-items tag tag--order available">Line items fields</span>';
    echo '<span class="fields-product-id tag tag--fields available">Product ID</span>';
    echo '<span class="fields-product-name tag tag--fields available">Product name</span>';
    echo '<span class="fields-product-total tag tag--fields available">Total</span>';
    echo '</div>';

    echo '<div class="feature-tags order-properties">';
    echo '<a href="https://anyapiplugin.com/pricing"><span class="upgrade tag tag--woo available">Unlock All fields</span></a>';
    echo '<div class="image-container"><img src="' . esc_url( $image2 ) . '" alt="" width="16" height="16"></div>';
    echo '</div>';

    // Advance mode preview
    echo '<pre class="prettyprint scroll pretty advance prettyprinted" id="filter-output">';
    echo '</pre></div>';

    // Expert mode
    echo '<div class="filter-json-expert mode-pre json">';
    echo '<pre class="prettyprint scroll pretty api">';
    echo ' **** ';
    echo ' Stay Tune ! ';
    echo ' **** ';
    echo '</pre>';
    echo '</div>';

  }

  public function selectConfirmation() {

    echo '<hr><br>';
    self::apiKey();
    self::triggerAction();
    self::filterMode();
    self::preArea();

  }

  public function selectedSave( $args ) {

    $fields = self::fields();
    foreach ( $fields[ 'orderApi' ] as $key => $value ) {
      $name = $args[ 'anyApiOrderApi' ];
      $value = '';
      echo '<input type="hidden" class="final-' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '">';
    }

  }

  public function selectedRecord() {

    echo '<div class="api-input-form"><div class="feature-tags">';
    echo "<span class=\"tag-key tag tag--woo\">Submit API successfully</span>";
    echo '</div></div>';
    echo '<table class="tag-table api-submitted">';
    echo '<tr><th>API Key</th><th>Url</th><th>Trigger</th><th>Body</th><th>Mode</th><th>Json</th>';
    echo '<tr>';
    echo "<td><span class=\"api-id tag tag--woo\"></span></td>";
    echo "<td><span class=\"api-url tag medium tag--attr\"></span></td>";
    echo "<td><span class=\"api-trigger tag medium tag--large\"></span></td>";
    echo "<td><span class=\"api-body tag medium tag--large\"></span></td>";
    echo "<td><span class=\"api-mode tag tag--woo\"></span></td>";
    echo "<td><span class=\"api-json tag medium tag--woo\"></span></td>";
    echo '</tr>';
    echo '</table><br>';
    echo '<div class="api-input-form"><div class="feature-tags">';
    echo "<span class=\"tag-key tag tag--auth\">Check OrderAPI action in API Status >>></span>";
    echo '</div></div>';

  }

  public function selectedTable( $args ) {

    $options = get_option( $args[ 'anyApiOrderApi' ], array() );

    echo '<div class="api-input-form">';
    echo '<br><div class="feature-tags">';
    echo "<span class=\"tag-key tag tag--woo\">API Action</span>";
    echo '</div></div>';

    if ( ! empty( $options ) ) {
      echo '<table class="tag-table api-data">';
      echo '<tr><th>API Key</th><th>Authorization</th><th>Url</th><th>Trigger</th><th>Body</th><th>Mode</th><th>Json</th><th class="text-center">Status</th><th class="text-center">Action</th>';
    } else {
      echo '<div class="feature-tags"><span class="tag-key tag tag--attr">No Record</span></div>';
    }

    foreach ( $options as $option ) {

      echo '<tr id="' . esc_attr( $option[ 'id' ] ) . '">';
      echo "<td><span class=\"record-id tag tag--woo\">" . esc_attr( $option[ 'id' ] ) . "</span></td>";

      $optionIdTrimmed  = trim( $option[ 'id' ] );
      $optionsId        = get_option( $args[ 'anyApiKey' ], array() );
      $foundMatch       = false;

      foreach ( $optionsId as $optionId ) {
        if( $optionId[ 'id' ] === $optionIdTrimmed ) {
          echo "<td><span class=\"record-auth tag medium tag--auth\">" . esc_attr( $optionId[ 'authType' ] ) . "</span></td>";
          $foundMatch = true;
        }
      }

      if ( ! $foundMatch ) {
        echo "<td><span class=\"record-auth tag medium tag--attr selected\">Record Not Found</span></td>";
      }

      echo "<td><span class=\"record-url tag tag medium tag--attr\">" . esc_attr( $option[ 'url' ] ) . "</span></td>";
      echo "<td><span class=\"record-trigger tag medium tag--large\">" . esc_attr( $option[ 'trigger' ] ) . "</span></td>";
      echo "<td><span class=\"record-body tag medium tag--large\">" . esc_attr( $option[ 'body' ] ) . "</span></td>";
      echo "<td><span class=\"record-mode tag tag--woo\">" . esc_attr( $option[ 'mode' ] ) . "</span></td>";

      if ( $option[ 'mode' ] === 'Basic' || $option[ 'mode' ] === 'Expert' ) {
        echo "<td><span class=\"record-json tag tag medium tag--attr\">Original JSON will be sent</span></td>";
      } else {
        echo "<td><span class=\"record-json tag tag medium tag--attr\">" . esc_attr( $option[ 'json' ] ) . "</span></td>";
      }

      $checked = isset( $option[ $args[ 'value' ] ] ) ? ( $option[ $args[ 'value' ] ] ? true : false ) : false;

      echo "<td class=\"text-center\">";
      echo '<form method="post" action="options.php" class="inline-block">';
      echo '<div class="ui-toggle"><input type="checkbox" id="' . esc_attr( $args[ 'anyApiOrderApi' ] ) .'_' . esc_attr( $option[ 'id' ] ) . '" name="' . esc_attr( $args[ 'anyApiOrderApi' ] ) .'[' . esc_attr( $args[ 'value' ] ) . ']" value="1" class="" ' . ( esc_attr( $checked ) ? 'checked' : '' ) . '><label for="' . esc_attr( $args[ 'anyApiOrderApi' ] ) .'_' . esc_attr( $option[ 'id' ] ) . '"><div></div></label></div></td>';

      echo "<td class=\"text-center\">";
      foreach ( $option as $key => $value ) {
        if ( $key !== $args[ 'value' ] ) {
          echo '<input type="hidden" name="' . esc_attr( $args[ 'anyApiOrderApi' ] ) . '['. esc_attr( $key ) . ']" value="' . esc_attr( $option[ $key ] ) . '">';
        }
      }

      settings_fields( $args[ 'anyApiOrderApi' ] );
      echo '<div class="feature-tags">';
      echo '<input type="submit" name="submit" id="submit" class="btn medium btn--full" value="Save">';
      echo '</form> ';
  
      echo '<form method="post" action="options.php" class="inline-block">';
      settings_fields( $args[ 'anyApiOrderApi' ] );
      echo '<input type="hidden" name="remove" value="' . esc_attr( $option[ 'id' ] ) . '">';
      echo '<input type="submit" name="submit" id="submit" class="btn medium btn--outline" value="Revoke" onclick="return confirm(\'Are you sure delete this record ?\')">';
      echo '</form>';

      echo '</div></td>';
      echo '</tr>';

    }

    echo '</table>';

  }

  public static function fields() {
    return array(
      'action'   => array(
        'thankyou'  => array(
          'class'   => 'trigger thankyoupage',
          'title'   => 'Watch Orders',
          'text'    => 'Trigger when the order checkout in thankyou page'
        ),
        'new order' => array(
          'class'   => 'trigger neworder',
          'title'   => 'Watch New Orders',
          'text'    => 'Trigger when a new order is created'
        ),
        'pending'     => array(
          'class'     => 'trigger order-status-pending',
          'title'     => 'Watch Pending Order',
          'text'      => 'Trigger when the order status is pending'
        ),
        'processing'  => array(
          'class'     => 'trigger order-status-processing',
          'title'     => 'Watch Processing Order',
          'text'      => 'Trigger when the order status is processing'
        ),
        'on-hold'     => array(
          'class'     => 'trigger order-status-on-hold',
          'title'     => 'Watch On Hold Order',
          'text'      => 'Trigger when the order status is on hold'
        ),
        'completed'   => array(
          'class'     => 'trigger order-status-completed',
          'title'     => 'Watch Completed Order',
          'text'      => 'Trigger when the order status is completed'
        ),
        'cancelled'   => array(
          'class'     => 'trigger order-status-cancelled',
          'title'     => 'Watch Cancelled Order',
          'text'      => 'Trigger when the order status is cancelled'
        ),
        'refunded'    => array(
          'class'     => 'trigger order-status-refunded',
          'title'     => 'Watch Refunded Order',
          'text'      => 'Trigger when the order status is refunded'
        ),
        'failed'    => array(
          'class' => 'trigger order-status-failed',
          'title'     => 'Watch Failed Order',
          'text'      => 'Trigger when the order status is failed'
        ),
      ),
      'orderApi' => array(
        'id'      => array(),
        'url'     => array(),
        'trigger' => array(),
        'body'    => array(),
        'mode'    => array(),
        'json'    => array(),
      ),
    );
  }

  public static function triggers() {
    return array(
      'action' => array(
        'Orders'  => array(
          'status'  => 'woocommerce_thankyou'
        ),
        'New Orders' => array(
          'status'  => 'woocommerce_new_order'
        ),
      )
    );
  }

  public static function basicJsonSample() {

    echo ' *** JSON response example: ***
{
    "id": 727,
    "parent_id": 0,
    "number": "727",
    "order_key": "wc_order_58d2d042d1d",
    "created_via": "rest-api",
    "version": "3.0.0",
    "status": "completed",
    "currency": "USD",
    "date_created": "2017-03-22T16:28:02",
    "date_created_gmt": "2017-03-22T19:28:02",
    "date_modified": "2017-03-22T16:30:35",
    "date_modified_gmt": "2017-03-22T19:30:35",
    "discount_total": "0.00",
    "discount_tax": "0.00",
    "shipping_total": "10.00",
    "shipping_tax": "0.00",
    "cart_tax": "1.35",
    "total": "29.35",
    "total_tax": "1.35",
    "prices_include_tax": false,
    "customer_id": 0,
    "customer_ip_address": "",
    "customer_user_agent": "",
    "customer_note": "",
    "billing": {
      "first_name": "John",
      "last_name": "Doe",
      "company": "",
      "address_1": "969 Market",
      "address_2": "",
      "city": "San Francisco",
      "state": "CA",
      "postcode": "94103",
      "country": "US",
      "email": "john.doe@example.com",
      "phone": "(555) 555-5555"
    },
    "shipping": {
      "first_name": "John",
      "last_name": "Doe",
      "company": "",
      "address_1": "969 Market",
      "address_2": "",
      "city": "San Francisco",
      "state": "CA",
      "postcode": "94103",
      "country": "US"
    },
    "payment_method": "bacs",
    "payment_method_title": "Direct Bank Transfer",
    "transaction_id": "",
    "date_paid": "2017-03-22T16:28:08",
    "date_paid_gmt": "2017-03-22T19:28:08",
    "date_completed": "2017-03-22T16:30:35",
    "date_completed_gmt": "2017-03-22T19:30:35",
    "cart_hash": "",
    "meta_data": [
      {
        "id": 13106,
        "key": "_download_permissions_granted",
        "value": "yes"
      },
      {
        "id": 13109,
        "key": "_order_stock_reduced",
        "value": "yes"
      }
    ],
    "line_items": [
      {
        "id": 315,
        "name": "Woo Single #1",
        "product_id": 93,
        "variation_id": 0,
        "quantity": 2,
        "tax_class": "",
        "subtotal": "6.00",
        "subtotal_tax": "0.45",
        "total": "6.00",
        "total_tax": "0.45",
        "taxes": [
          {
            "id": 75,
            "total": "0.45",
            "subtotal": "0.45"
          }
        ],
        "meta_data": [],
        "sku": "",
        "price": 3
      },
      {
        "id": 316,
        "name": "Ship Your Idea &ndash; Color: Black, Size: M Test",
        "product_id": 22,
        "variation_id": 23,
        "quantity": 1,
        "tax_class": "",
        "subtotal": "12.00",
        "subtotal_tax": "0.90",
        "total": "12.00",
        "total_tax": "0.90",
        "taxes": [
          {
            "id": 75,
            "total": "0.9",
            "subtotal": "0.9"
          }
        ],
        "meta_data": [
          {
            "id": 2095,
            "key": "pa_color",
            "value": "black"
          },
          {
            "id": 2096,
            "key": "size",
            "value": "M Test"
          }
        ],
        "sku": "Bar3",
        "price": 12
      }
    ],
    "tax_lines": [
      {
        "id": 318,
        "rate_code": "US-CA-STATE TAX",
        "rate_id": 75,
        "label": "State Tax",
        "compound": false,
        "tax_total": "1.35",
        "shipping_tax_total": "0.00",
        "meta_data": []
      }
    ],
    "shipping_lines": [
      {
        "id": 317,
        "method_title": "Flat Rate",
        "method_id": "flat_rate",
        "total": "10.00",
        "total_tax": "0.00",
        "taxes": [],
        "meta_data": []
      }
    ],
    "fee_lines": [],
    "coupon_lines": [],
    "refunds": [],
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/orders/727"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/orders"
        }
      ]
    }
}';

  }

  public static function advanceJsonSample() {

    echo ' *** Upgrade now ! Filter the Order API json fields ***';
    echo ' 
 *** JSON response example: ***
{
    "id" : "",
    "status" : "",
    "total" : "",
    "billing" : {
            "first_name": "",
            "last_name": "",
    },
    "line_items": [
            {
              "name" : "",
              "product_id" : "",
              "sku" : "",
            },
    ],
}';

  }

  public static function apiKey() {

    echo '<div class="api-input-form">';
    echo '<div class="feature-tags">';
    echo "<span class=\"tag-key tag large tag--large\">API Key</span>";
    echo '<div class="feature-tags selected-key">';
    echo '</div></div>';
    echo '</div>';

  }

  public static function triggerAction() {

    echo '<div class="api-input-form">';
    echo '<div class="feature-tags">';
    echo "<span class=\"tag-key tag large tag--large\">API Action</span>";
    echo '<div class="feature-tags trigger-action">';
    echo '</div></div>';
    echo '</div>';

  }

  public static function filterMode () {

    echo '<div class="api-input-form">';
    echo '<div class="feature-tags">';
    echo "<span class=\"tag-key tag large tag--large\">Filter Mode</span>";
    echo '<div class="feature-tags api-mode">';
    echo '</div></div>';
    echo '</div>';

  }

  public static function preArea () {

    echo '<div class="filter-json json-mode">';
    echo '<pre class="prettyprint pretty-api pretty advance"></pre>';
    echo '</div>';

  }

}
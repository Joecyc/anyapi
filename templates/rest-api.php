<?php
/**
 * rest-api.php — WooCommerce REST API Test Tool template.
 *
 * @package AnyApi
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Saved API Keys (for auth selector) ───────────────────────────────────────
$keys = get_option( 'anyapi_wc_apikey', array() );
$active_keys = array_filter( $keys, fn( $k ) => ( $k['status'] ?? 'active' ) === 'active' );

// ── Common WC endpoints (quick-pick) ─────────────────────────────────────────
$wc_endpoints = array(
  array( 'label' => 'List Orders',        'method' => 'GET',  'path' => '/wp-json/wc/v3/orders' ),
  array( 'label' => 'Get Order',          'method' => 'GET',  'path' => '/wp-json/wc/v3/orders/{id}' ),
  array( 'label' => 'List Products',      'method' => 'GET',  'path' => '/wp-json/wc/v3/products' ),
  array( 'label' => 'Get Product',        'method' => 'GET',  'path' => '/wp-json/wc/v3/products/{id}' ),
  array( 'label' => 'List Customers',     'method' => 'GET',  'path' => '/wp-json/wc/v3/customers' ),
  array( 'label' => 'System Status',      'method' => 'GET',  'path' => '/wp-json/wc/v3/system_status' ),
  array( 'label' => 'Create Order',       'method' => 'POST', 'path' => '/wp-json/wc/v3/orders' ),
  array( 'label' => 'Update Order',       'method' => 'PUT',  'path' => '/wp-json/wc/v3/orders/{id}' ),
);
?>

<div id="anyapi-restapi" class="wrap">

  <!-- ======================================================================
       Page header
       ====================================================================== -->
  <div class="rt-header">
    <div class="rt-header__left">
      <h1 class="rt-title">🧪 <?php esc_html_e( 'WC REST API Tester', 'anyapi' ); ?></h1>
      <p class="rt-desc"><?php esc_html_e( 'Send requests to your WooCommerce REST API and inspect responses.', 'anyapi' ); ?></p>
    </div>
    <div class="rt-header__right">
      <a href="https://woocommerce.github.io/woocommerce-rest-api-docs/" target="_blank" rel="noopener" class="rt-docs-link">
        📖 <?php esc_html_e( 'WC API Docs', 'anyapi' ); ?>
      </a>
    </div>
  </div>

  <!-- ======================================================================
       Main layout: Request panel (left) + Response panel (right)
       ====================================================================== -->
  <div class="rt-layout">

    <!-- ── Left: Request ──────────────────────────────────────────────────── -->
    <div class="rt-panel rt-panel--request">

      <div class="rt-panel__header">
        <span class="rt-panel__icon">📤</span>
        <h2 class="rt-panel__title"><?php esc_html_e( 'Request', 'anyapi' ); ?></h2>
      </div>

      <div class="rt-panel__body">

        <!-- Quick-pick endpoints -->
        <div class="rt-field-group">
          <label class="rt-label"><?php esc_html_e( 'Quick Pick', 'anyapi' ); ?></label>
          <div class="rt-quickpick" id="rt-quickpick">
            <?php foreach ( $wc_endpoints as $ep ) :
              $method_class = 'is-' . strtolower( $ep['method'] );
            ?>
            <button
              class="rt-quickpick__btn"
              type="button"
              data-method="<?php echo esc_attr( $ep['method'] ); ?>"
              data-path="<?php echo esc_attr( $ep['path'] ); ?>"
            >
              <span class="rt-method-badge <?php echo esc_attr( $method_class ); ?>"><?php echo esc_html( $ep['method'] ); ?></span>
              <span class="rt-quickpick__label"><?php echo esc_html( $ep['label'] ); ?></span>
            </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Method + URL -->
        <div class="rt-field-group">
          <label class="rt-label" for="rt-url">
            <?php esc_html_e( 'Endpoint', 'anyapi' ); ?>
            <span class="rt-required">*</span>
          </label>
          <div class="rt-url-row">
            <select id="rt-method" class="rt-select rt-method-select">
              <option value="GET">GET</option>
              <option value="POST">POST</option>
              <option value="PUT">PUT</option>
              <option value="PATCH">PATCH</option>
              <option value="DELETE">DELETE</option>
            </select>
            <div class="rt-url-wrap">
              <span class="rt-base-url" id="rt-base-url"><?php echo esc_html( get_site_url() ); ?></span>
              <input
                type="text"
                id="rt-url"
                class="rt-input rt-input--mono"
                placeholder="/wp-json/wc/v3/orders"
                value="/wp-json/wc/v3/orders"
                autocomplete="off"
                spellcheck="false"
              >
            </div>
          </div>
          <div class="rt-error" id="rt-url-error"></div>
        </div>

        <!-- Authentication -->
        <div class="rt-field-group">
          <label class="rt-label"><?php esc_html_e( 'Authentication', 'anyapi' ); ?></label>
          <div class="rt-auth-row">
            <select id="rt-auth-type" class="rt-select">
              <option value="none"><?php esc_html_e( 'None', 'anyapi' ); ?></option>
              <option value="saved"><?php esc_html_e( 'Saved API Key', 'anyapi' ); ?></option>
              <option value="wc_basic"><?php esc_html_e( 'WC Consumer Key/Secret', 'anyapi' ); ?></option>
              <option value="bearer"><?php esc_html_e( 'Bearer Token (manual)', 'anyapi' ); ?></option>
            </select>
          </div>

          <!-- Saved key selector -->
          <div id="rt-auth-saved" class="rt-auth-fields" style="display:none;">
            <?php if ( empty( $active_keys ) ) : ?>
              <p class="rt-hint rt-hint--warn">
                ⚠️ <?php esc_html_e( 'No active API Keys found.', 'anyapi' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=anyapi-apikey' ) ); ?>">
                  <?php esc_html_e( 'Add one →', 'anyapi' ); ?>
                </a>
              </p>
            <?php else : ?>
              <select id="rt-saved-key" class="rt-select">
                <option value=""><?php esc_html_e( '— Select API Key —', 'anyapi' ); ?></option>
                <?php foreach ( $active_keys as $k ) : ?>
                  <option
                    value="<?php echo esc_attr( $k['id'] ); ?>"
                    data-type="<?php echo esc_attr( $k['type'] ?? 'bearer' ); ?>"
                  >
                    <?php echo esc_html( $k['name'] ); ?>
                    (<?php echo $k['type'] === 'basic' ? 'Basic Auth' : 'Bearer'; ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>

          <!-- WC Consumer Key / Secret -->
          <div id="rt-auth-wc" class="rt-auth-fields rt-two-col" style="display:none;">
            <div class="rt-field-group">
              <label class="rt-label rt-label--sm" for="rt-ck">Consumer Key</label>
              <input type="text" id="rt-ck" class="rt-input rt-input--mono rt-input--sm" placeholder="ck_xxxxxxxxxxxx" autocomplete="off">
            </div>
            <div class="rt-field-group">
              <label class="rt-label rt-label--sm" for="rt-cs">Consumer Secret</label>
              <div class="rt-secret-wrap">
                <input type="password" id="rt-cs" class="rt-input rt-input--mono rt-input--sm" placeholder="cs_xxxxxxxxxxxx">
                <button class="rt-eye-btn" type="button" data-target="rt-cs">👁</button>
              </div>
            </div>
          </div>

          <!-- Manual Bearer Token -->
          <div id="rt-auth-bearer" class="rt-auth-fields" style="display:none;">
            <div class="rt-secret-wrap">
              <input type="password" id="rt-bearer-token" class="rt-input rt-input--mono" placeholder="<?php esc_attr_e( 'Paste Bearer token…', 'anyapi' ); ?>">
              <button class="rt-eye-btn" type="button" data-target="rt-bearer-token">👁</button>
            </div>
          </div>
        </div>

        <!-- Request Headers (optional, collapsible) -->
        <div class="rt-field-group">
          <button class="rt-collapsible" type="button" id="rt-headers-toggle" aria-expanded="false">
            <span class="rt-collapsible__icon">▶</span>
            <?php esc_html_e( 'Custom Headers', 'anyapi' ); ?>
            <span class="rt-collapsible__badge" id="rt-headers-count" style="display:none;">0</span>
          </button>
          <div class="rt-collapsible__body" id="rt-headers-body" style="display:none;">
            <div id="rt-headers-list" class="rt-kv-list">
              <!-- dynamic rows injected by JS -->
            </div>
            <button class="rt-add-row-btn" type="button" id="rt-add-header">
              + <?php esc_html_e( 'Add Header', 'anyapi' ); ?>
            </button>
          </div>
        </div>

        <!-- Request Body (POST/PUT/PATCH only) -->
        <div class="rt-field-group" id="rt-body-group">
          <label class="rt-label" for="rt-body">
            <?php esc_html_e( 'Request Body', 'anyapi' ); ?>
            <span class="rt-label-hint"><?php esc_html_e( 'JSON', 'anyapi' ); ?></span>
          </label>
          <div class="rt-editor-wrap">
            <textarea
              id="rt-body"
              class="rt-editor"
              placeholder='{"key": "value"}'
              rows="8"
              spellcheck="false"
            ></textarea>
            <div class="rt-editor-actions">
              <button class="rt-editor-btn" type="button" id="rt-body-format">
                ✨ <?php esc_html_e( 'Format', 'anyapi' ); ?>
              </button>
              <button class="rt-editor-btn" type="button" id="rt-body-clear">
                🗑 <?php esc_html_e( 'Clear', 'anyapi' ); ?>
              </button>
            </div>
          </div>
          <div class="rt-error" id="rt-body-error"></div>
        </div>

        <!-- Send button -->
        <button class="rt-send-btn" type="button" id="rt-send">
          <span class="rt-send-btn__icon" id="rt-send-icon">▶</span>
          <span id="rt-send-label"><?php esc_html_e( 'Send Request', 'anyapi' ); ?></span>
        </button>

      </div><!-- /.rt-panel__body -->
    </div><!-- /.rt-panel--request -->

    <!-- ── Right: Response ────────────────────────────────────────────────── -->
    <div class="rt-panel rt-panel--response">

      <div class="rt-panel__header">
        <span class="rt-panel__icon">📥</span>
        <h2 class="rt-panel__title"><?php esc_html_e( 'Response', 'anyapi' ); ?></h2>
        <div class="rt-response-meta" id="rt-response-meta" style="display:none;">
          <span class="rt-status-badge" id="rt-status-badge"></span>
          <span class="rt-latency" id="rt-latency"></span>
        </div>
        <button class="rt-copy-btn" type="button" id="rt-copy-response" style="display:none;" title="Copy response">
          📋
        </button>
      </div>

      <div class="rt-panel__body rt-panel__body--response">

        <!-- Empty state -->
        <div class="rt-response-empty" id="rt-response-empty">
          <div class="rt-response-empty__icon">📡</div>
          <p class="rt-response-empty__text">
            <?php esc_html_e( 'Send a request to see the response here.', 'anyapi' ); ?>
          </p>
        </div>

        <!-- Loading state -->
        <div class="rt-response-loading" id="rt-response-loading" style="display:none;">
          <div class="rt-spinner"></div>
          <p><?php esc_html_e( 'Sending request…', 'anyapi' ); ?></p>
        </div>

        <!-- Response tabs -->
        <div class="rt-response-content" id="rt-response-content" style="display:none;">

          <div class="rt-tabs" role="tablist">
            <button class="rt-tab active" role="tab" data-tab="body" aria-selected="true">
              <?php esc_html_e( 'Body', 'anyapi' ); ?>
            </button>
            <button class="rt-tab" role="tab" data-tab="headers" aria-selected="false">
              <?php esc_html_e( 'Headers', 'anyapi' ); ?>
            </button>
          </div>

          <!-- Body tab -->
          <div class="rt-tab-panel" id="rt-tab-body">
            <div class="rt-response-toolbar">
              <div class="rt-view-toggle" role="group">
                <button class="rt-view-btn active" data-view="pretty" type="button">Pretty</button>
                <button class="rt-view-btn" data-view="raw" type="button">Raw</button>
              </div>
              <span class="rt-response-size" id="rt-response-size"></span>
            </div>
            <pre class="rt-code" id="rt-response-body"></pre>
          </div>

          <!-- Headers tab -->
          <div class="rt-tab-panel" id="rt-tab-headers" style="display:none;">
            <div class="rt-headers-table" id="rt-response-headers"></div>
          </div>

        </div><!-- /.rt-response-content -->

      </div><!-- /.rt-panel__body -->
    </div><!-- /.rt-panel--response -->

  </div><!-- /.rt-layout -->

</div><!-- /#anyapi-restapi -->
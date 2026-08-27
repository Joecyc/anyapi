<?php
/**
 * order-wizard.php — Shared integration wizard partial.
 *
 * Self-contained: computes only the plan/limits/keys/triggers data the
 * wizard markup below references. Included from both the Order API page
 * and the Integration Templates page.
 *
 * @package AnyApi
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$plan             = \Anyapi\PlanHelper::currentPlan();
$limits           = \Anyapi\PlanHelper::currentLimits();
$is_free          = ( $plan === 'starter' );
$json_locked      = ! $limits['json_filter'];
$upgrade_url      = esc_url( $limits['upgrade_url'] );

$used_keys        = \Anyapi\PlanHelper::usedApiKeys();
$key_limit        = $limits['api_keys_limit'];
$key_limit_hit    = ( $key_limit !== PHP_INT_MAX && $used_keys >= $key_limit );

$used_calls       = \Anyapi\PlanHelper::usedCallsThisMonth();
$call_limit       = $limits['monthly_calls'];
$call_pct         = ( $call_limit !== PHP_INT_MAX && $call_limit > 0 )
                     ? min( 100, round( ( $used_calls / $call_limit ) * 100 ) )
                     : -1;

$allowed_triggers = $limits['allowed_triggers'];

// ── Stored API Keys (for select dropdown) ────────────────────────────────────
$stored_keys_raw  = get_option( 'anyapi_wc_apikey', array() );

// Migrate: normalise legacy format (numeric index, no id/type/status fields)
// New format: [ 'ak_xxx' => ['id'=>'ak_xxx','name'=>...,'type'=>...,'status'=>...] ]
// Old format: [ 0 => ['authType'=>'bearer','key'=>'...','name'=>'santi'] ]
$stored_keys = array();
foreach ( $stored_keys_raw as $k => $v ) {
  if ( ! is_array( $v ) ) continue;
  if ( ! isset( $v['id'] ) || strpos( (string) $v['id'], 'ak_' ) !== 0 ) {
    // Legacy record — assign a stable id based on key index
    $v['id']     = 'ak_legacy_' . $k;
    $v['type']   = $v['type']   ?? ( isset($v['authType']) ? ( $v['authType'] === 'bearer' ? 'bearer' : 'basic' ) : 'bearer' );
    $v['name']   = $v['name']   ?? ( 'Key #' . $k );
    $v['status'] = $v['status'] ?? 'active';
  }
  $stored_keys[ $v['id'] ] = $v;
}

$active_keys = array_filter( $stored_keys, fn( $k ) => ( $k['status'] ?? 'active' ) === 'active' );

// ── All supported triggers ────────────────────────────────────────────────────
$all_triggers = array(
  'watch_orders'           => array( 'icon' => '🛒', 'label' => 'Watch Orders',  'desc' => 'Fires on the thank-you page after checkout' ),
  'watch_new_orders'       => array( 'icon' => '🆕', 'label' => 'New Order',     'desc' => 'Fires when a new order is created' ),
  'watch_pending_order'    => array( 'icon' => '⏳', 'label' => 'Pending',       'desc' => 'Order status → pending' ),
  'watch_processing_order' => array( 'icon' => '⚙️', 'label' => 'Processing',    'desc' => 'Order status → processing' ),
  'watch_on_hold_order'    => array( 'icon' => '🛑', 'label' => 'On Hold',       'desc' => 'Order status → on-hold' ),
  'watch_completed_order'  => array( 'icon' => '✅', 'label' => 'Completed',     'desc' => 'Order status → completed' ),
  'watch_cancelled_order'  => array( 'icon' => '❌', 'label' => 'Cancelled',     'desc' => 'Order status → cancelled' ),
  'watch_refunded_order'   => array( 'icon' => '💸', 'label' => 'Refunded',      'desc' => 'Order status → refunded' ),
  'watch_failed_order'     => array( 'icon' => '⚠️', 'label' => 'Failed',        'desc' => 'Order status → failed' ),
);
?>

<!-- ======================================================================
     Wizard Panel (hidden by default, shown on New / Edit)
====================================================================== -->
<div id="integration-wizard" class="integration-wizard is-hidden">

  <div class="wizard-toolbar">
    <h2 id="wizard-title" class="wizard-title"><?php esc_html_e( 'New Integration', 'anyapi' ); ?></h2>
    <button id="wizard-close-btn" class="oi-btn oi-btn--ghost" type="button">
      ✕ <?php esc_html_e( 'Cancel', 'anyapi' ); ?>
    </button>
  </div>

  <!-- hidden edit state -->
  <input type="hidden" id="integration-id" value="0">

  <div class="integration-main-grid">

    <!-- ===== Sidebar ===== -->
    <div class="steps-sidebar">
      <div class="progress-bar-container">
        <div class="progress-bar"><div class="progress-fill" id="progress-fill"></div></div>
      </div>
      <div class="step-item active" data-step="1"><div class="step-number">1</div><div class="step-label"><?php esc_html_e( 'Automation', 'anyapi' ); ?></div></div>
      <div class="step-item"        data-step="2"><div class="step-number">2</div><div class="step-label"><?php esc_html_e( 'Trigger', 'anyapi' ); ?></div></div>
      <div class="step-item"        data-step="3"><div class="step-number">3</div><div class="step-label"><?php esc_html_e( 'JSON Filter', 'anyapi' ); ?></div></div>
      <div class="step-item"        data-step="4"><div class="step-number">4</div><div class="step-label"><?php esc_html_e( 'Complete', 'anyapi' ); ?></div></div>

      <?php if ( $call_pct >= 0 ) : ?>
      <div class="sidebar-usage-meter">
        <div class="usage-meter__header">
          <span class="usage-meter__label"><?php esc_html_e( 'Monthly Calls', 'anyapi' ); ?></span>
          <span class="usage-meter__count"><?php echo esc_html( number_format( $used_calls ) . ' / ' . number_format( $call_limit ) ); ?></span>
        </div>
        <div class="usage-meter__bar">
          <div class="usage-meter__fill<?php echo $call_pct >= 100 ? ' is-throttling' : ( $call_pct >= 80 ? ' is-warn' : '' ); ?>"
               style="width:<?php echo esc_attr( $call_pct ); ?>%"></div>
        </div>
        <?php if ( $call_pct >= 80 ) : ?>
        <?php if ( $call_pct >= 100 ) : ?>
        <p class="usage-meter__note is-throttling">
          <?php esc_html_e( 'Limit reached — integrations fire with 30s delay.', 'anyapi' ); ?>
        </p>
        <?php endif; ?>
        <a class="usage-meter__cta" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener">
          ⚡ <?php echo $call_pct >= 100 ? esc_html__( 'Remove delay →', 'anyapi' ) : esc_html__( 'Upgrade for unlimited →', 'anyapi' ); ?>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="sidebar-plan-badge plan-badge--<?php echo esc_attr( $plan ); ?>">
        <span class="plan-badge__dot"></span>
        <?php echo esc_html( $limits['label'] . ' Plan' ); ?>
        <?php if ( $is_free ) : ?>
        &nbsp;·&nbsp;<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Upgrade', 'anyapi' ); ?></a>
        <?php endif; ?>
      </div>
    </div>

    <!-- ===== Main content ===== -->
    <div class="integration-content">

      <!-- STEP 1 -------------------------------------------------------- -->
      <div class="step-panel active" data-step="1">
        <div class="panel-header js-generic-ctx">
          <h2><?php esc_html_e( 'API Endpoint & Authentication', 'anyapi' ); ?></h2>
          <p class="description"><?php esc_html_e( 'Set up your API endpoint, authentication, and base payload.', 'anyapi' ); ?></p>
        </div>
        <div class="panel-header js-template-ctx" hidden>
          <h2><?php esc_html_e( 'Email Notification Setup', 'anyapi' ); ?></h2>
          <p class="description"><?php esc_html_e( 'Pick a preset and set the recipient — the message already includes the full order summary.', 'anyapi' ); ?></p>
        </div>

        <!-- Destination type -->
        <div class="form-group js-generic-ctx">
          <label><?php esc_html_e( 'Destination Type', 'anyapi' ); ?></label>
          <div class="dest-selector">
            <button class="dest-btn active" data-dest="url" type="button">
              <span class="mode-icon">🔗</span><span class="mode-label"><?php esc_html_e( 'URL / API', 'anyapi' ); ?></span>
            </button>
            <button class="dest-btn<?php echo $is_free ? ' is-locked' : ''; ?>" data-dest="email"
                    data-locked="<?php echo $is_free ? '1' : '0'; ?>" type="button">
              <span class="mode-icon">✉️</span><span class="mode-label"><?php esc_html_e( 'Email', 'anyapi' ); ?><?php if ( $is_free ) echo ' <span class="mode-lock-badge">Template</span>'; ?></span>
            </button>
          </div>
        </div>

        <!-- Integration name -->
        <div class="form-group">
          <label for="integration-name"><?php esc_html_e( 'Integration Name', 'anyapi' ); ?></label>
          <input type="text" id="integration-name" class="widefat"
                 placeholder="<?php esc_attr_e( 'e.g. Slack Order Notification', 'anyapi' ); ?>"
                 data-ph-generic="<?php esc_attr_e( 'e.g. Slack Order Notification', 'anyapi' ); ?>"
                 data-ph-template="<?php esc_attr_e( 'e.g. Order Details to Courier', 'anyapi' ); ?>">
          <p class="help-text js-generic-ctx"><?php esc_html_e( 'Optional. Helps you identify this integration in the list.', 'anyapi' ); ?></p>
          <p class="help-text js-template-ctx" hidden><?php esc_html_e( 'Optional. A name to identify this integration in your list.', 'anyapi' ); ?></p>
        </div>

        <div id="dest-url-fields">
        <!-- API URL -->
        <div class="form-group">
          <label for="api-url"><?php esc_html_e( 'API URL', 'anyapi' ); ?> <span class="required">*</span></label>
          <input type="url" id="api-url" class="widefat"
                 placeholder="https://api.example.com/orders">
          <p class="help-text"><?php esc_html_e( 'Must use https://', 'anyapi' ); ?></p>
          <div class="error-message" id="url-error"></div>
        </div>

        <!-- API Key select — value = ak_xxx ID -->
        <div class="form-group">
          <div class="label-row">
            <label for="api-key-select"><?php esc_html_e( 'API Key', 'anyapi' ); ?> <span class="required">*</span></label>
            <?php if ( $key_limit !== PHP_INT_MAX ) : ?>
            <span class="usage-pill<?php echo $key_limit_hit ? ' is-maxed' : ''; ?>">
              <?php echo esc_html( $used_keys . ' / ' . $key_limit ); ?> used
            </span>
            <?php endif; ?>
          </div>

          <div class="api-key-select-row">
            <select id="api-key-select" class="widefat">
              <option value=""><?php esc_html_e( '— Select API Key —', 'anyapi' ); ?></option>
              <?php foreach ( $active_keys as $key ) : ?>
              <option value="<?php echo esc_attr( $key['id'] ); ?>"
                      data-type="<?php echo esc_attr( $key['type'] ); ?>">
                <?php
                $type_badge = $key['type'] === 'bearer' ? 'Bearer' : 'Basic';
                echo esc_html( $key['name'] . ' (' . $type_badge . ')' );
                ?>
              </option>
              <?php endforeach; ?>
            </select>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=anyapi_apikey' ) ); ?>"
               class="oi-btn oi-btn--ghost oi-btn--sm" title="<?php esc_attr_e( 'Manage API Keys', 'anyapi' ); ?>">
              🔑 <?php esc_html_e( 'Manage', 'anyapi' ); ?>
            </a>
          </div>

          <?php if ( empty( $active_keys ) ) : ?>
          <p class="limit-warning">
            <?php esc_html_e( 'No API Keys found.', 'anyapi' ); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=anyapi_apikey' ) ); ?>">
              <?php esc_html_e( 'Create one →', 'anyapi' ); ?>
            </a>
          </p>
          <?php elseif ( $key_limit_hit ) : ?>
          <p class="limit-warning">
            🔒 <?php esc_html_e( 'API Key limit reached.', 'anyapi' ); ?>
            <a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener">
              <?php esc_html_e( 'Upgrade to add more →', 'anyapi' ); ?>
            </a>
          </p>
          <?php endif; ?>
          <div class="error-message" id="key-error"></div>
        </div>

        <!-- Payload -->
        <div class="form-group">
          <label for="api-payload"><?php esc_html_e( 'API Payload (JSON)', 'anyapi' ); ?></label>
          <textarea id="api-payload" class="widefat code-textarea" rows="7"
            placeholder='{"text": "New order received"}'></textarea>
          <p class="help-text" id="payload-hint">
            <?php esc_html_e( 'Leave empty to send the full WooCommerce order data.', 'anyapi' ); ?>
          </p>
          <div class="error-message" id="payload-error"></div>
        </div>
        </div><!-- /#dest-url-fields -->

        <div id="dest-email-fields" hidden>
          <div class="form-group">
            <label for="email-to"><?php esc_html_e( 'Recipient Email', 'anyapi' ); ?> <span class="required">*</span></label>
            <input type="text" id="email-to" class="widefat"
                   placeholder="<?php esc_attr_e( 'orders@example.com', 'anyapi' ); ?>"
                   data-ph-generic="<?php esc_attr_e( 'orders@example.com', 'anyapi' ); ?>"
                   data-ph-template="<?php esc_attr_e( 'your-courier@example.com', 'anyapi' ); ?>">
            <p class="help-text js-generic-ctx"><?php esc_html_e( 'Where to send the order notification.', 'anyapi' ); ?></p>
            <p class="help-text js-template-ctx" hidden><?php esc_html_e( 'e.g. courier or logistics email.', 'anyapi' ); ?></p>
            <div class="error-message" id="email-to-error"></div>
          </div>
          <div class="form-group">
            <label for="email-subject"><?php esc_html_e( 'Subject', 'anyapi' ); ?> <span class="required">*</span></label>
            <input type="text" id="email-subject" class="widefat" placeholder="<?php esc_attr_e( 'New order #{{order_id}}', 'anyapi' ); ?>">
            <div class="error-message" id="email-subject-error"></div>
          </div>
          <div class="form-group">
            <label for="email-preamble"><?php esc_html_e( 'Intro Message (optional)', 'anyapi' ); ?></label>
            <textarea id="email-preamble" class="widefat code-textarea" rows="4"
              placeholder="<?php esc_attr_e( 'Your message. Use {{order_summary}} to insert order details.', 'anyapi' ); ?>"></textarea>
            <p class="help-text js-generic-ctx"><?php esc_html_e( 'Use {{order_summary}} to insert the full order details (items, weight, dimensions, total), and {{order_id}} for the order number. Other placeholders are not replaced.', 'anyapi' ); ?></p>
            <p class="help-text js-template-ctx" hidden><?php esc_html_e( '{{order_summary}} inserts the full order details (items, weight, dimensions, total). Write your intro around it or move it where you like.', 'anyapi' ); ?></p>
          </div>
        </div><!-- /#dest-email-fields -->

        <div class="form-actions">
          <button class="oi-btn oi-btn--primary oi-btn--hero next-step" data-next="2" type="button">
            <?php esc_html_e( 'Next →', 'anyapi' ); ?>
          </button>
        </div>
      </div><!-- /step-panel 1 -->

      <!-- STEP 2 -------------------------------------------------------- -->
      <div class="step-panel" data-step="2">
        <div class="panel-header">
          <h2><?php esc_html_e( 'Select Order Trigger', 'anyapi' ); ?></h2>
          <p class="description">
            <?php esc_html_e( 'Choose which WooCommerce order event fires this integration.', 'anyapi' ); ?>
            <?php if ( $is_free ) : ?>
            &nbsp;·&nbsp;<span class="plan-inline-note">
              <?php esc_html_e( 'Starter plan: 3 triggers · 500 calls/mo (throttled after limit)', 'anyapi' ); ?>
            </span>
            <?php endif; ?>
          </p>
        </div>

        <div class="action-tiles">
          <?php foreach ( $all_triggers as $action => $t ) :
            $locked = ! empty( $allowed_triggers ) && ! in_array( $action, $allowed_triggers, true );
          ?>
          <div class="action-tile<?php echo $locked ? ' is-locked' : ''; ?>"
               data-action="<?php echo esc_attr( $action ); ?>"
               data-locked="<?php echo $locked ? '1' : '0'; ?>"
               role="button" tabindex="0"
               aria-label="<?php echo esc_attr( $t['label'] ); ?>">
            <?php if ( $locked ) : ?>
            <span class="tile-lock-badge">🔒 Lite+</span>
            <?php endif; ?>
            <div class="tile-icon"><?php echo esc_html( $t['icon'] ); ?></div>
            <h3><?php echo esc_html( $t['label'] ); ?></h3>
            <p><?php echo esc_html( $t['desc'] ); ?></p>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if ( $is_free ) : ?>
        <div class="plan-inline-upgrade">
          🔒 <?php esc_html_e( '6 more triggers available on Lite and above — and no call throttling.', 'anyapi' ); ?>&nbsp;
          <a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener" class="btn-inline-upgrade">
            <?php esc_html_e( 'Unlock all triggers →', 'anyapi' ); ?>
          </a>
        </div>
        <?php endif; ?>

        <div class="error-message" id="action-error"></div>
        <div class="form-actions">
          <button class="oi-btn oi-btn--ghost oi-btn--hero prev-step" data-prev="1" type="button">← <?php esc_html_e( 'Back', 'anyapi' ); ?></button>
          <button class="oi-btn oi-btn--primary oi-btn--hero next-step" data-next="3" type="button"><?php esc_html_e( 'Next →', 'anyapi' ); ?></button>
        </div>
      </div><!-- /step-panel 2 -->

      <!-- STEP 3 -------------------------------------------------------- -->
      <div class="step-panel" data-step="3">
        <div class="panel-header">
          <h2>JSON Filter</h2>
          <p class="description" id="step3-desc"><?php esc_html_e( 'Choose what order data gets sent in the API payload.', 'anyapi' ); ?></p>
        </div>

        <div id="filter-ui-wrap">
        <div class="filter-mode-selector">
          <button class="filter-mode-btn active" data-mode="basic" type="button">
            <span class="mode-icon">📦</span>
            <span class="mode-label"><?php esc_html_e( 'Basic', 'anyapi' ); ?></span>
            <span class="mode-desc"><?php esc_html_e( 'Send all fields', 'anyapi' ); ?></span>
          </button>
          <button class="filter-mode-btn<?php echo $json_locked ? ' is-locked' : ''; ?>"
                  data-mode="advanced" data-locked="<?php echo $json_locked ? '1' : '0'; ?>" type="button">
            <span class="mode-icon">🎛</span>
            <span class="mode-label"><?php esc_html_e( 'Advanced', 'anyapi' ); ?> <?php if ( $json_locked ) echo '<span class="mode-lock-badge">Lite+</span>'; ?></span>
            <span class="mode-desc"><?php esc_html_e( 'Pick fields visually', 'anyapi' ); ?></span>
          </button>
          <button class="filter-mode-btn<?php echo $json_locked ? ' is-locked' : ''; ?>"
                  data-mode="expert" data-locked="<?php echo $json_locked ? '1' : '0'; ?>" type="button">
            <span class="mode-icon">⌨️</span>
            <span class="mode-label"><?php esc_html_e( 'Expert', 'anyapi' ); ?> <?php if ( $json_locked ) echo '<span class="mode-lock-badge">Lite+</span>'; ?></span>
            <span class="mode-desc"><?php esc_html_e( 'Edit raw JSON', 'anyapi' ); ?></span>
          </button>
        </div>

        <?php if ( $json_locked ) : ?>
        <div class="filter-upgrade-nudge">
          <span class="nudge-icon">✨</span>
          <div class="nudge-body">
            <strong><?php esc_html_e( 'Choose exactly which fields to send', 'anyapi' ); ?></strong>
            <p><?php esc_html_e( 'Basic sends the full order object. Advanced picks fields visually, Expert edits the raw JSON — both run on Lite.', 'anyapi' ); ?></p>
          </div>
          <a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener" class="btn-nudge-upgrade">
            <?php esc_html_e( 'See Lite pricing →', 'anyapi' ); ?>
          </a>
        </div>
        <?php endif; ?>

        <!-- Basic -->
        <div id="mode-basic" class="mode-section">
          <div class="basic-mode-info">
            <div class="info-icon">ℹ️</div>
            <div>
              <strong><?php esc_html_e( 'Full order data, or your own JSON.', 'anyapi' ); ?></strong>
              <p><?php esc_html_e( 'Leave the Step 1 payload empty to forward the full WooCommerce order object. Enter a static JSON there to send exactly that instead. {{variable}} placeholders are not interpolated in Basic mode.', 'anyapi' ); ?></p>
            </div>
          </div>
          <div class="json-preview-box"><pre>// Sent when the Step 1 payload is empty:
{
  "id": 1234,
  "status": "processing",
  "total": "49.99",
  "billing": { "first_name": "...", "email": "..." },
  "line_items": [ { "name": "...", "quantity": 1 } ],
  ...
}</pre></div>
        </div>

        <!-- Advanced -->
        <div id="mode-advanced" class="mode-section" style="display:none;">
          <div class="field-search-wrapper">
            <div class="search-input-group">
              <span class="search-icon">🔍</span>
              <input type="text" id="field-search" class="field-search-input"
                placeholder="<?php esc_attr_e( 'Search fields… e.g. billing.email, total', 'anyapi' ); ?>"
                autocomplete="off">
            </div>
            <div id="search-results" class="search-results-dropdown" style="display:none;"></div>
          </div>
          <div class="step3-grid">
            <div class="selected-fields-panel">
              <div class="panel-title-row">
                <h3><?php esc_html_e( 'Selected Fields', 'anyapi' ); ?> <span class="badge" id="selected-count">0</span></h3>
                <button class="button-clear-all" type="button"><?php esc_html_e( 'Clear All', 'anyapi' ); ?></button>
              </div>
              <div id="selected-fields-list" class="selected-fields-list">
                <p class="no-fields-hint"><?php esc_html_e( 'Search for fields above and click to add them here.', 'anyapi' ); ?></p>
              </div>
            </div>
            <div class="json-preview-panel">
              <div class="panel-title-row">
                <h3><?php esc_html_e( 'Live JSON Preview', 'anyapi' ); ?></h3>
                <span class="preview-badge"><?php esc_html_e( 'Auto-updates', 'anyapi' ); ?></span>
              </div>
              <div class="json-preview-box json-preview-box--live">
                <pre id="json-preview-content">// <?php esc_html_e( 'No fields selected yet', 'anyapi' ); ?></pre>
              </div>
            </div>
          </div>
        </div>

        <!-- Expert -->
        <div id="mode-expert" class="mode-section" style="display:none;">
          <div class="expert-mode-divider"><span><?php esc_html_e( 'Raw JSON Override', 'anyapi' ); ?></span></div>
          <p class="help-text"><?php esc_html_e( 'Write your full custom JSON. Use {{variable}} placeholders.', 'anyapi' ); ?></p>
          <textarea id="expert-json-textarea" class="widefat code-textarea" rows="10"
            placeholder='{"order_id":"{{order_id}}","customer":"{{billing_first_name}} {{billing_last_name}}","total":"{{order_total}}"}'></textarea>
          <div class="error-message" id="expert-error"></div>
        </div>
        </div><!-- /#filter-ui-wrap -->

        <div id="email-body-note" hidden>
          <p class="help-text"><?php esc_html_e( 'Email destinations use the message body you write in Step 1. JSON filtering doesn\'t apply.', 'anyapi' ); ?></p>
        </div>

        <div class="form-actions">
          <button class="oi-btn oi-btn--ghost oi-btn--hero prev-step" data-prev="2" type="button">← <?php esc_html_e( 'Back', 'anyapi' ); ?></button>
          <button class="oi-btn oi-btn--primary oi-btn--hero next-step" data-next="4" type="button"><?php esc_html_e( 'Review →', 'anyapi' ); ?></button>
        </div>
      </div><!-- /step-panel 3 -->

      <!-- STEP 4 -------------------------------------------------------- -->
      <div class="step-panel" data-step="4">
        <div class="step4-wrapper">
          <div class="step4-header">
            <div class="step4-icon">🎉</div>
            <h2><?php esc_html_e( 'Ready to Save', 'anyapi' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Review your integration settings before saving.', 'anyapi' ); ?></p>
          </div>
          <div class="summary-grid">
            <div class="summary-card"><span class="summary-label"><?php esc_html_e( 'Name', 'anyapi' ); ?></span><span class="summary-value" id="summary-name">—</span></div>
            <div class="summary-card"><span class="summary-label"><?php esc_html_e( 'API Endpoint', 'anyapi' ); ?></span><span class="summary-value summary-value--url" id="summary-url">—</span></div>
            <div class="summary-card"><span class="summary-label"><?php esc_html_e( 'API Key', 'anyapi' ); ?></span><span class="summary-value" id="summary-key">—</span></div>
            <div class="summary-card"><span class="summary-label"><?php esc_html_e( 'Trigger', 'anyapi' ); ?></span><span class="summary-value" id="summary-trigger">—</span></div>
            <div class="summary-card"><span class="summary-label"><?php esc_html_e( 'Filter Mode', 'anyapi' ); ?></span><span class="summary-value" id="summary-mode">—</span></div>
            <div class="summary-card summary-card--full"><span class="summary-label"><?php esc_html_e( 'Fields', 'anyapi' ); ?></span><span class="summary-value" id="summary-fields">—</span></div>
            <div class="summary-card summary-card--email" id="summary-to-card" hidden><span class="summary-label"><?php esc_html_e( 'Recipient', 'anyapi' ); ?></span><span class="summary-value" id="summary-to">—</span></div>
            <div class="summary-card summary-card--email" id="summary-subject-card" hidden><span class="summary-label"><?php esc_html_e( 'Subject', 'anyapi' ); ?></span><span class="summary-value" id="summary-subject">—</span></div>
          </div>
          <div id="save-status" class="save-status" style="display:none;"></div>
          <div class="step4-actions">
            <button class="oi-btn oi-btn--ghost oi-btn--hero prev-step" data-prev="3" type="button">← <?php esc_html_e( 'Edit', 'anyapi' ); ?></button>
            <button id="finish-btn" class="oi-btn oi-btn--primary oi-btn--hero oi-btn--save" type="button">
              💾 <?php esc_html_e( 'Save & Finish', 'anyapi' ); ?>
            </button>
          </div>
          <p class="step4-hint">
            <?php esc_html_e( 'After saving, test by placing an order and checking', 'anyapi' ); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=anyapi_apilog' ) ); ?>">
              <?php esc_html_e( 'API Logs', 'anyapi' ); ?>
            </a>.
          </p>
        </div>
      </div><!-- /step-panel 4 -->

    </div><!-- /.integration-content -->
  </div><!-- /.integration-main-grid -->
</div><!-- /#integration-wizard -->

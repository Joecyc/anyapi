<?php
/**
 * order-api.php — Order API Integration wizard and list template.
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

// ── Existing integrations (for list table) ────────────────────────────────────
$integrations_raw = get_option( 'anyapi_wc_orderapi', array() );

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

// Trigger label lookup for list table
$trigger_labels = array_map( fn( $t ) => $t['icon'] . ' ' . $t['label'], $all_triggers );
?>

<div id="anyapi-order-page" class="wrap" data-plan="<?php echo esc_attr( $plan ); ?>">

  <!-- ======================================================================
       Upgrade Modal
  ====================================================================== -->
  <div id="anyapi-upgrade-modal" class="upgrade-modal" role="dialog" aria-modal="true"
       aria-labelledby="modal-title" style="display:none;">
    <div class="upgrade-modal__backdrop"></div>
    <div class="upgrade-modal__box">
      <button class="upgrade-modal__close" type="button" aria-label="Close">×</button>
      <div class="upgrade-modal__icon">🚀</div>
      <h2 class="upgrade-modal__title" id="modal-title"><?php esc_html_e( 'Upgrade Required', 'anyapi' ); ?></h2>
      <p class="upgrade-modal__body" id="modal-body"></p>
      <div class="upgrade-modal__plans">
        <div class="modal-plan modal-plan--current">
          <div class="modal-plan__badge"><?php esc_html_e( 'Current', 'anyapi' ); ?></div>
          <div class="modal-plan__name">Free</div>
          <div class="modal-plan__price">$0</div>
          <ul class="modal-plan__features">
            <li class="feat--yes">1 API Key</li>
            <li class="feat--yes">3 Triggers</li>
            <li class="feat--yes">500 API Calls/mo (throttled after limit)</li>
            <li class="feat--yes">Real-time Log</li>
            <li class="feat--no">JSON Filter</li>
            <li class="feat--no">Log Search &amp; Stats</li>
          </ul>
        </div>
        <div class="modal-plan modal-plan--highlight">
          <div class="modal-plan__badge modal-plan__badge--pro">Most Popular</div>
          <div class="modal-plan__name">Lite</div>
          <div class="modal-plan__price">$79<span>/yr</span></div>
          <ul class="modal-plan__features">
            <li class="feat--yes">5 API Keys</li>
            <li class="feat--yes">All Triggers</li>
            <li class="feat--yes">Unlimited API Calls</li>
            <li class="feat--yes">Full API Logs</li>
            <li class="feat--yes">JSON Filter</li>
            <li class="feat--yes">Log Search &amp; Stats</li>
          </ul>
        </div>
        <div class="modal-plan modal-plan--plus">
          <div class="modal-plan__name">Plus</div>
          <div class="modal-plan__price">$149<span>/yr</span></div>
          <ul class="modal-plan__features">
            <li class="feat--yes">20 API Keys</li>
            <li class="feat--yes">All Triggers</li>
            <li class="feat--yes">JSON Filter</li>
            <li class="feat--yes">Webhook Inbound</li>
          </ul>
        </div>
      </div>
      <a href="<?php echo esc_url( $upgrade_url ); ?>" class="upgrade-modal__cta" target="_blank" rel="noopener">
        <?php esc_html_e( 'Upgrade Now →', 'anyapi' ); ?>
      </a>
      <p class="upgrade-modal__note">30-day money-back guarantee · Cancel anytime</p>
    </div>
  </div>

  <!-- ======================================================================
       Integration List
  ====================================================================== -->
  <div class="order-page-header">
    <div>
      <h1 class="order-page-title">🔗 <?php esc_html_e( 'Order Integrations', 'anyapi' ); ?></h1>
      <p class="order-page-desc"><?php esc_html_e( 'Connect WooCommerce orders to any REST API.', 'anyapi' ); ?></p>
    </div>
    <button id="new-integration-btn" class="oi-btn oi-btn--primary" type="button">
      + <?php esc_html_e( 'New Integration', 'anyapi' ); ?>
    </button>
  </div>

  <!-- Integration table (shown when list is non-empty) -->
  <div id="integration-list-wrap" class="<?php echo empty( $integrations_raw ) ? 'is-hidden' : ''; ?>">
    <table class="oi-table">
      <thead>
        <tr>
          <th><?php esc_html_e( 'Name', 'anyapi' ); ?></th>
          <th><?php esc_html_e( 'Trigger', 'anyapi' ); ?></th>
          <th><?php esc_html_e( 'API Endpoint', 'anyapi' ); ?></th>
          <th><?php esc_html_e( 'Filter', 'anyapi' ); ?></th>
          <th><?php esc_html_e( 'Status', 'anyapi' ); ?></th>
          <th><?php esc_html_e( 'Actions', 'anyapi' ); ?></th>
        </tr>
      </thead>
      <tbody id="integration-tbody">
        <?php foreach ( $integrations_raw as $rec ) :
          $trigger_label  = $trigger_labels[ $rec['trigger'] ?? '' ] ?? esc_html( $rec['trigger'] ?? '—' );
          $api_url_short  = strlen( $rec['api_url'] ?? '' ) > 40
                            ? substr( $rec['api_url'], 0, 40 ) . '…'
                            : ( $rec['api_url'] ?? '—' );
          $is_active      = ( ( $rec['status'] ?? 'active' ) === 'active' );
          $name_display   = $rec['name'] ?? 'Integration #' . $rec['id'];
          // Migrate: api_key_id may still be stored as api_key
          $key_id         = $rec['api_key_id'] ?? $rec['api_key'] ?? '';
          $key_name       = '';
          if ( str_starts_with( $key_id, 'ak_' ) && isset( $stored_keys[ $key_id ] ) ) {
            $key_name = $stored_keys[ $key_id ]['name'] ?? $key_id;
          }
        ?>
        <tr data-id="<?php echo esc_attr( $rec['id'] ); ?>"
            class="oi-row <?php echo $is_active ? '' : 'is-inactive'; ?>">
          <td class="oi-col-name">
            <strong><?php echo esc_html( $name_display ); ?></strong>
            <?php if ( $key_name ) : ?>
            <span class="oi-key-badge">🔑 <?php echo esc_html( $key_name ); ?></span>
            <?php endif; ?>
          </td>
          <td><?php echo esc_html( $trigger_label ); ?></td>
          <td class="oi-col-url">
            <code title="<?php echo esc_attr( $rec['api_url'] ?? '' ); ?>">
              <?php echo esc_html( $api_url_short ); ?>
            </code>
          </td>
          <td><?php echo esc_html( ucfirst( $rec['filter_mode'] ?? 'basic' ) ); ?></td>
          <td>
            <label class="oi-toggle" title="<?php esc_attr_e( 'Toggle active', 'anyapi' ); ?>">
              <input type="checkbox" class="oi-toggle__input js-toggle-integration"
                     data-id="<?php echo esc_attr( $rec['id'] ); ?>"
                     <?php checked( $is_active ); ?>>
              <span class="oi-toggle__track"></span>
            </label>
          </td>
          <td class="oi-col-actions">
            <button type="button" class="oi-btn oi-btn--sm oi-btn--ghost js-edit-integration"
                    data-id="<?php echo esc_attr( $rec['id'] ); ?>">
              ✏️ <?php esc_html_e( 'Edit', 'anyapi' ); ?>
            </button>
            <button type="button" class="oi-btn oi-btn--sm oi-btn--danger-ghost js-delete-integration"
                    data-id="<?php echo esc_attr( $rec['id'] ); ?>">
              🗑
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Empty state (shown when list is empty) -->
  <div id="integration-empty" class="oi-empty <?php echo ! empty( $integrations_raw ) ? 'is-hidden' : ''; ?>">
    <div class="oi-empty__icon">🔗</div>
    <h3><?php esc_html_e( 'No integrations yet', 'anyapi' ); ?></h3>
    <p><?php esc_html_e( 'Create your first integration to start sending order data to any API.', 'anyapi' ); ?></p>
    <button class="oi-btn oi-btn--primary js-open-wizard" type="button">
      + <?php esc_html_e( 'Create First Integration', 'anyapi' ); ?>
    </button>
  </div>

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
          <div class="panel-header">
            <h2><?php esc_html_e( 'API Endpoint & Authentication', 'anyapi' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Set up your API endpoint, authentication, and base payload.', 'anyapi' ); ?></p>
          </div>

          <!-- Integration name -->
          <div class="form-group">
            <label for="integration-name"><?php esc_html_e( 'Integration Name', 'anyapi' ); ?></label>
            <input type="text" id="integration-name" class="widefat"
                   placeholder="<?php esc_attr_e( 'e.g. Slack Order Notification', 'anyapi' ); ?>">
            <p class="help-text"><?php esc_html_e( 'Optional. Helps you identify this integration in the list.', 'anyapi' ); ?></p>
          </div>

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
              <a href="<?php echo esc_url( admin_url( 'admin.php?page=anyapi-apikey' ) ); ?>">
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
            <p class="description"><?php esc_html_e( 'Choose what order data gets sent in the API payload.', 'anyapi' ); ?></p>
          </div>

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
              <strong><?php esc_html_e( 'JSON Filter is a Lite feature', 'anyapi' ); ?></strong>
              <p><?php esc_html_e( 'Select exactly which order fields to send — keep payloads clean and reduce API errors.', 'anyapi' ); ?></p>
            </div>
            <a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener" class="btn-nudge-upgrade">
              <?php esc_html_e( 'Upgrade to Lite →', 'anyapi' ); ?>
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

</div><!-- /#anyapi-order-page -->
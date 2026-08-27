<?php
/**
 * order-api.php — Order API Integration wizard and list template.
 *
 * @package AnyApi
 */
if ( ! defined( 'ABSPATH' ) ) exit;

( new \Anyapi\Views\Dashboard() )->brandHeader();

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
      <h2 class="upgrade-modal__title" id="modal-title" data-default-title="<?php esc_attr_e( 'Available on Lite', 'anyapi' ); ?>"><?php esc_html_e( 'Available on Lite', 'anyapi' ); ?></h2>
      <p class="upgrade-modal__body" id="modal-body"></p>
      <div class="upgrade-modal__primary" id="modal-primary-action" hidden>
        <a href="#" class="oi-btn oi-btn--primary oi-btn--hero" id="modal-primary-link"></a>
      </div>
      <div class="upgrade-modal__plans">
        <div class="modal-plan modal-plan--current">
          <div class="modal-plan__badge"><?php esc_html_e( 'Current', 'anyapi' ); ?></div>
          <div class="modal-plan__name">Free</div>
          <div class="modal-plan__price">$0</div>
          <ul class="modal-plan__features">
            <li class="feat--yes">1 API Key</li>
            <li class="feat--yes">1 Email Destination</li>
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
            <li class="feat--yes">3 Email Destinations</li>
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
        <?php esc_html_e( 'See Lite pricing →', 'anyapi' ); ?>
      </a>
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
    <div class="order-page-header-actions">
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=anyapi_templates' ) ); ?>" class="oi-btn oi-btn--ghost" style="margin-right:8px;">🧩 <?php esc_html_e( 'Start from a template', 'anyapi' ); ?> →</a>
      <button id="new-integration-btn" class="oi-btn oi-btn--primary" type="button">
        + <?php esc_html_e( 'New Integration', 'anyapi' ); ?>
      </button>
    </div>
  </div>

  <!-- Integration table (shown when list is non-empty) -->
  <div id="integration-list-wrap" class="<?php echo empty( $integrations_raw ) ? 'is-hidden' : ''; ?>">
    <table class="oi-table">
      <thead>
        <tr>
          <th><?php esc_html_e( 'Name', 'anyapi' ); ?></th>
          <th><?php esc_html_e( 'Trigger', 'anyapi' ); ?></th>
          <th><?php esc_html_e( 'Key', 'anyapi' ); ?></th>
          <th><?php esc_html_e( 'Endpoint', 'anyapi' ); ?></th>
          <th><?php esc_html_e( 'Filter', 'anyapi' ); ?></th>
          <th><?php esc_html_e( 'Status', 'anyapi' ); ?></th>
          <th><?php esc_html_e( 'Actions', 'anyapi' ); ?></th>
        </tr>
      </thead>
      <tbody id="integration-tbody">
        <?php foreach ( $integrations_raw as $rec ) :
          $trigger_slug   = $rec['trigger'] ?? '';
          $trigger_label  = $trigger_labels[ $trigger_slug ] ?? esc_html( $trigger_slug ?: '—' );
          $trigger_desc   = $all_triggers[ $trigger_slug ]['desc'] ?? '';
          $api_url_short  = strlen( $rec['api_url'] ?? '' ) > 40
                            ? substr( $rec['api_url'], 0, 40 ) . '…'
                            : ( $rec['api_url'] ?? '—' );
          $is_active      = ( ( $rec['status'] ?? 'active' ) === 'active' );
          $name_display   = $rec['name'] ?? 'Integration #' . $rec['id'];
          $is_email       = ( ( $rec['destination_type'] ?? 'url' ) === 'email' );
          $filter_mode    = $rec['filter_mode'] ?? 'basic';
          // Migrate: api_key_id may still be stored as api_key
          $key_id         = $rec['api_key_id'] ?? $rec['api_key'] ?? '';
          $key_name       = '';
          $key_type       = '';
          if ( str_starts_with( $key_id, 'ak_' ) && isset( $stored_keys[ $key_id ] ) ) {
            $key_name = $stored_keys[ $key_id ]['name'] ?? $key_id;
            $key_type = $stored_keys[ $key_id ]['type'] ?? '';
          }
        ?>
        <tr data-id="<?php echo esc_attr( $rec['id'] ); ?>"
            class="oi-row <?php echo $is_active ? '' : 'is-inactive'; ?>">
          <td class="oi-col-name">
            <strong><?php echo esc_html( $name_display ); ?></strong>
            <?php if ( ( $rec['created_via'] ?? 'manual' ) === 'template' ) : ?>
            <span class="oi-source-badge">🧩 <?php esc_html_e( 'Template', 'anyapi' ); ?></span>
            <?php endif; ?>
          </td>
          <td><?php echo esc_html( $trigger_label ); ?></td>
          <td>
            <?php if ( $key_name ) : ?>
            <span class="oi-key-badge">🔑 <?php echo esc_html( $key_name ); ?></span>
            <?php else : ?>
            —
            <?php endif; ?>
          </td>
          <?php if ( $is_email ) : ?>
          <td class="oi-col-url">✉️ <?php echo esc_html( $rec['email_to'] ?? '' ); ?></td>
          <?php else : ?>
          <td class="oi-col-url">
            <code title="<?php echo esc_attr( $rec['api_url'] ?? '' ); ?>">
              <?php echo esc_html( $api_url_short ); ?>
            </code>
          </td>
          <?php endif; ?>
          <td><?php echo esc_html( ucfirst( $filter_mode ) ); ?></td>
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
                    data-id="<?php echo esc_attr( $rec['id'] ); ?>">✏️</button>
            <button type="button" class="oi-btn oi-btn--sm oi-btn--danger-ghost js-delete-integration"
                    data-id="<?php echo esc_attr( $rec['id'] ); ?>">
              🗑
            </button>
            <button type="button" class="oi-expand-btn js-expand-row"
                    data-id="<?php echo esc_attr( $rec['id'] ); ?>" aria-expanded="false">▶</button>
          </td>
        </tr>
        <tr class="oi-detail-row" id="oi-detail-<?php echo esc_attr( $rec['id'] ); ?>">
          <td class="oi-detail-cell" colspan="7">
            <div class="oi-detail-grid">
              <div class="oi-detail-item">
                <span class="oi-detail-item__label"><?php esc_html_e( 'Trigger', 'anyapi' ); ?></span>
                <span class="oi-detail-item__value"><?php echo esc_html( $trigger_label ); ?><?php echo $trigger_desc ? ' — ' . esc_html( $trigger_desc ) : ''; ?></span>
              </div>
              <div class="oi-detail-item">
                <span class="oi-detail-item__label"><?php esc_html_e( 'Method / Authentication', 'anyapi' ); ?></span>
                <span class="oi-detail-item__value">
                  <?php if ( $is_email ) : ?>
                    ✉️ <?php esc_html_e( 'Email delivery (no HTTP method, no auth)', 'anyapi' ); ?>
                  <?php else : ?>
                    <span class="oi-method-inline"><?php echo esc_html( $rec['http_method'] ?? 'POST' ); ?></span>
                    <?php echo $key_name
                      ? esc_html( $key_name ) . ' (' . esc_html( $key_type ) . ')'
                      : esc_html__( 'No authentication', 'anyapi' ); ?>
                  <?php endif; ?>
                </span>
              </div>
              <div class="oi-detail-item">
                <span class="oi-detail-item__label"><?php esc_html_e( 'Created', 'anyapi' ); ?></span>
                <span class="oi-detail-item__value"><?php echo esc_html( $rec['created_at'] ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $rec['created_at'] ) ) : '—' ); ?></span>
              </div>
              <?php if ( $is_email ) : ?>
              <div class="oi-detail-item">
                <span class="oi-detail-item__label"><?php esc_html_e( 'Subject', 'anyapi' ); ?></span>
                <span class="oi-detail-item__value"><?php echo esc_html( $rec['email_subject'] ?? '' ); ?></span>
              </div>
              <div class="oi-detail-item">
                <span class="oi-detail-item__label"><?php esc_html_e( 'Intro', 'anyapi' ); ?></span>
                <span class="oi-detail-item__value"><?php echo esc_html( $rec['email_preamble'] ?? '' ); ?></span>
              </div>
              <?php else : ?>
              <div class="oi-detail-item oi-detail-full">
                <span class="oi-detail-item__label"><?php esc_html_e( 'Full endpoint', 'anyapi' ); ?></span>
                <span class="oi-detail-item__value"><?php echo esc_html( $rec['api_url'] ?? '' ); ?></span>
              </div>
              <?php endif; ?>
              <div class="oi-detail-item oi-detail-full">
                <span class="oi-detail-item__label"><?php esc_html_e( 'Filter content', 'anyapi' ); ?></span>
                <?php if ( 'basic' === $filter_mode ) : ?>
                  <?php if ( '' !== trim( $rec['payload'] ?? '' ) ) : ?>
                  <pre><?php echo esc_html( $rec['payload'] ); ?></pre>
                  <?php else : ?>
                  <span class="oi-detail-item__value"><?php esc_html_e( 'No custom payload — the full WooCommerce order data is sent.', 'anyapi' ); ?></span>
                  <?php endif; ?>
                <?php elseif ( 'advanced' === $filter_mode ) : ?>
                <div class="oi-field-list">
                  <?php foreach ( ( $rec['selected_fields'] ?? array() ) as $field ) : ?>
                  <span class="oi-field-chip"><?php echo esc_html( $field ); ?></span>
                  <?php endforeach; ?>
                </div>
                <?php elseif ( 'expert' === $filter_mode ) : ?>
                <pre><?php echo esc_html( $rec['raw_json_override'] ?? '' ); ?></pre>
                <?php endif; ?>
              </div>
            </div>
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

  <?php require __DIR__ . '/partials/order-wizard.php'; ?>

</div><!-- /#anyapi-order-page -->
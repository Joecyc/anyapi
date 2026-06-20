<?php
/**
 * settings.php — Plan Overview + General Settings template.
 *
 * @package AnyApi
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Plan data ─────────────────────────────────────────────────────────────
$plan         = \Anyapi\PlanHelper::currentPlan();
$limits       = \Anyapi\PlanHelper::currentLimits();
$all_plans    = \Anyapi\PlanHelper::allPlans();
$is_free      = ( $plan === 'starter' );
$upgrade_url  = esc_url( $limits['upgrade_url'] );
$plan_label   = $limits['label'];

// ── Usage ─────────────────────────────────────────────────────────────────
$used_keys    = \Anyapi\PlanHelper::usedApiKeys();
$key_limit    = $limits['api_keys_limit'];
$key_limit_ui = $key_limit === PHP_INT_MAX ? '∞' : $key_limit;
$key_pct      = $key_limit !== PHP_INT_MAX && $key_limit > 0
                  ? min( 100, round( $used_keys / $key_limit * 100 ) ) : -1;

$used_calls   = \Anyapi\PlanHelper::usedCallsThisMonth();
$call_limit   = $limits['monthly_calls'];
$call_limit_ui = $call_limit === PHP_INT_MAX ? '∞' : number_format( $call_limit );
$call_pct     = $call_limit !== PHP_INT_MAX && $call_limit > 0
                  ? min( 100, round( $used_calls / $call_limit * 100 ) ) : -1;

// ── Plugin version ────────────────────────────────────────────────────────
$current_ver  = ANYAPI_VERSION;
$latest_info  = \Anyapi\Admin::getLatestVersion();
$has_update   = $latest_info && version_compare( $current_ver, $latest_info['version'], '<' );
$update_url   = \Anyapi\Admin::pluginUpdateUrl();

// ── Pro expiry (stored by Pro plugin, optional) ───────────────────────────
$pro_expiry   = get_option( 'anyapi_pro_expiry', '' );  // 'YYYY-MM-DD' or ''

// ── General Settings option ───────────────────────────────────────────────
$debug_mode      = get_option( 'anyapi_debug_mode', '0' );
$clean_uninstall = get_option( 'anyapi_clean_uninstall', '0' );

// ── Feature matrix definition ─────────────────────────────────────────────
$features = array(
  array(
    'icon'   => '🔗',
    'label'  => __( 'Order API Integration', 'anyapi' ),
    'desc'   => __( 'Connect WooCommerce orders to any REST API', 'anyapi' ),
    'plans'  => array( 'starter', 'lite', 'plus', 'agency' ),
    'note'   => '',
  ),
  array(
    'icon'   => '⚡',
    'label'  => __( 'Monthly API Calls', 'anyapi' ),
    'desc'   => __( 'Number of outgoing API calls per month', 'anyapi' ),
    'plans'  => array( 'starter', 'lite', 'plus', 'agency' ),
    'note'   => __( 'Starter: 500 calls (throttled after limit) · Lite+: Unlimited', 'anyapi' ),
  ),
  array(
    'icon'   => '⚡',
    'label'  => __( 'All Order Triggers', 'anyapi' ),
    'desc'   => __( 'Pending, On-Hold, Cancelled, Refunded, Failed…', 'anyapi' ),
    'plans'  => array( 'lite', 'plus', 'agency' ),
    'note'   => __( 'Starter: 3 triggers only (New Order, Processing, Completed)', 'anyapi' ),
  ),
  array(
    'icon'   => '🔑',
    'label'  => __( 'API Keys', 'anyapi' ),
    'desc'   => __( 'Manage Bearer / Basic Auth credentials', 'anyapi' ),
    'plans'  => array( 'starter', 'lite', 'plus', 'agency' ),
    'note'   => __( 'Starter: 1 key · Lite: 5 · Plus: 20 · Agency: ∞', 'anyapi' ),
  ),
  array(
    'icon'   => '🧪',
    'label'  => __( 'WC REST API Tester', 'anyapi' ),
    'desc'   => __( 'Test WooCommerce endpoints with live responses', 'anyapi' ),
    'plans'  => array( 'starter', 'lite', 'plus', 'agency' ),
    'note'   => '',
  ),
  array(
    'icon'   => '🔧',
    'label'  => __( 'JSON Filter', 'anyapi' ),
    'desc'   => __( 'Transform and filter payload fields', 'anyapi' ),
    'plans'  => array( 'lite', 'plus', 'agency' ),
    'note'   => '',
  ),
  array(
    'icon'   => '📋',
    'label'  => __( 'Real-time API Log', 'anyapi' ),
    'desc'   => __( 'View recent outgoing API calls', 'anyapi' ),
    'plans'  => array( 'starter', 'lite', 'plus', 'agency' ),
    'note'   => __( 'Starter: last 10 entries (read-only) · Lite+: Full log with search & filters', 'anyapi' ),
  ),
  array(
    'icon'   => '📊',
    'label'  => __( 'Log Search & Statistics', 'anyapi' ),
    'desc'   => __( 'Filter logs, export, and view success / error rates', 'anyapi' ),
    'plans'  => array( 'lite', 'plus', 'agency' ),
    'note'   => '',
  ),
  // pre-release
  // array(
  //   'icon'   => '📦',
  //   'label'  => __( 'Integration Templates', 'anyapi' ),
  //   'desc'   => __( 'Pre-built templates for popular services', 'anyapi' ),
  //   'plans'  => array( 'lite', 'plus', 'agency' ),
  //   'note'   => __( 'Lite: 3 · Plus & Agency: All', 'anyapi' ),
  // ),
  array(
    'icon'   => '📥',
    'label'  => __( 'Webhook Receiver', 'anyapi' ),
    'desc'   => __( 'Receive inbound webhooks from external APIs', 'anyapi' ),
    'plans'  => array( 'plus', 'agency' ),
    'note'   => __( 'Plus & Agency only', 'anyapi' ),
  ),
);
?>

<div id="anyapi-settings" class="wrap">

  <!-- ======================================================================
       Page header
       ====================================================================== -->
  <div class="st-header">
    <div>
      <h1 class="st-title">⚙️ <?php esc_html_e( 'Settings', 'anyapi' ); ?></h1>
      <p class="st-desc"><?php esc_html_e( 'Plan overview, usage, and plugin configuration.', 'anyapi' ); ?></p>
    </div>
  </div>

  <div class="st-layout">

    <!-- ====================================================================
         LEFT COLUMN
         ==================================================================== -->
    <div class="st-col st-col--main">

      <!-- ── Section 1: Plan Card ────────────────────────────────────────── -->
      <div class="st-card st-plan-card <?php echo 'is-' . esc_attr( $plan ); ?>">

        <div class="st-plan-card__top">
          <div class="st-plan-card__left">
            <span class="st-plan-badge"><?php echo esc_html( $plan_label ); ?></span>
            <h2 class="st-plan-card__name">
              <?php
              if ( $is_free ) {
                esc_html_e( 'Free Plan', 'anyapi' );
              } else {
                printf(
                  /* translators: %s = plan name */
                  esc_html__( 'AnyAPI %s', 'anyapi' ),
                  esc_html( $plan_label )
                );
              }
              ?>
            </h2>
            <?php if ( $pro_expiry && ! $is_free ) : ?>
              <p class="st-plan-card__expiry">
                <?php
                $exp_date   = new DateTime( $pro_expiry );
                $today      = new DateTime();
                $days_left  = (int) $today->diff( $exp_date )->days;
                $is_expiring = $days_left <= 30;
                ?>
                <span class="st-expiry-dot <?php echo $is_expiring ? 'is-warn' : 'is-ok'; ?>"></span>
                <?php
                printf(
                  /* translators: %s = date */
                  esc_html__( 'Renews %s', 'anyapi' ),
                  esc_html( $exp_date->format( 'M j, Y' ) )
                );
                if ( $is_expiring ) {
                  // translators: %d is the number of days until license expiry
                  echo ' — <strong>' . esc_html( sprintf( __( '%d days left', 'anyapi' ), $days_left ) ) . '</strong>';
                }
                ?>
              </p>
            <?php endif; ?>
          </div>

          <?php if ( $is_free ) : ?>
          <a href="<?php echo esc_url( $upgrade_url ); ?>" class="st-upgrade-btn" target="_blank" rel="noopener">
            🚀 <?php esc_html_e( 'Upgrade to Lite — $79/yr', 'anyapi' ); ?>
          </a>
          <?php endif; ?>
        </div>

        <?php if ( $is_free ) : ?>
        <!-- Starter upgrade pitch -->
        <div class="st-plan-card__pitch">
          <p class="st-plan-card__pitch-text">
            <?php esc_html_e( 'Upgrade to unlock full API Logs, all order triggers, JSON Filter, and unlimited monthly calls (no throttle delay).', 'anyapi' ); ?>
          </p>
          <div class="st-plan-compare">
            <?php
            $compare_features = array(
              array( 'label' => __( 'Monthly Calls', 'anyapi' ), 'free' => '500',         'lite' => '∞',            'note' => __( 'Throttled after limit', 'anyapi' ) ),
              array( 'label' => __( 'API Keys',      'anyapi' ), 'free' => '1',           'lite' => '5',            'note' => '' ),
              array( 'label' => __( 'Triggers',      'anyapi' ), 'free' => '3',           'lite' => __( 'All', 'anyapi' ), 'note' => '' ),
              array( 'label' => __( 'JSON Filter',   'anyapi' ), 'free' => '✗',           'lite' => '✓',            'note' => '' ),
              array( 'label' => __( 'Real-time Log', 'anyapi' ), 'free' => '10 entries',  'lite' => __( 'Full + Search', 'anyapi' ), 'note' => '' ),
            );
            foreach ( $compare_features as $cf ) : ?>
            <div class="st-plan-compare__row">
              <span class="st-plan-compare__feature"><?php echo esc_html( $cf['label'] ); ?></span>
              <span class="st-plan-compare__free"><?php echo esc_html( $cf['free'] ); ?></span>
              <span class="st-plan-compare__arrow">→</span>
              <span class="st-plan-compare__lite"><?php echo esc_html( $cf['lite'] ); ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /.st-plan-card -->

      <!-- ── Section 2: Usage Meters ────────────────────────────────────── -->
      <div class="st-card">
        <div class="st-card__header">
          <h3 class="st-card__title">📊 <?php esc_html_e( 'Usage', 'anyapi' ); ?></h3>
          <span class="st-card__subtitle"><?php esc_html_e( 'This month', 'anyapi' ); ?></span>
        </div>
        <div class="st-card__body">

          <!-- Monthly Calls -->
          <div class="st-meter">
            <div class="st-meter__header">
              <span class="st-meter__label"><?php esc_html_e( 'Monthly API Calls', 'anyapi' ); ?></span>
              <span class="st-meter__value">
                <?php
                if ( $call_pct === -1 ) {
                  echo '<span class="st-meter__unlimited">∞ ' . esc_html__( 'Unlimited', 'anyapi' ) . '</span>';
                } else {
                  echo '<strong>' . number_format( $used_calls ) . '</strong> / ' . esc_html( $call_limit_ui );
                }
                ?>
              </span>
            </div>
            <?php if ( $call_pct !== -1 ) : ?>
            <div class="st-meter__bar">
              <div class="st-meter__fill <?php echo $call_pct >= 100 ? 'is-throttling' : ( $call_pct >= 80 ? 'is-warn' : '' ); ?>"
                   style="width:<?php echo esc_attr( $call_pct ); ?>%"></div>
            </div>
            <?php if ( $call_pct >= 80 ) : ?>
            <p class="st-meter__hint <?php echo $call_pct >= 100 ? 'is-throttling' : 'is-warn'; ?>">
              <?php
              if ( $call_pct >= 100 ) {
                esc_html_e( 'Monthly limit reached. Integrations will fire with a 30-second delay via WP Cron.', 'anyapi' );
              } else {
                esc_html_e( 'Approaching limit. After 500 calls, integrations are throttled (30s delay).', 'anyapi' );
              }
              ?>
              <?php if ( $is_free ) : ?>
              <a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Remove delay →', 'anyapi' ); ?></a>
              <?php endif; ?>
            </p>
            <?php endif; ?>
            <?php endif; ?>
          </div>

          <!-- API Keys -->
          <div class="st-meter">
            <div class="st-meter__header">
              <span class="st-meter__label"><?php esc_html_e( 'API Keys', 'anyapi' ); ?></span>
              <span class="st-meter__value">
                <?php
                if ( $key_pct === -1 ) {
                  echo '<strong>' . esc_html( $used_keys ) . '</strong> <span class="st-meter__unlimited">/ ∞</span>';
                } else {
                  echo '<strong>' . esc_html( $used_keys ) . '</strong> / ' . esc_html( $key_limit_ui );
                }
                ?>
              </span>
            </div>
            <?php if ( $key_pct !== -1 ) : ?>
            <div class="st-meter__bar">
              <div class="st-meter__fill <?php echo $key_pct >= 100 ? 'is-full' : ( $key_pct >= 80 ? 'is-warn' : '' ); ?>"
                   style="width:<?php echo esc_attr( $key_pct ); ?>%"></div>
            </div>
            <?php endif; ?>
            <p class="st-meter__hint">
              <a href="<?php echo esc_url( admin_url( 'admin.php?page=anyapi-apikey' ) ); ?>">
                <?php esc_html_e( 'Manage API Keys →', 'anyapi' ); ?>
              </a>
            </p>
          </div>

        </div>
      </div><!-- /.st-card usage -->

      <!-- ── Section 3: Feature Matrix ─────────────────────────────────── -->
      <div class="st-card">
        <div class="st-card__header">
          <h3 class="st-card__title">✅ <?php esc_html_e( 'Features', 'anyapi' ); ?></h3>
          <span class="st-plan-badge st-plan-badge--sm"><?php echo esc_html( $plan_label ); ?></span>
        </div>
        <div class="st-card__body st-card__body--flush">
          <div class="st-feature-list">
            <?php foreach ( $features as $feat ) :
              $unlocked = in_array( $plan, $feat['plans'], true );
            ?>
            <div class="st-feature-row <?php echo $unlocked ? 'is-unlocked' : 'is-locked'; ?>">
              <span class="st-feature-row__icon"><?php echo esc_html( $feat['icon'] ); ?></span>
              <div class="st-feature-row__body">
                <span class="st-feature-row__label"><?php echo esc_html( $feat['label'] ); ?></span>
                <?php if ( $feat['note'] ) : ?>
                <span class="st-feature-row__note"><?php echo esc_html( $feat['note'] ); ?></span>
                <?php endif; ?>
              </div>
              <div class="st-feature-row__status">
                <?php if ( $unlocked ) : ?>
                  <span class="st-check">✓</span>
                <?php else : ?>
                  <a href="<?php echo esc_url( $upgrade_url ); ?>" class="st-lock-cta" target="_blank" rel="noopener" title="<?php esc_attr_e( 'Upgrade to unlock', 'anyapi' ); ?>">
                    🔒 <?php esc_html_e( 'Upgrade', 'anyapi' ); ?>
                  </a>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div><!-- /.st-card features -->

    </div><!-- /.st-col--main -->

    <!-- ====================================================================
         RIGHT COLUMN
         ==================================================================== -->
    <div class="st-col st-col--side">

      <!-- ── Plugin Info ─────────────────────────────────────────────────── -->
      <div class="st-card">
        <div class="st-card__header">
          <h3 class="st-card__title">🔌 <?php esc_html_e( 'Plugin Info', 'anyapi' ); ?></h3>
        </div>
        <div class="st-card__body">

          <div class="st-info-row">
            <span class="st-info-row__label"><?php esc_html_e( 'Version', 'anyapi' ); ?></span>
            <span class="st-info-row__value st-mono"><?php echo esc_html( $current_ver ); ?></span>
          </div>

          <div class="st-info-row">
            <span class="st-info-row__label"><?php esc_html_e( 'Status', 'anyapi' ); ?></span>
            <span class="st-info-row__value">
              <?php if ( $has_update ) : ?>
                <span class="st-badge st-badge--warn">
                  <?php
                  // translators: %s is the new plugin version number e.g. 2.1.0
                  printf( esc_html__( 'v%s available', 'anyapi' ), esc_html( $latest_info['version'] ) ); ?>
                </span>
              <?php elseif ( $latest_info ) : ?>
                <span class="st-badge st-badge--ok"><?php esc_html_e( 'Up to date', 'anyapi' ); ?></span>
              <?php else : ?>
                <span class="st-badge st-badge--muted"><?php esc_html_e( 'Unknown', 'anyapi' ); ?></span>
              <?php endif; ?>
            </span>
          </div>

          <?php if ( $has_update ) : ?>
          <div class="st-update-box">
            <p class="st-update-box__title">
              🆕 <?php
              // translators: %s is the new plugin version number e.g. 2.1.0
              printf( esc_html__( 'AnyAPI %s is available', 'anyapi' ), esc_html( $latest_info['version'] ) ); ?>
            </p>
            <?php if ( ! empty( $latest_info['changes'] ) ) : ?>
            <ul class="st-update-box__changes">
              <?php foreach ( array_slice( $latest_info['changes'], 0, 4 ) as $change ) : ?>
              <li><?php echo esc_html( $change ); ?></li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <a href="<?php echo esc_url( $update_url ); ?>" class="st-btn st-btn--primary st-btn--sm">
              ⬆ <?php esc_html_e( 'Update Now', 'anyapi' ); ?>
            </a>
          </div>
          <?php endif; ?>

          <div class="st-info-row">
            <span class="st-info-row__label"><?php esc_html_e( 'Released', 'anyapi' ); ?></span>
            <span class="st-info-row__value st-mono"><?php echo esc_html( ANYAPI_RELEASE_DATE ); ?></span>
          </div>

          <div class="st-info-links">
            <a href="https://anyapiplugin.com/documentation" target="_blank" rel="noopener">📖 Docs</a>
            <a href="https://wordpress.org/support/plugin/anyapi/" target="_blank" rel="noopener">💬 Support</a>
            <a href="https://wordpress.org/support/plugin/anyapi/reviews/#new-post" target="_blank" rel="noopener">⭐ Review</a>
          </div>

        </div>
      </div><!-- /.st-card plugin-info -->

      <!-- ── General Settings ───────────────────────────────────────────── -->
      <div class="st-card">
        <div class="st-card__header">
          <h3 class="st-card__title">🛠 <?php esc_html_e( 'General Settings', 'anyapi' ); ?></h3>
        </div>
        <div class="st-card__body">

          <form method="post" action="options.php" id="st-general-form">
            <?php wp_nonce_field( 'anyapi_general_settings', 'anyapi_general_nonce' ); ?>
            <input type="hidden" name="action" value="anyapi_save_general_settings">

            <!-- Debug mode -->
            <div class="st-setting-row">
              <div class="st-setting-row__info">
                <label class="st-setting-row__label" for="st-debug">
                  <?php esc_html_e( 'Debug Mode', 'anyapi' ); ?>
                </label>
                <p class="st-setting-row__desc">
                  <?php esc_html_e( 'Log extra info to WP debug.log for troubleshooting.', 'anyapi' ); ?>
                </p>
              </div>
              <label class="st-toggle">
                <input type="checkbox" id="st-debug" name="anyapi_debug_mode" value="1"
                  <?php checked( $debug_mode, '1' ); ?>>
                <span class="st-toggle__track"></span>
              </label>
            </div>

            <!-- Clean uninstall -->
            <div class="st-setting-row st-setting-row--danger">
              <div class="st-setting-row__info">
                <label class="st-setting-row__label" for="st-clean">
                  <?php esc_html_e( 'Remove Data on Uninstall', 'anyapi' ); ?>
                </label>
                <p class="st-setting-row__desc">
                  <?php esc_html_e( 'Delete all plugin data (API Keys, logs, settings) when plugin is uninstalled.', 'anyapi' ); ?>
                </p>
              </div>
              <label class="st-toggle">
                <input type="checkbox" id="st-clean" name="anyapi_clean_uninstall" value="1"
                  <?php checked( $clean_uninstall, '1' ); ?>>
                <span class="st-toggle__track"></span>
              </label>
            </div>

            <div class="st-form-footer">
              <span class="st-form-status" id="st-form-status"></span>
              <button type="button" class="st-btn st-btn--primary st-btn--sm" id="st-save-btn">
                <?php esc_html_e( 'Save Settings', 'anyapi' ); ?>
              </button>
            </div>

          </form>

        </div>
      </div><!-- /.st-card general-settings -->

      <!-- ── Quick nav ─────────────────────────────────────────────────── -->
      <div class="st-card st-quick-nav">
        <div class="st-card__header">
          <h3 class="st-card__title">🧭 <?php esc_html_e( 'Quick Navigation', 'anyapi' ); ?></h3>
        </div>
        <div class="st-card__body st-card__body--flush">
          <?php
          $nav_links = array(
            array( 'icon' => '🔗', 'label' => __( 'Order Integrations', 'anyapi' ), 'url' => admin_url( 'admin.php?page=anyapi_orderapi' ) ),
            array( 'icon' => '🔑', 'label' => __( 'API Keys', 'anyapi' ),           'url' => admin_url( 'admin.php?page=anyapi_apikey' ) ),
            array( 'icon' => '🧪', 'label' => __( 'REST API Tester', 'anyapi' ),    'url' => admin_url( 'admin.php?page=anyapi_restapi' ) ),
            array( 'icon' => '📋', 'label' => __( 'API Logs', 'anyapi' ),           'url' => admin_url( 'admin.php?page=anyapi_apilog' ) ),
          );
          foreach ( $nav_links as $link ) : ?>
          <a href="<?php echo esc_url( $link['url'] ); ?>" class="st-nav-link">
            <span class="st-nav-link__icon"><?php echo esc_html( $link['icon'] ); ?></span>
            <span class="st-nav-link__label"><?php echo esc_html( $link['label'] ); ?></span>
            <span class="st-nav-link__arrow">→</span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

    </div><!-- /.st-col--side -->

  </div><!-- /.st-layout -->

</div><!-- /#anyapi-settings -->
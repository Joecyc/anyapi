<?php
/**
 * api-log.php — API Log page template.
 *
 * @package AnyApi
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$plan        = \Anyapi\PlanHelper::currentPlan();
$limits      = \Anyapi\PlanHelper::currentLimits();
$is_starter  = ( $plan === 'starter' );
$upgrade_url = esc_url( $limits['upgrade_url'] );
?>

<div id="anyapi-logs" class="wrap" data-plan="<?php echo esc_attr( $plan ); ?>">

  <!-- ======================================================================
       Page Header
       ====================================================================== -->
  <div class="al-header">
    <div class="al-header__left">
      <h1 class="al-title">📋 <?php esc_html_e( 'API Logs', 'anyapi' ); ?></h1>
      <p class="al-desc">
        <?php if ( $is_starter ) : ?>
          <?php esc_html_e( 'Real-time log of your last 10 API calls. Upgrade to Lite for full history, search, and analytics.', 'anyapi' ); ?>
        <?php else : ?>
          <?php esc_html_e( 'Monitor all outgoing API calls triggered by WooCommerce orders.', 'anyapi' ); ?>
        <?php endif; ?>
      </p>
    </div>
  </div>

  <?php if ( $is_starter ) : ?>
  <!-- ======================================================================
       Starter — Static 10-row real-time log + blurred stats
       ====================================================================== -->

  <!-- Stats row: blurred (Lite+ feature) -->
  <div class="al-stats al-stats--locked" id="al-stats">
    <div class="al-stat-card">
      <span class="al-stat-card__value">—</span>
      <span class="al-stat-card__label"><?php esc_html_e( 'Total Logs', 'anyapi' ); ?></span>
    </div>
    <div class="al-stat-card al-stat-card--success">
      <span class="al-stat-card__value">—</span>
      <span class="al-stat-card__label"><?php esc_html_e( 'Successful (2xx)', 'anyapi' ); ?></span>
    </div>
    <div class="al-stat-card al-stat-card--warn">
      <span class="al-stat-card__value">—</span>
      <span class="al-stat-card__label"><?php esc_html_e( 'Client Errors (4xx)', 'anyapi' ); ?></span>
    </div>
    <div class="al-stat-card al-stat-card--danger">
      <span class="al-stat-card__value">—</span>
      <span class="al-stat-card__label"><?php esc_html_e( 'Server Errors (5xx)', 'anyapi' ); ?></span>
    </div>
    <!-- Blur overlay -->
    <div class="al-stats-blur-overlay">
      <a href="<?php echo esc_url( $upgrade_url ); ?>" class="al-upgrade-cta" target="_blank" rel="noopener">
        📊 <?php esc_html_e( 'See your success rate → Upgrade to Lite', 'anyapi' ); ?>
      </a>
    </div>
  </div>

  <!-- Toolbar: search + filter locked for Starter -->
  <div class="al-toolbar">
    <div class="al-search-wrap al-search-wrap--locked">
      <!-- <span class="al-search-icon">🔍</span> -->
      <input
        type="text"
        class="al-search"
        placeholder="<?php esc_attr_e( 'Search Order ID, URL, payload…', 'anyapi' ); ?>"
        readonly
        aria-label="<?php esc_attr_e( 'Search locked — upgrade to Lite', 'anyapi' ); ?>"
      >
      <a href="<?php echo esc_url( $upgrade_url ); ?>" class="al-search-lock-badge" target="_blank" rel="noopener">
        🔒 <?php esc_html_e( 'Lite+', 'anyapi' ); ?>
      </a>
    </div>
    <div class="al-filters al-filters--locked" role="group" aria-label="Filter by status">
      <button class="al-filter-btn active" data-status="" type="button" disabled><?php esc_html_e( 'All', 'anyapi' ); ?></button>
      <button class="al-filter-btn" data-status="2xx" type="button" disabled>2xx</button>
      <button class="al-filter-btn" data-status="4xx" type="button" disabled>4xx</button>
      <button class="al-filter-btn" data-status="5xx" type="button" disabled>5xx</button>
    </div>
  </div>

  <!-- Static real-time log: last 10 entries -->
  <div class="al-table-card al-table-card--starter">

    <?php
    $recent_logs = \Anyapi\Admin::getRecentLogs( 10 );
    ?>

    <?php if ( empty( $recent_logs ) ) : ?>
    <div class="al-empty" style="display:flex;">
      <div class="al-empty__icon">📭</div>
      <h3 class="al-empty__title"><?php esc_html_e( 'No API calls yet', 'anyapi' ); ?></h3>
      <p class="al-empty__desc"><?php esc_html_e( 'Logs appear here once your integrations start firing.', 'anyapi' ); ?></p>
    </div>

    <?php else : ?>

    <div class="al-starter-log-header">
      <span class="al-starter-log-title"><?php esc_html_e( 'REAL-TIME API LOG', 'anyapi' ); ?></span>
      <span class="al-starter-log-note">
        <?php esc_html_e( 'Last 10 calls', 'anyapi' ); ?> &mdash;
        <a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener">
          <?php esc_html_e( 'Upgrade for full log', 'anyapi' ); ?> &rarr;
        </a>
      </span>
    </div>

    <div class="al-table-wrap">
      <table class="al-table anyapi-log-table" id="al-table">
        <thead>
          <tr>
            <th class="al-th--order"><?php esc_html_e( 'Order', 'anyapi' ); ?></th>
            <th class="al-th--status"><?php esc_html_e( 'Status', 'anyapi' ); ?></th>
            <th class="al-th--trigger"><?php esc_html_e( 'Trigger', 'anyapi' ); ?></th>
            <th class="al-th--url"><?php esc_html_e( 'Endpoint', 'anyapi' ); ?></th>
            <th class="al-th--latency"><?php esc_html_e( 'Latency', 'anyapi' ); ?></th>
            <th class="al-th--time"><?php esc_html_e( 'Time', 'anyapi' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $recent_logs as $row ) :
            $http_code  = (int) ( $row['http_code'] ?? 0 );
            $status_cls = $http_code >= 500 ? 'is-5xx'
                        : ( $http_code >= 400 ? 'is-4xx'
                        : ( $http_code >= 200 ? 'is-2xx' : 'is-other' ) );
            $trigger    = $row['trigger'] ?? '—';
            // Endpoint: parse path only
            $endpoint   = isset( $row['api_url'] )
                          ? wp_parse_url( $row['api_url'], PHP_URL_PATH )
                          : '—';
            $latency    = isset( $row['latency'] )
                          ? esc_html( $row['latency'] ) . 'ms'
                          : '—';
            // Short time format: HH:MM:SS from timestamp
            $time_str   = isset( $row['timestamp'] )
                          ? substr( $row['timestamp'], 11, 8 )
                          : '—';
          ?>
          <tr>
            <td><?php echo isset( $row['order_id'] ) ? '#' . esc_html( $row['order_id'] ) : '—'; ?></td>
            <td>
              <span class="al-status-badge <?php echo esc_attr( $status_cls ); ?>">
                <?php echo $http_code ? esc_html( $http_code ) : '—'; ?>
              </span>
            </td>
            <td class="al-td--trigger">
              <span class="al-trigger-badge">
                <?php echo esc_html( $trigger ); ?>
              </span>
            </td>
            <td class="al-ep" title="<?php echo esc_attr( $row['api_url'] ?? '' ); ?>">
              <?php echo esc_html( $endpoint ?: '/' ); ?>
            </td>
            <td><?php echo esc_html( $latency ); ?></td>
            <td class="al-td--time"><?php echo esc_html( $time_str ); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div><!-- /.al-table-wrap -->

    <?php endif; // empty $recent_logs ?>

    <!-- Upgrade nudge bar -->
    <div class="al-starter-upgrade-bar">
      <span class="al-starter-upgrade-bar__text">
        🔒 <?php esc_html_e( 'Showing last 10 calls only. Full history, search & analytics on Lite+.', 'anyapi' ); ?>
      </span>
      <a href="<?php echo esc_url( $upgrade_url ); ?>" class="al-btn al-btn--primary al-btn--sm" target="_blank" rel="noopener">
        ⚡ <?php esc_html_e( 'Upgrade to Lite', 'anyapi' ); ?> &rarr;
      </a>
    </div>

  </div><!-- /.al-table-card --starter -->

  <?php else : ?>
  <!-- ======================================================================
       LITE+ — Full AJAX Log UI (provided by anyapi-lite plugin)
       ====================================================================== -->
  <?php
    // Lite+ Log UI is now rendered by anyapi-lite plugin via action hook.
    do_action( 'anyapi_render_log_ui', $plan, $limits );

    // Fallback if Lite not active (should not happen — plan gate above)
    if ( ! did_action( 'anyapi_render_log_ui' ) ) {
      echo '<p>' . esc_html__( 'Full API Log UI requires AnyAPI Lite plugin.', 'anyapi' ) . '</p>';
    }
  ?>
  <?php endif; ?>
</div><!-- /#anyapi-logs -->
<?php
/**
 * Dashboard.php
 *
 * Dashboard view: header, stats, log section, version card.
 *
 * @package AnyApi
 */

namespace Anyapi\Views;

use Anyapi\Admin;
use Anyapi\Anyapi;
use Anyapi\PlanHelper;

class Dashboard {

  // ── Header ─────────────────────────────────────────────────────────────────

  public function dashboardHeader( $args ) {
    ?>
    <?php self::showBanner(); ?>

    <div id="anyapi-dashboard-header">
      <div class="anyapi-header-left">
        <span class="anyapi-logo-name">Any<span>API</span></span>
      </div>
      <div class="anyapi-header-right">
        <button type="button" id="dark-mode-toggle" class="dark-mode-toggle"
                aria-label="<?php esc_attr_e( 'Toggle dark mode', 'anyapi' ); ?>">
          <div class="dm-track" aria-hidden="true">
            <span class="dm-track-icon dm-sun">☀️</span>
            <span class="dm-track-icon dm-moon">🌙</span>
          </div>
          <div class="dm-thumb" aria-hidden="true">
            <span class="dm-thumb-icon">☀️</span>
          </div>
        </button>
      </div>
    </div>
    <?php
  }

  // ── Review Banner ──────────────────────────────────────────────────────────

  public function showBanner() {

    if ( ! Admin::shouldShowBanner() ) return;

    $log_count   = Admin::getLogCount();
    $active_days = Admin::activatedDay();
    ?>
    <div id="anyapi-review-banner" class="anyapi-review-banner">

      <svg class="banner-circuit" viewBox="0 0 280 160" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <rect x="20"  y="20"  width="8" height="8" rx="2" fill="white"/>
        <rect x="80"  y="60"  width="8" height="8" rx="2" fill="white"/>
        <rect x="160" y="20"  width="8" height="8" rx="2" fill="white"/>
        <rect x="220" y="80"  width="8" height="8" rx="2" fill="white"/>
        <rect x="140" y="120" width="8" height="8" rx="2" fill="white"/>
        <line x1="24"  y1="24"  x2="80"  y2="64"  stroke="white" stroke-width="1.5"/>
        <line x1="84"  y1="64"  x2="160" y2="24"  stroke="white" stroke-width="1.5"/>
        <line x1="164" y1="24"  x2="220" y2="84"  stroke="white" stroke-width="1.5"/>
        <line x1="220" y1="84"  x2="144" y2="120" stroke="white" stroke-width="1.5"/>
        <circle cx="28"  cy="28"  r="3" fill="white" opacity="0.6"/>
        <circle cx="164" cy="28"  r="3" fill="white" opacity="0.6"/>
        <circle cx="224" cy="88"  r="3" fill="white" opacity="0.6"/>
      </svg>

      <button class="banner-close" id="anyapi-review-close" type="button"
              aria-label="<?php esc_attr_e( 'Dismiss banner', 'anyapi' ); ?>">&times;</button>

      <div class="banner-body">
        <div class="banner-icon" aria-hidden="true">✨</div>
        <div class="banner-text">
          <div class="banner-title"><?php esc_html_e( 'Enjoying AnyAPI?', 'anyapi' ); ?></div>
          <div class="banner-sub">
            <?php printf(
              /* translators: %s: days active */
              esc_html__( "You've been automating WooCommerce orders for %s days now.", 'anyapi' ),
              '<strong style="color:#fff">' . esc_html( $active_days ) . '</strong>'
            ); ?>
          </div>
          <div class="banner-stat">
            <span class="banner-stat-dot"></span>
            <span><?php printf(
              /* translators: %s: automations count */
              esc_html__( '%s automations fired this month', 'anyapi' ),
              '<strong style="color:#4ade80">' . esc_html( number_format( $log_count ) ) . '</strong>'
            ); ?></span>
          </div>
          <div class="banner-stars" aria-label="<?php esc_attr_e( '5 stars', 'anyapi' ); ?>">⭐⭐⭐⭐⭐</div>
        </div>
        <div class="banner-actions">
          <a href="https://wordpress.org/support/plugin/anyapi/reviews/#new-post"
             id="anyapi-review-yes" class="btn-review" target="_blank" rel="noopener">
            🌟 <?php esc_html_e( 'Leave a Review', 'anyapi' ); ?>
          </a>
          <button id="anyapi-review-dismiss" class="btn-later" type="button">
            <?php esc_html_e( 'Ask me later', 'anyapi' ); ?>
          </button>
        </div>
      </div>

    </div>
    <?php
  }

  // ── Dashboard Content ──────────────────────────────────────────────────────

  public function dashboardContent( $args ) {

    $plan       = PlanHelper::currentPlan();
    $limits     = PlanHelper::currentLimits();
    $used_calls = PlanHelper::usedCallsThisMonth();

    $throttle_threshold = $limits['monthly_calls'];
    $has_threshold      = ( $throttle_threshold !== PHP_INT_MAX && $throttle_threshold > 0 );
    $throttle_pct       = $has_threshold ? round( ( $used_calls / $throttle_threshold ) * 100 ) : -1;

    $alert_state = 'none';
    if ( $plan === 'starter' && $has_threshold ) {
      if ( $throttle_pct >= 100 )     $alert_state = 'throttling';
      elseif ( $throttle_pct >= 80 )  $alert_state = 'approaching';
    }
    ?>
    <div id="anyapi-dashboard" class="wrap">

      <div class="anyapi-dash-topbar">
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Dashboard', 'anyapi' ); ?></h1>
        <span id="anyapi-last-updated">
          <?php esc_html_e( 'Last update:', 'anyapi' ); ?>
          <span id="update-time"><?php esc_html_e( 'Just a moment', 'anyapi' ); ?></span>
        </span>
      </div>

      <?php if ( $alert_state !== 'none' ) : ?>
      <div class="anyapi-usage-alert <?php echo esc_attr( 'is-' . $alert_state ); ?>">

        <div class="alert-icon-wrap" aria-hidden="true">
          <?php echo $alert_state === 'throttling' ? '🔄' : '⚡'; ?>
        </div>

        <div class="alert-body">
          <?php if ( $alert_state === 'throttling' ) : ?>
            <div class="alert-title">
              <?php esc_html_e( 'Calls Are Being Throttled', 'anyapi' ); ?>
              <span class="throttle-badge badge-throttle">THROTTLING</span>
            </div>
            <div class="alert-sub">
              <span class="cron-dot"></span>
              <?php esc_html_e( 'API calls still firing — queued & sent via WP Cron (~30s delay)', 'anyapi' ); ?>
            </div>
          <?php else : ?>
            <div class="alert-title">
              <?php esc_html_e( 'Approaching Throttle Threshold', 'anyapi' ); ?>
              <span class="throttle-badge badge-approach"><?php echo esc_html( $throttle_pct ); ?>%</span>
            </div>
            <div class="alert-sub">
              <?php esc_html_e( 'After 500 calls, new requests queue ~30s before firing', 'anyapi' ); ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="alert-meter">
          <div class="meter-wrap">
            <div class="meter-bar">
              <div class="meter-fill" style="width:<?php echo esc_attr( min( 100, $throttle_pct ) ); ?>%"></div>
            </div>
            <span class="meter-label">
              <?php if ( $alert_state === 'throttling' ) :
                // translators: %s is the number of API calls this month
                printf( esc_html__( '%s calls · throttle active', 'anyapi' ), esc_html( number_format( $used_calls ) ) );
              else :
                // translators: %1$s is the used calls count, %2$s is the throttle threshold
                printf( esc_html__( '%1$s / %2$s throttle threshold', 'anyapi' ), esc_html( number_format( $used_calls ) ), esc_html( number_format( $throttle_threshold ) ) );
              endif; ?>
            </span>
          </div>
        </div>

        <a href="<?php echo esc_url( $limits['upgrade_url'] ); ?>" class="btn-upgrade-sm" target="_blank" rel="noopener">
          <?php echo $alert_state === 'throttling'
            ? esc_html__( 'Remove delay →', 'anyapi' )
            : esc_html__( 'Upgrade →', 'anyapi' ); ?>
        </a>

      </div>
      <?php endif; ?>

      <div class="anyapi-main-content">
        <?php self::versionCard(); ?>
        <?php self::statusCard(); ?>
        <?php self::chartCard(); ?>
        <?php self::logSection(); ?>
      </div>

    </div>
    <?php
  }

  // ── Status Cards ───────────────────────────────────────────────────────────

  public function statusCard() {

    $todayCalls      = Admin::getLog( 'todaycall' );
    $ytdCalls        = Admin::getLog( 'ytdcall' );
    $callsChange     = $ytdCalls > 0
      ? round( ( ( $todayCalls - $ytdCalls ) / $ytdCalls ) * 100, 1 )
      : ( $todayCalls > 0 ? 100 : 0 );
    $todaySuccess    = Admin::getLog( 'rate' );
    $successRate     = $todayCalls > 0 ? round( ( $todaySuccess / $todayCalls ) * 100, 2 ) : 0;
    $avgLatency      = (int) round( Admin::getLog( 'latency' ) ?: 0 );
    $activeEndpoints = Admin::getLog( 'endpoints' ) ?: 0;
    ?>
    <div class="anyapi-grid">

      <div class="anyapi-card stat-card">
        <div class="stat-card-label"><?php esc_html_e( 'API Calls Today', 'anyapi' ); ?></div>
        <div class="stat-card-value"><?php echo number_format( $todayCalls ); ?></div>
        <div class="stat-chip <?php echo $callsChange >= 0 ? 'chip-up' : 'chip-down'; ?>">
          <?php echo $callsChange >= 0 ? '▲' : '▼'; ?>
          <?php echo esc_html( abs( $callsChange ) ); ?>%
          <?php esc_html_e( 'vs yesterday', 'anyapi' ); ?>
        </div>
      </div>

      <div class="anyapi-card stat-card">
        <div class="stat-card-label"><?php esc_html_e( 'Success Rate Today', 'anyapi' ); ?></div>
        <div class="stat-card-value <?php echo $successRate >= 90 ? 'success' : ( $successRate >= 70 ? 'warn' : 'danger' ); ?>">
          <?php echo esc_html( $successRate ); ?>%
        </div>
        <div class="stat-chip <?php echo $successRate >= 90 ? 'chip-up' : 'chip-warn'; ?>">
          <?php echo $successRate >= 90
            ? '✓ ' . esc_html__( 'Healthy', 'anyapi' )
            : '⚠ ' . esc_html__( 'Check errors', 'anyapi' ); ?>
        </div>
      </div>

      <div class="anyapi-card stat-card">
        <div class="stat-card-label"><?php esc_html_e( 'Avg Latency', 'anyapi' ); ?></div>
        <div class="stat-card-value"><?php echo number_format( $avgLatency ); ?> ms</div>
        <div class="stat-chip <?php echo $avgLatency < 1800 ? 'chip-up' : 'chip-warn'; ?>">
          <?php echo $avgLatency < 1800
            ? '🚀 ' . esc_html__( 'Fast', 'anyapi' )
            : '🐢 ' . esc_html__( 'Slow', 'anyapi' ); ?>
        </div>
      </div>

      <div class="anyapi-card stat-card">
        <div class="stat-card-label"><?php esc_html_e( 'Active Endpoints', 'anyapi' ); ?></div>
        <div class="stat-card-value"><?php echo esc_html( $activeEndpoints ); ?></div>
        <div class="stat-chip chip-mute"><?php esc_html_e( 'Unique URLs', 'anyapi' ); ?></div>
      </div>

    </div>
    <?php
  }

  // ── Chart Card ─────────────────────────────────────────────────────────────

  public function chartCard(): void {

    // ── Data ──────────────────────────────────────────────────────────────────
    $weekly = Admin::getWeeklyCallData( 7 );
    $dist   = Admin::getErrorDistribution();

    $bar_count  = count( $weekly );
    $bar_vals   = array_values( $weekly );
    $bar_dates  = array_keys( $weekly );
    $bar_max    = max( array_merge( $bar_vals, array( 1 ) ) );
    $svg_w      = 420;
    $svg_h      = 148;
    $bar_gap    = 10;
    $bar_w      = ( $svg_w - ( $bar_gap * ( $bar_count + 1 ) ) ) / $bar_count;
    $val_area_h = 14;
    $lbl_area_h = 18;
    $bar_area_h = $svg_h - $val_area_h - $lbl_area_h;
    $bar_scale  = 0.82; // tallest bar = 82% height, never clips card edge

    $donut_r      = 44;
    $donut_cx     = 60;
    $donut_cy     = 60;
    $donut_stroke = 17;
    $donut_total  = max( $dist['total'], 1 );
    $donut_segments = array(
      array( 'key' => '2xx', 'color' => '#00c48c', 'label' => '2xx' ),
      array( 'key' => '4xx', 'color' => '#f59e0b', 'label' => '4xx' ),
      array( 'key' => '5xx', 'color' => '#ff6b6b', 'label' => '5xx' ),
    );
    $circ = 2 * M_PI * $donut_r;
    ?>

    <div class="anyapi-charts-row">

      <!-- ── Bar Chart: Calling Trends ── -->
      <div class="anyapi-chart-card">

        <div class="chart-header">
          <span class="chart-title"><?php esc_html_e( 'Calling Trends — Past 7 Days', 'anyapi' ); ?></span>
          <span class="chart-total"><?php printf(
            /* translators: %s = formatted number */
            esc_html__( '%s calls', 'anyapi' ),
            '<strong>' . esc_html( number_format( array_sum( $bar_vals ) ) ) . '</strong>'
          ); ?></span>
        </div>

        <?php if ( array_sum( $bar_vals ) === 0 ) : ?>
          <div class="chart-empty">
            <span>&#128202;</span>
            <span><?php esc_html_e( 'No API calls in the past 7 days.', 'anyapi' ); ?></span>
          </div>
        <?php else : ?>
          <div class="chart-bar-wrap" role="img" aria-label="<?php esc_attr_e( 'Bar chart: API calls past 7 days', 'anyapi' ); ?>">
            <svg class="chart-svg" viewBox="0 0 <?php echo esc_attr( $svg_w ); ?> <?php echo esc_attr( $svg_h ); ?>" preserveAspectRatio="xMidYMax meet">
              <defs>
                <linearGradient id="anyapi-bar-grad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%"   stop-color="var(--anyapi-primary)" stop-opacity="1"/>
                  <stop offset="100%" stop-color="var(--anyapi-primary)" stop-opacity="0.4"/>
                </linearGradient>
                <linearGradient id="anyapi-bar-grad-today" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%"   stop-color="var(--anyapi-primary)" stop-opacity="1"/>
                  <stop offset="100%" stop-color="var(--anyapi-primary-hover)" stop-opacity="0.7"/>
                </linearGradient>
              </defs>
              <?php foreach ( $bar_vals as $i => $val ) :
                $x      = $bar_gap + $i * ( $bar_w + $bar_gap );
                $bar_h  = $bar_area_h * ( $val / $bar_max ) * $bar_scale;
                $y      = $val_area_h + ( $bar_area_h - $bar_h );
                $date   = $bar_dates[ $i ];
                $label  = gmdate( 'M j', strtotime( $date ) );
                $is_today = ( $date === gmdate( 'Y-m-d' ) );
              ?>
                <rect
                  x="<?php echo esc_html( round( $x, 1 ) ); ?>"
                  y="<?php echo esc_html( round( $y, 1 ) ); ?>"
                  width="<?php echo esc_html( round( $bar_w, 1 ) ); ?>"
                  height="<?php echo esc_html( round( $bar_h, 1 ) ); ?>"
                  rx="5" ry="5"
                  fill="<?php echo $is_today ? 'url(#anyapi-bar-grad-today)' : 'url(#anyapi-bar-grad)'; ?>"
                  class="chart-bar<?php echo $is_today ? ' chart-bar--today' : ''; ?>"
                  data-val="<?php echo esc_attr( $val ); ?>"
                  data-date="<?php echo esc_attr( $date ); ?>">
                  <title><?php echo esc_html( $label . ': ' . number_format( $val ) . ' calls' ); ?></title>
                </rect>
                <?php if ( $val > 0 ) : ?>
                <text
                  x="<?php echo esc_html( round( $x + $bar_w / 2, 1 ) ); ?>"
                  y="<?php echo esc_html( round( $y - 4, 1 ) ); ?>"
                  class="chart-bar-val"
                  text-anchor="middle">
                  <?php echo esc_html( $val >= 1000 ? round( $val / 1000, 1 ) . 'k' : $val ); ?>
                </text>
                <?php endif; ?>
                <text
                  x="<?php echo esc_html( round( $x + $bar_w / 2, 1 ) ); ?>"
                  y="<?php echo esc_attr( $svg_h - 3 ); ?>"
                  class="chart-bar-date<?php echo $is_today ? ' chart-bar-date--today' : ''; ?>"
                  text-anchor="middle">
                  <?php echo esc_html( $label ); ?>
                </text>
              <?php endforeach; ?>
            </svg>
          </div>
        <?php endif; ?>

      </div><!-- /bar chart -->

      <!-- ── Donut Chart: Error Distribution ── -->
      <div class="anyapi-chart-card">

        <div class="chart-header">
          <span class="chart-title"><?php esc_html_e( 'Error Type Distribution', 'anyapi' ); ?></span>
          <span class="chart-total"><?php
            printf(
              // translators: %s is the total number of API calls recorded
              esc_html__( '%s total', 'anyapi' ),
              '<strong>' . esc_html( number_format( $dist['total'] ) ) . '</strong>'
            ); ?></span>
        </div>

        <?php if ( $dist['total'] === 0 ) : ?>
          <div class="chart-empty">
            <span>&#9989;</span>
            <span><?php esc_html_e( 'No calls recorded yet.', 'anyapi' ); ?></span>
          </div>
        <?php else : ?>
          <div class="chart-donut-wrap">

            <svg class="chart-donut-svg" viewBox="0 0 120 120"
                 role="img" aria-label="<?php esc_attr_e( 'Donut chart: error distribution', 'anyapi' ); ?>">
              <circle cx="<?php echo esc_attr( $donut_cx ); ?>" cy="<?php echo esc_attr( $donut_cy ); ?>"
                      r="<?php echo esc_attr( $donut_r ); ?>"
                      fill="none" stroke="var(--anyapi-border)"
                      stroke-width="<?php echo esc_attr( $donut_stroke ); ?>"/>
              <?php
                $offset = 0;
                // Start from 12 o'clock (rotate -90deg via transform)
                foreach ( $donut_segments as $seg ) :
                  $count = $dist[ $seg['key'] ];
                  if ( $count === 0 ) continue;
                  $pct  = $count / $donut_total;
                  $dash = $pct * $circ;
                  $gap  = $circ - $dash;
              ?>
              <circle cx="<?php echo esc_attr( $donut_cx ); ?>" cy="<?php echo esc_attr( $donut_cy ); ?>"
                      r="<?php echo esc_attr( $donut_r ); ?>"
                      fill="none"
                      stroke="<?php echo esc_attr( $seg['color'] ); ?>"
                      stroke-width="<?php echo esc_attr( $donut_stroke ); ?>"
                      stroke-dasharray="<?php echo esc_html( round( $dash, 2 ) . ' ' . round( $gap, 2 ) ); ?>"
                      stroke-dashoffset="<?php echo esc_html( round( $circ * ( 0.25 - $offset ), 2 ) ); ?>"
                      stroke-linecap="butt">
                <title><?php echo esc_html( $seg['label'] . ': ' . number_format( $count ) . ' (' . round( $pct * 100, 1 ) . '%)' ); ?></title>
              </circle>
              <?php
                  $offset += $pct;
                endforeach;
              ?>
              <?php
                $success_pct = $dist['total'] > 0 ? round( ( $dist['2xx'] / $dist['total'] ) * 100, 1 ) : 0;
              ?>
              <text x="<?php echo esc_attr( $donut_cx ); ?>" y="<?php echo esc_attr( $donut_cy - 4 ); ?>"
                    class="donut-centre-val" text-anchor="middle">
                <?php echo esc_html( $success_pct ); ?>%
              </text>
              <text x="<?php echo esc_attr( $donut_cx ); ?>" y="<?php echo esc_attr( $donut_cy + 9 ); ?>"
                    class="donut-centre-lbl" text-anchor="middle">
                <?php esc_html_e( 'success', 'anyapi' ); ?>
              </text>
            </svg>

            <div class="chart-donut-legend">
              <?php foreach ( $donut_segments as $seg ) :
                $count = $dist[ $seg['key'] ];
                $pct   = $dist['total'] > 0 ? round( ( $count / $dist['total'] ) * 100, 1 ) : 0;
              ?>
              <div class="donut-legend-row">
                <div class="donut-legend-dot" style="background:<?php echo esc_attr( $seg['color'] ); ?>;"></div>
                <div class="donut-legend-body">
                  <span class="donut-legend-label"><?php echo esc_html( $seg['label'] ); ?></span>
                  <span class="donut-legend-pct" style="color:<?php echo esc_attr( $seg['color'] ); ?>;"><?php echo esc_html( $pct ); ?>%</span>
                  <span class="donut-legend-count">(<?php echo esc_html( number_format( $count ) ); ?>)</span>
                </div>
              </div>
              <?php endforeach; ?>
            </div>

          </div><!-- /chart-donut-wrap -->
        <?php endif; ?>

      </div><!-- /donut chart -->

    </div><!-- /anyapi-charts-row -->
    <?php
  }

  // ── Log Section ────────────────────────────────────────────────────────────

  public function logSection() {

    $plan        = PlanHelper::currentPlan();
    $upgrade_url = admin_url( 'admin.php?page=anyapi_settings#plan' );

    if ( $plan === 'starter' ) {

      // ── Starter: static 10 rows (read-only) ──────────────────────────────
      $recent_logs = Admin::getRecentLogs( 10 );
      ?>
      <div id="anyapi-logs-section" class="anyapi-card">

        <div class="al-log-header">
          <div class="al-log-header-left">
            <strong class="al-log-title"><?php esc_html_e( 'Real-Time API Log', 'anyapi' ); ?></strong>
            <span class="al-log-limit-note">
              <?php esc_html_e( 'Last 10 calls', 'anyapi' ); ?> &mdash;
              <a href="<?php echo esc_url( $upgrade_url ); ?>"><?php esc_html_e( 'Upgrade for full log', 'anyapi' ); ?></a>
            </span>
          </div>
          <div class="al-search-locked" tabindex="0" role="button"
               aria-label="<?php esc_attr_e( 'Search requires Lite', 'anyapi' ); ?>"
               onclick="this.closest('#anyapi-logs-section').querySelector('.al-search-prompt').style.display='flex';this.style.display='none';">
            <span class="al-placeholder-text"><?php esc_html_e( 'Search logs…', 'anyapi' ); ?></span>
            <span class="al-lock-chip">&#128274; Lite</span>
          </div>
        </div>

        <div class="al-search-prompt" style="display:none;">
          <span aria-hidden="true">&#128269;</span>
          <span>
            <?php esc_html_e( 'Search across all logs with Lite', 'anyapi' ); ?> &mdash;
            <a href="<?php echo esc_url( $upgrade_url ); ?>" class="al-prompt-link">$79/yr &rarr;</a>
          </span>
          <button type="button" class="al-prompt-close"
                  onclick="this.closest('.al-search-prompt').style.display='none';this.closest('#anyapi-logs-section').querySelector('.al-search-locked').style.display='';"
                  aria-label="<?php esc_attr_e( 'Close', 'anyapi' ); ?>">&times;</button>
        </div>

        <div class="al-table-wrap">
          <table id="anyapi-logs-table" class="anyapi-log-table al-table">
            <thead>
              <tr>
                <th><?php esc_html_e( 'Time', 'anyapi' ); ?></th>
                <th><?php esc_html_e( 'Method', 'anyapi' ); ?></th>
                <th><?php esc_html_e( 'Endpoint', 'anyapi' ); ?></th>
                <th><?php esc_html_e( 'Status', 'anyapi' ); ?></th>
                <th><?php esc_html_e( 'Latency', 'anyapi' ); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if ( empty( $recent_logs ) ) : ?>
                <tr>
                  <td colspan="5" style="text-align:center;padding:32px;color:var(--anyapi-text-muted);">
                    <?php esc_html_e( 'No API logs yet. Trigger a WooCommerce order to see activity.', 'anyapi' ); ?>
                  </td>
                </tr>
              <?php else : ?>
                <?php foreach ( $recent_logs as $row ) :
                  $code      = (int) $row['http_code'];
                  $badge_cls = ( $code >= 200 && $code < 300 ) ? 'ok' : ( ( $code >= 400 && $code < 500 ) ? 'warn' : 'err' );
                  $latency   = ! empty( $row['latency'] ) ? number_format( (int) $row['latency'] ) . ' ms' : '—';
                  $method    = strtoupper( $row['method'] ?? 'POST' );
                  $ts_raw    = $row['timestamp'] ?? '';
                  $ts_short  = ( strlen( $ts_raw ) > 10 ) ? substr( $ts_raw, 11, 8 ) : $ts_raw;
                  $ep_full   = $row['api_url'] ?? '—';
                  $ep_path   = wp_parse_url( $ep_full, PHP_URL_PATH ) ?: $ep_full;
                ?>
                <tr>
                  <td class="al-time"><?php echo esc_html( $ts_short ); ?></td>
                  <td><span class="al-method"><?php echo esc_html( $method ); ?></span></td>
                  <td class="al-ep" title="<?php echo esc_attr( $ep_full ); ?>"><?php echo esc_html( $ep_path ); ?></td>
                  <td><span class="al-code <?php echo esc_attr( $badge_cls ); ?>"><?php echo esc_html( $code ?: '—' ); ?></span></td>
                  <td><?php echo esc_html( $latency ); ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="al-stats-locked">
          <div class="al-fake-stats" aria-hidden="true">
            <div class="al-fake-stat">
              <span class="al-fake-lbl"><?php esc_html_e( '2xx Success', 'anyapi' ); ?></span>
              <span class="al-fake-val" style="color:var(--anyapi-success);">80%</span>
            </div>
            <div class="al-fake-stat">
              <span class="al-fake-lbl"><?php esc_html_e( '4xx Client Error', 'anyapi' ); ?></span>
              <span class="al-fake-val" style="color:var(--anyapi-warn);">10%</span>
            </div>
            <div class="al-fake-stat">
              <span class="al-fake-lbl"><?php esc_html_e( '5xx Server Error', 'anyapi' ); ?></span>
              <span class="al-fake-val" style="color:var(--anyapi-danger);">10%</span>
            </div>
          </div>
          <div class="al-blur-overlay">
            <a href="<?php echo esc_url( $upgrade_url ); ?>" class="al-upgrade-btn">
              <?php esc_html_e( 'See your success rate → Upgrade to Lite', 'anyapi' ); ?>
            </a>
          </div>
        </div>

      </div>
      <?php
      return;
    }

    // ── Lite+: full AJAX log UI ────────────────────────────────────────────
    ?>
    <div id="anyapi-logs-section" class="anyapi-card">

      <div id="anyapi-logs-header">
        <h2><?php esc_html_e( 'Real-Time API Log', 'anyapi' ); ?></h2>
        <div class="logs-controls">
          <input type="text" id="anyapi-log-search"
                 placeholder="<?php esc_attr_e( 'Search endpoint, payload, order ID…', 'anyapi' ); ?>"
                 autocomplete="off">
          <select id="anyapi-status-filter" aria-label="<?php esc_attr_e( 'Filter by status', 'anyapi' ); ?>">
            <option value=""><?php esc_html_e( 'All Status', 'anyapi' ); ?></option>
            <option value="2xx"><?php esc_html_e( 'Success (2xx)', 'anyapi' ); ?></option>
            <option value="4xx"><?php esc_html_e( 'Client Error (4xx)', 'anyapi' ); ?></option>
            <option value="5xx"><?php esc_html_e( 'Server Error (5xx)', 'anyapi' ); ?></option>
          </select>
          <button id="anyapi-log-refresh" class="button" type="button"
                  aria-label="<?php esc_attr_e( 'Refresh logs', 'anyapi' ); ?>">
            <span class="refresh-icon" aria-hidden="true">🔄</span>
            <?php esc_html_e( 'Refresh', 'anyapi' ); ?>
          </button>
        </div>
      </div>

      <div class="table-container">
        <table id="anyapi-logs-table" class="anyapi-log-table">
          <thead>
            <tr>
              <th class="column-time"><?php esc_html_e( 'Time', 'anyapi' ); ?></th>
              <th class="column-method"><?php esc_html_e( 'Method', 'anyapi' ); ?></th>
              <th class="column-endpoint"><?php esc_html_e( 'Endpoint', 'anyapi' ); ?></th>
              <th class="column-status"><?php esc_html_e( 'Status', 'anyapi' ); ?></th>
              <th class="column-latency"><?php esc_html_e( 'Latency', 'anyapi' ); ?></th>
              <th class="column-payload"><?php esc_html_e( 'Payload', 'anyapi' ); ?></th>
            </tr>
          </thead>
          <tbody id="anyapi-logs-body">
            <tr><td colspan="6" class="loading"><?php esc_html_e( 'Loading logs…', 'anyapi' ); ?></td></tr>
          </tbody>
        </table>
      </div>

      <div id="anyapi-logs-footer">
        <div id="logs-pagination" class="tablenav-pages"></div>
        <div id="logs-info"></div>
      </div>

    </div>
    <?php
  }

  // ── Version Bar (full-width) ───────────────────────────────────────────────

  public function versionCard(): void {

    $currentVer = ANYAPI_VERSION;
    $latestVer  = Admin::getLatestVersion();
    $has_update = Admin::pluginVersionCheck( $currentVer );

    // Latest version — show nothing, keep dashboard clean
    if ( ! $has_update && $latestVer ) return;
    ?>

    <div class="anyapi-version-bar <?php echo $has_update ? 'anyapi-version-bar--update' : 'anyapi-version-bar--warn'; ?>">

      <?php if ( $has_update ) : ?>

        <!-- ── New version available ── -->
        <div class="avb-icon" aria-hidden="true">🔄</div>
        <div class="avb-body">
          <div class="avb-title">
            <?php
              printf(
                /* translators: %s = new version number e.g. 2.1.0 */
                esc_html__( 'AnyAPI %s is available', 'anyapi' ),
                '<strong>' . esc_html( $latestVer['version'] ) . '</strong>'
              );
            ?>
            <span class="avb-date"><?php echo esc_html( $latestVer['date'] ?? '' ); ?></span>
          </div>
          <?php if ( ! empty( $latestVer['changes'] ) ) : ?>
            <ul class="avb-changes">
              <?php foreach ( array_slice( $latestVer['changes'], 0, 3 ) as $change ) : ?>
                <li><?php echo esc_html( $change ); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
        <div class="avb-actions">
          <a href="<?php echo esc_url( Admin::pluginUpdateUrl() ); ?>"
             class="button button-primary button-small">
            <?php
              /* translators: %s = version number */
              printf( esc_html__( 'Update to v%s', 'anyapi' ), esc_html( $latestVer['version'] ) );
            ?>
          </a>
          <a href="https://wordpress.org/plugins/anyapi/#developers"
             target="_blank" rel="noopener" class="avb-details-link">
            <?php esc_html_e( 'View changelog →', 'anyapi' ); ?>
          </a>
        </div>

      <?php else : ?>

        <!-- ── Version check failed / unreachable ── -->
        <div class="avb-icon" aria-hidden="true">⚠️</div>
        <div class="avb-body">
          <span class="avb-title">
            <?php esc_html_e( 'Could not check for updates. Verify your connection or', 'anyapi' ); ?>
            <a href="https://anyapiplugin.com/changelog" target="_blank" rel="noopener">
              <?php esc_html_e( 'view changelog →', 'anyapi' ); ?>
            </a>
          </span>
        </div>

      <?php endif; ?>

    </div>
    <?php
  }

  // ── Static Helpers (backward compat for settings-apikey.php) ──────────────

  public static function fields(): array {
    return array(
      'keyId'       => array(
        'id' => array(
          'title'       => 'Name',
          'placeholder' => 'ID of Automation (Webhook)',
          'tooltip'     => 'Unique name for identifying this Automation',
        ),
      ),
      'basicAuth'   => array(
        'basicKey'    => array( 'title' => 'Consumer Key',    'placeholder' => 'Username', 'tooltip' => 'Basic Auth Consumer Key' ),
        'basicSecret' => array( 'title' => 'Consumer Secret', 'placeholder' => 'Password', 'tooltip' => 'Basic Auth Consumer Secret' ),
      ),
      'bearerToken' => array(
        'bearerToken' => array( 'title' => 'Token', 'placeholder' => 'Token', 'tooltip' => 'Bearer Token for protected API' ),
      ),
    );
  }

  public static function getAuth( array $option, string $auth ): bool {
    return isset( $option['authType'] ) && $option['authType'] === $auth;
  }

}

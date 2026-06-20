<?php
/**
 * PlanHelper.php
 *
 * Starter stub — always returns plan 'starter'. Skipped when anyapi-lite is active (class_exists guard).
 *
 * @package AnyApi
 */

namespace Anyapi;

if ( class_exists( __NAMESPACE__ . '\\PlanHelper' ) ) {
	return; // Lite's full version already loaded — skip stub
}

class PlanHelper {

	// ── Constants ────────────────────────────────────────────────────────

	const PLAN_OPTION      = 'anyapi_pro_plan';
	const PLAN_HASH_OPTION = 'anyapi_pro_plan_hash';

	const STARTER_TRIGGERS = array(
		'watch_orders',
		'watch_new_orders',
		'watch_processing_order',
	);

	// ── Plan detection ───────────────────────────────────────────────────

	public static function currentPlan(): string {
		return 'starter';
	}

	public static function currentLimits(): array {
		$plans = self::allPlans();
		return array_merge( $plans['starter'], array( 'plan' => 'starter' ) );
	}

	// ── Plan matrix (for Settings UI comparison table) ───────────────────

	public static function allPlans(): array {
		return array(
			'starter' => array(
				'label'              => 'Free',
				'api_keys_limit'     => 1,
				'monthly_calls'      => 500,
				'json_filter'        => false,
				'all_triggers'       => false,
				'api_logs'           => false,
				'templates'          => 0,
				'webhook_inbound'    => false,
				'sites_limit'        => 1,
				'upgrade_url'        => 'https://anyapiplugin.com/pricing',
				'allowed_triggers'   => self::STARTER_TRIGGERS,
			),
			'lite' => array(
				'label'              => 'Lite',
				'api_keys_limit'     => 5,
				'monthly_calls'      => PHP_INT_MAX,
				'json_filter'        => true,
				'all_triggers'       => true,
				'api_logs'           => true,
				'templates'          => 3,
				'webhook_inbound'    => false,
				'sites_limit'        => 1,
				'upgrade_url'        => 'https://anyapiplugin.com/pricing',
				'allowed_triggers'   => array(),
			),
			'plus' => array(
				'label'              => 'Plus',
				'api_keys_limit'     => 20,
				'monthly_calls'      => PHP_INT_MAX,
				'json_filter'        => true,
				'all_triggers'       => true,
				'api_logs'           => true,
				'templates'          => PHP_INT_MAX,
				'webhook_inbound'    => true,
				'sites_limit'        => 3,
				'upgrade_url'        => 'https://anyapiplugin.com/pricing',
				'allowed_triggers'   => array(),
			),
			'agency' => array(
				'label'              => 'Agency',
				'api_keys_limit'     => PHP_INT_MAX,
				'monthly_calls'      => PHP_INT_MAX,
				'json_filter'        => true,
				'all_triggers'       => true,
				'api_logs'           => true,
				'templates'          => PHP_INT_MAX,
				'webhook_inbound'    => true,
				'sites_limit'        => PHP_INT_MAX,
				'upgrade_url'        => '',
				'allowed_triggers'   => array(),
			),
		);
	}

	// ── API Key count helper ─────────────────────────────────────────────

	public static function usedApiKeys(): int {
		$keys = get_option( 'anyapi_wc_apikey', array() );
		return is_array( $keys ) ? count( $keys ) : 0;
	}

	// ── Usage tracking ───────────────────────────────────────────────────

	public static function usedCallsThisMonth(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'anyapi_log_anyapi';

		// Guard: table may not exist yet
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM `{$table}` WHERE MONTH(timestamp) = MONTH(CURRENT_DATE()) AND YEAR(timestamp) = YEAR(CURRENT_DATE())" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		return $count;
	}

	// ── Throttle ─────────────────────────────────────────────────────────

	public static function canFire( string $trigger = '', int $order_id = 0, string $integration_id = '' ): bool {
		$limits = self::currentLimits();
		$used   = self::usedCallsThisMonth();

		if ( $limits['monthly_calls'] !== PHP_INT_MAX && $used >= $limits['monthly_calls'] ) {
			// Schedule WP Cron retry if not already scheduled
			if ( $order_id && $integration_id && ! wp_next_scheduled( 'anyapi_throttled_fire', array( $order_id, $integration_id ) ) ) {
				wp_schedule_single_event( time() + 30, 'anyapi_throttled_fire', array( $order_id, $integration_id ) );
			}
			return false;
		}

		// Check trigger whitelist via filter (Lite can extend via anyapi_allowed_triggers)
		$allowed = apply_filters( 'anyapi_allowed_triggers', self::STARTER_TRIGGERS );
		if ( $allowed !== null && ! in_array( $trigger, (array) $allowed, true ) ) {
			return false;
		}

		return true;
	}

	// ── Stub no-ops (Lite overrides these) ───────────────────────────────

	public static function setPlan( string $plan ): void {
		// No-op in Starter stub. Lite's full PlanHelper handles this.
	}

	public static function clearPlan(): void {
		// No-op in Starter stub. Lite's full PlanHelper handles this.
	}

	// ── JS data helper ───────────────────────────────────────────────────

	public static function getJsData(): array {
		$limits = self::currentLimits();
		return array(
			'plan'             => 'starter',
			'monthly_calls'    => $limits['monthly_calls'],
			'calls_used'       => self::usedCallsThisMonth(),
			'api_keys_limit'   => $limits['api_keys_limit'],
			'all_triggers'     => false,
			'json_filter'      => false,
			'allowed_triggers' => $limits['allowed_triggers'],
			'upgrade_url'      => $limits['upgrade_url'],
		);
	}

}

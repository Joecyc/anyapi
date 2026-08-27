<?php
/**
 * Templates.php
 *
 * Integration Templates view: template catalogue, context, and JS presets.
 *
 * @package AnyApi
 */

namespace Anyapi\Views;

if ( ! defined( 'ABSPATH' ) ) exit;

class Templates {

	// ── Catalogue ────────────────────────────────────────────────────────

	public static function all(): array {

		$defaults = array(
			'email' => array(
				'id'      => 'email',
				'icon'    => '✉️',
				'tier'    => 'starter',
				'locked'  => false,
				'name'    => __( 'Email Order Notification', 'anyapi' ),
				'desc'    => __( 'Automatically email an order summary — items, weight, dimensions and total — to your courier or logistics company on every new order.', 'anyapi' ),
				'presets' => array(
					'default' => array(
						'name'        => __( 'Order Details to Courier', 'anyapi' ),
						'subject'     => __( 'New order #{{order_id}} — shipping details', 'anyapi' ),
						'preamble'    => __( 'Please arrange shipping for the following order:', 'anyapi' ) . "\n\n" . '{{order_summary}}',
						'trigger'     => 'watch_processing_order',
						'destination' => 'email',
					),
					'custom' => array(
						'name'        => __( 'Order Email', 'anyapi' ),
						'subject'     => __( 'New order #{{order_id}} — shipping details', 'anyapi' ),
						'preamble'    => '',
						'trigger'     => 'watch_processing_order',
						'destination' => 'email',
					),
				),
			),
			'slack' => array(
				'id'           => 'slack',
				'icon'         => '💬',
				'tier'         => 'lite',
				'locked'       => true,
				'name'         => __( 'Slack Order Notification', 'anyapi' ),
				'desc'         => __( 'Push new orders to a Slack channel in real time with a formatted summary card, so your team sees every sale instantly.', 'anyapi' ),
				'presets'      => array(),
				'lock_message' => __( 'Slack Order Notification is coming soon. It will push new orders to a channel with a formatted summary card.', 'anyapi' ),
			),
			'sheets' => array(
				'id'           => 'sheets',
				'icon'         => '📊',
				'tier'         => 'lite',
				'locked'       => true,
				'name'         => __( 'Google Sheets Order Log', 'anyapi' ),
				'desc'         => __( 'Append every order as a row in a Google Sheet — perfect for reporting, reconciliation, and connecting third-party analytics tools.', 'anyapi' ),
				'presets'      => array(),
				'lock_message' => __( 'Google Sheets Order Log is coming soon. It will append every order as a row in a spreadsheet.', 'anyapi' ),
			),
			'courier-api' => array(
				'id'           => 'courier-api',
				'icon'         => '🚚',
				'tier'         => 'lite',
				'locked'       => true,
				'name'         => __( 'Courier / Logistics API', 'anyapi' ),
				'desc'         => __( "Send orders straight to your courier or logistics provider's REST API — a structured, automated handoff with no manual email step.", 'anyapi' ),
				'presets'      => array(),
				'lock_message' => __( "Courier / Logistics API is coming soon. It will send orders straight to your provider's REST API.", 'anyapi' ),
			),
		);

		$defaults = array_filter( $defaults, static function ( $t ) {
			return empty( $t['locked'] );
		} );

		return apply_filters( 'anyapi_templates', $defaults );
	}

	// ── Context (for template + JS localize) ────────────────────────────

	public static function context(): array {
		return array(
			'templates'     => self::all(),
			'upgrade_url'   => \Anyapi\PlanHelper::currentLimits()['upgrade_url'],
			'order_api_url' => admin_url( 'admin.php?page=anyapi_orderapi' ),
		);
	}

	// ── JS presets (for order-api.js prefill) ────────────────────────────

	public static function getPresetsForJs(): array {
		$out = array();
		foreach ( self::all() as $id => $t ) {
			if ( empty( $t['locked'] ) && ! empty( $t['presets'] ) ) {
				$out[ $id ] = $t['presets'];
			}
		}
		return $out;
	}

}

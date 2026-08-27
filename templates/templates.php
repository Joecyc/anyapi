<?php
/**
 * templates.php — Integration Templates catalogue and prefill preview.
 *
 * @package AnyApi
 */
if ( ! defined( 'ABSPATH' ) ) exit;

( new \Anyapi\Views\Dashboard() )->brandHeader();

$ctx       = \Anyapi\Views\Templates::context();
$plan      = \Anyapi\PlanHelper::currentPlan();
$templates = $ctx['templates'];
$unlocked_count = count( array_filter( $templates, fn( $t ) => empty( $t['locked'] ) ) );
$total_count    = count( $templates );
?>

<div id="anyapi-templates-page" class="wrap">

	<!-- ======================================================================
	     Upgrade Modal (reused markup — styled by style.css)
	====================================================================== -->
	<div id="anyapi-upgrade-modal" class="upgrade-modal" role="dialog" aria-modal="true"
	     aria-labelledby="modal-title" style="display:none;">
		<div class="upgrade-modal__backdrop"></div>
		<div class="upgrade-modal__box">
			<button class="upgrade-modal__close" type="button" aria-label="Close">×</button>
			<div class="upgrade-modal__icon">🚀</div>
			<h2 class="upgrade-modal__title" id="modal-title"><?php esc_html_e( 'Coming soon', 'anyapi' ); ?></h2>
			<p class="upgrade-modal__body" id="modal-body"></p>
		</div>
	</div>

	<!-- ======================================================================
	     Page Header
	====================================================================== -->
	<div class="tpl-page-header">
		<div>
			<h1 class="tpl-page-title">🧩 <?php esc_html_e( 'Integration Templates', 'anyapi' ); ?></h1>
			<p class="tpl-page-desc"><?php esc_html_e( 'Start in one click. Skip manual setup — pick a template and the wizard opens prefilled.', 'anyapi' ); ?></p>
		</div>
		<div class="tpl-tier-note">
			<?php
			printf(
				/* translators: %1$s: plan label, %2$d: unlocked template count, %3$d: total template count */
				esc_html__( 'Current plan: %1$s — %2$d of %3$d templates available.', 'anyapi' ),
				'<strong>' . esc_html( ucfirst( $plan ) ) . '</strong>',
				(int) $unlocked_count,
				(int) $total_count
			);
			if ( $unlocked_count < $total_count ) {
				echo ' ' . esc_html__( 'The rest are coming soon.', 'anyapi' );
			}
			?>
		</div>
	</div>

	<!-- ======================================================================
	     Template Grid
	====================================================================== -->
	<div class="tpl-grid">
		<?php foreach ( $templates as $t ) : ?>
		<div class="tpl-card<?php echo ! empty( $t['locked'] ) ? ' is-locked' : ''; ?>"
		     data-tpl="<?php echo esc_attr( $t['id'] ); ?>"
		     <?php if ( ! empty( $t['locked'] ) ) : ?>
		     data-lock-message="<?php echo esc_attr( $t['lock_message'] ?? '' ); ?>"
		     <?php endif; ?>>
			<?php if ( ! empty( $t['locked'] ) ) : ?>
			<span class="tpl-lock-corner">⏳</span>
			<?php endif; ?>
			<div class="tpl-icon"><?php echo esc_html( $t['icon'] ); ?></div>
			<div class="tpl-badges">
				<?php if ( empty( $t['locked'] ) ) : ?>
				<span class="tpl-badge tpl-badge--starter">✓ <?php esc_html_e( 'Starter', 'anyapi' ); ?></span>
				<?php else : ?>
				<span class="tpl-badge tpl-badge--lite"><?php esc_html_e( 'Soon', 'anyapi' ); ?></span>
				<?php endif; ?>
			</div>
			<h3 class="tpl-name"><?php echo esc_html( $t['name'] ); ?></h3>
			<p class="tpl-desc"><?php echo esc_html( $t['desc'] ); ?></p>
			<?php if ( empty( $t['locked'] ) ) : ?>
			<button class="oi-btn oi-btn--primary oi-btn--block js-tpl-cta" type="button">
				<?php esc_html_e( 'Use template →', 'anyapi' ); ?>
			</button>
			<?php else : ?>
			<button class="oi-btn oi-btn--lite oi-btn--block js-tpl-cta" type="button">
				<?php esc_html_e( 'Coming soon', 'anyapi' ); ?>
			</button>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</div>

	<?php require __DIR__ . '/partials/order-wizard.php'; ?>

</div><!-- /#anyapi-templates-page -->

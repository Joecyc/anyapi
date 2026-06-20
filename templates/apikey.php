<?php
/**
 * apikey.php — API Key management page template.
 *
 * @package AnyApi
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Plan data ────────────────────────────────────────────────────────────────
$plan       = \Anyapi\PlanHelper::currentPlan();
$limits     = \Anyapi\PlanHelper::currentLimits();
$is_free    = ( $plan === 'starter' );
$key_limit  = $limits['api_keys_limit'];          // PHP_INT_MAX = unlimited
$used_keys  = \Anyapi\PlanHelper::usedApiKeys();
$limit_hit  = ( $key_limit !== PHP_INT_MAX && $used_keys >= $key_limit );
$anyapi_upgrade_url = $limits['upgrade_url'];
$show_limit  = ( $key_limit !== PHP_INT_MAX );

// ── Saved keys ───────────────────────────────────────────────────────────────
$keys = get_option( 'anyapi_wc_apikey', array() );
?>

<div id="anyapi-apikey" class="wrap anyapi-page">

  <!-- ======================================================================
       Page header
       ====================================================================== -->
  <div class="ak-page-header">
    <div class="ak-page-header__left">
      <h1 class="ak-page-title">🔑 <?php esc_html_e( 'API Keys', 'anyapi' ); ?></h1>
      <p class="ak-page-desc">
        <?php esc_html_e( 'Manage authentication credentials for your API integrations.', 'anyapi' ); ?>
      </p>
    </div>
    <div class="ak-page-header__right">
      <?php if ( $show_limit ) : ?>
      <div class="ak-quota">
        <div class="ak-quota__bar-wrap">
          <?php $pct = min( 100, round( $used_keys / $key_limit * 100 ) ); ?>
          <div class="ak-quota__bar">
            <div class="ak-quota__fill <?php echo $pct >= 100 ? 'is-full' : ( $pct >= 80 ? 'is-warn' : '' ); ?>"
                 style="width:<?php echo esc_attr( $pct ); ?>%"></div>
          </div>
          <span class="ak-quota__label">
            <?php echo esc_html( $used_keys . ' / ' . $key_limit ); ?>
            <?php esc_html_e( 'keys used', 'anyapi' ); ?>
          </span>
        </div>
        <?php if ( $is_free ) : ?>
        <a href="<?php echo esc_url( $anyapi_upgrade_url ); ?>" class="ak-upgrade-pill" target="_blank" rel="noopener">
          ⚡ <?php esc_html_e( 'Upgrade for more', 'anyapi' ); ?>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <button
        id="ak-add-btn"
        class="ak-btn ak-btn--primary<?php echo $limit_hit ? ' is-disabled' : ''; ?>"
        type="button"
        <?php echo $limit_hit ? 'disabled aria-disabled="true"' : ''; ?>
        data-limit-hit="<?php echo $limit_hit ? '1' : '0'; ?>"
        data-upgrade-url="<?php echo esc_url( $anyapi_upgrade_url ); ?>"
      >
        <span class="ak-btn__icon">+</span>
        <?php esc_html_e( 'Add API Key', 'anyapi' ); ?>
      </button>
    </div>
  </div>

  <!-- ======================================================================
       Add / Edit form (hidden by default, shown via JS)
       ====================================================================== -->
  <div id="ak-form-wrap" class="ak-form-wrap" style="display:none;" aria-hidden="true">
    <div class="ak-form-card">
      <div class="ak-form-card__header">
        <h2 class="ak-form-card__title" id="ak-form-title">
          <?php esc_html_e( 'New API Key', 'anyapi' ); ?>
        </h2>
        <button class="ak-form-close" id="ak-form-close" type="button" aria-label="Close">×</button>
      </div>

      <div class="ak-form-body">
        <input type="hidden" id="ak-edit-id" value="">

        <!-- Name -->
        <div class="ak-field-group">
          <label class="ak-label" for="ak-name">
            <?php esc_html_e( 'Key Name', 'anyapi' ); ?>
            <span class="ak-required">*</span>
          </label>
          <input
            type="text"
            id="ak-name"
            class="ak-input"
            placeholder="<?php esc_attr_e( 'e.g. WhatsApp Production', 'anyapi' ); ?>"
            autocomplete="off"
          >
          <p class="ak-hint"><?php esc_html_e( 'A label to identify this key in your integrations.', 'anyapi' ); ?></p>
          <div class="ak-error" id="ak-name-error"></div>
        </div>

        <!-- Auth Type -->
        <div class="ak-field-group">
          <label class="ak-label"><?php esc_html_e( 'Authentication Type', 'anyapi' ); ?> <span class="ak-required">*</span></label>
          <div class="ak-type-selector" role="radiogroup" aria-label="Authentication type">

            <button class="ak-type-btn active" data-type="bearer" type="button" role="radio" aria-checked="true">
              <span class="ak-type-btn__icon">🔐</span>
              <span class="ak-type-btn__label">Bearer Token</span>
              <span class="ak-type-btn__desc">Authorization: Bearer &lt;token&gt;</span>
            </button>

            <button class="ak-type-btn" data-type="basic" type="button" role="radio" aria-checked="false">
              <span class="ak-type-btn__icon">🔑</span>
              <span class="ak-type-btn__label">Basic Auth</span>
              <span class="ak-type-btn__desc">Authorization: Basic &lt;base64&gt;</span>
            </button>

          </div>
          <input type="hidden" id="ak-type" value="bearer">
        </div>

        <!-- Bearer Token fields -->
        <div id="ak-fields-bearer" class="ak-auth-fields">
          <div class="ak-field-group">
            <label class="ak-label" for="ak-token">
              <?php esc_html_e( 'Bearer Token', 'anyapi' ); ?>
              <span class="ak-required">*</span>
            </label>
            <div class="ak-secret-wrap">
              <input
                type="password"
                id="ak-token"
                class="ak-input ak-input--mono"
                placeholder="<?php esc_attr_e( 'Paste your token here…', 'anyapi' ); ?>"
                autocomplete="new-password"
              >
              <button class="ak-eye-btn" type="button" data-target="ak-token" aria-label="Toggle visibility">
                <span class="ak-eye-icon">👁</span>
              </button>
            </div>
            <div class="ak-error" id="ak-token-error"></div>
          </div>
        </div>

        <!-- Basic Auth fields -->
        <div id="ak-fields-basic" class="ak-auth-fields" style="display:none;">
          <div class="ak-two-col">
            <div class="ak-field-group">
              <label class="ak-label" for="ak-username">
                <?php esc_html_e( 'Username', 'anyapi' ); ?>
                <span class="ak-required">*</span>
              </label>
              <input
                type="text"
                id="ak-username"
                class="ak-input"
                placeholder="<?php esc_attr_e( 'Username', 'anyapi' ); ?>"
                autocomplete="off"
              >
              <div class="ak-error" id="ak-username-error"></div>
            </div>
            <div class="ak-field-group">
              <label class="ak-label" for="ak-password">
                <?php esc_html_e( 'Password', 'anyapi' ); ?>
                <span class="ak-required">*</span>
              </label>
              <div class="ak-secret-wrap">
                <input
                  type="password"
                  id="ak-password"
                  class="ak-input ak-input--mono"
                  placeholder="<?php esc_attr_e( 'Password', 'anyapi' ); ?>"
                  autocomplete="new-password"
                >
                <button class="ak-eye-btn" type="button" data-target="ak-password" aria-label="Toggle visibility">
                  <span class="ak-eye-icon">👁</span>
                </button>
              </div>
              <div class="ak-error" id="ak-password-error"></div>
            </div>
          </div>
          <p class="ak-hint">
            <?php esc_html_e( 'Credentials will be Base64-encoded and sent as the Authorization header.', 'anyapi' ); ?>
          </p>
        </div>

        <!-- Status -->
        <div class="ak-field-group">
          <label class="ak-label"><?php esc_html_e( 'Status', 'anyapi' ); ?></label>
          <label class="ak-toggle">
            <input type="checkbox" id="ak-status" checked>
            <span class="ak-toggle__track"></span>
            <span class="ak-toggle__label-on"><?php esc_html_e( 'Active', 'anyapi' ); ?></span>
            <span class="ak-toggle__label-off"><?php esc_html_e( 'Inactive', 'anyapi' ); ?></span>
          </label>
        </div>

      </div><!-- /.ak-form-body -->

      <div class="ak-form-footer">
        <div class="ak-form-status" id="ak-form-status"></div>
        <div class="ak-form-actions">
          <button class="ak-btn ak-btn--ghost" id="ak-cancel-btn" type="button">
            <?php esc_html_e( 'Cancel', 'anyapi' ); ?>
          </button>
          <button class="ak-btn ak-btn--primary" id="ak-save-btn" type="button">
            <span id="ak-save-label"><?php esc_html_e( 'Save Key', 'anyapi' ); ?></span>
          </button>
        </div>
      </div>

    </div><!-- /.ak-form-card -->
  </div><!-- /#ak-form-wrap -->

  <!-- ======================================================================
       Key cards grid
       ====================================================================== -->
  <div id="ak-grid" class="ak-grid">

    <?php if ( empty( $keys ) ) : ?>
    <!-- Empty state -->
    <div class="ak-empty" id="ak-empty">
      <div class="ak-empty__icon">🔑</div>
      <h3 class="ak-empty__title"><?php esc_html_e( 'No API Keys yet', 'anyapi' ); ?></h3>
      <p class="ak-empty__desc"><?php esc_html_e( 'Create your first API Key to start connecting WooCommerce orders to external APIs.', 'anyapi' ); ?></p>
      <button class="ak-btn ak-btn--primary" id="ak-empty-add-btn" type="button">
        + <?php esc_html_e( 'Add Your First Key', 'anyapi' ); ?>
      </button>
    </div>
    <?php else : ?>
      <?php foreach ( $keys as $k ) :
        if ( empty( $k['id'] ) || empty( $k['name'] ) ) continue;
        $type     = $k['type']   ?? 'bearer';
        $status   = $k['status'] ?? 'active';
        $is_active = ( $status === 'active' );
        $type_label = $type === 'basic' ? 'Basic Auth' : 'Bearer Token';
        $type_icon  = $type === 'basic' ? '🔑' : '🔐';
        // Masked display of key
        $masked = '••••••••••••••••';
      ?>
      <div class="ak-card <?php echo $is_active ? 'is-active' : 'is-inactive'; ?>"
           data-id="<?php echo esc_attr( $k['id'] ); ?>"
           data-name="<?php echo esc_attr( $k['name'] ); ?>"
           data-type="<?php echo esc_attr( $type ); ?>"
           data-key="<?php echo esc_attr( $k['key'] ?? '' ); ?>"
           data-username="<?php echo esc_attr( $k['username'] ?? '' ); ?>"
           data-password="<?php echo esc_attr( $k['password'] ?? '' ); ?>"
           data-status="<?php echo esc_attr( $status ); ?>"
      >
        <div class="ak-card__header">
          <div class="ak-card__icon-wrap">
            <span class="ak-card__icon"><?php echo esc_html( $type_icon ); ?></span>
          </div>
          <div class="ak-card__meta">
            <h3 class="ak-card__name"><?php echo esc_html( $k['name'] ); ?></h3>
            <div class="ak-card__badges">
              <span class="ak-badge ak-badge--type"><?php echo esc_html( $type_label ); ?></span>
              <span class="ak-badge ak-badge--status <?php echo $is_active ? 'is-active' : 'is-inactive'; ?>">
                <?php echo $is_active ? esc_html__( 'Active', 'anyapi' ) : esc_html__( 'Inactive', 'anyapi' ); ?>
              </span>
            </div>
          </div>
          <div class="ak-card__actions">
            <!-- Status toggle -->
            <label class="ak-card__toggle" title="<?php esc_attr_e( 'Toggle status', 'anyapi' ); ?>">
              <input
                type="checkbox"
                class="ak-status-toggle"
                data-id="<?php echo esc_attr( $k['id'] ); ?>"
                <?php checked( $is_active ); ?>
              >
              <span class="ak-card__toggle-track"></span>
            </label>
            <!-- Edit -->
            <button class="ak-icon-btn ak-edit-btn" data-id="<?php echo esc_attr( $k['id'] ); ?>"
                    type="button" aria-label="<?php esc_attr_e( 'Edit', 'anyapi' ); ?>" title="Edit">
              ✏️
            </button>
            <!-- Delete -->
            <button class="ak-icon-btn ak-delete-btn" data-id="<?php echo esc_attr( $k['id'] ); ?>"
                    type="button" aria-label="<?php esc_attr_e( 'Delete', 'anyapi' ); ?>" title="Delete">
              🗑️
            </button>
          </div>
        </div>

        <!-- Credential preview row -->
        <div class="ak-card__cred">
          <span class="ak-card__cred-label">
            <?php echo $type === 'basic' ? esc_html__( 'Credentials', 'anyapi' ) : esc_html__( 'Token', 'anyapi' ); ?>
          </span>
          <code class="ak-card__cred-value" data-id="<?php echo esc_attr( $k['id'] ); ?>">
            <?php echo esc_html( $masked ); ?>
          </code>
          <button class="ak-reveal-btn" type="button"
                  data-id="<?php echo esc_attr( $k['id'] ); ?>"
                  data-revealed="0"
                  aria-label="<?php esc_attr_e( 'Reveal credential', 'anyapi' ); ?>">
            👁
          </button>
        </div>

        <!-- Inline delete confirm (hidden) -->
        <div class="ak-delete-confirm" id="ak-confirm-<?php echo esc_attr( $k['id'] ); ?>" style="display:none;">
          <span class="ak-delete-confirm__msg">
            ⚠️ <?php esc_html_e( 'Delete this key? Integrations using it will stop working.', 'anyapi' ); ?>
          </span>
          <div class="ak-delete-confirm__actions">
            <button class="ak-btn ak-btn--danger ak-confirm-yes" type="button" data-id="<?php echo esc_attr( $k['id'] ); ?>">
              <?php esc_html_e( 'Yes, Delete', 'anyapi' ); ?>
            </button>
            <button class="ak-btn ak-btn--ghost ak-confirm-no" type="button" data-id="<?php echo esc_attr( $k['id'] ); ?>">
              <?php esc_html_e( 'Cancel', 'anyapi' ); ?>
            </button>
          </div>
        </div>

      </div><!-- /.ak-card -->
      <?php endforeach; ?>
    <?php endif; ?>

  </div><!-- /#ak-grid -->

</div><!-- /#anyapi-apikey -->

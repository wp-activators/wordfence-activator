<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Wordfence Security Activator
 * Plugin URI:        https://github.com/wp-activators/wordfence-activator
 * Description:       Wordfence Security Plugin Activator
 * Version:           1.2.0
 * Requires at least: 3.9
 * Requires PHP:      7.2
 * Author:            mohamedhk2
 * Author URI:        https://github.com/mohamedhk2
 **/

defined( 'ABSPATH' ) || exit;
const WORDFENCE_ACTIVATOR_NAME   = 'Wordfence Activator';
const WORDFENCE_ACTIVATOR_DOMAIN = 'wordfence-activator';
const ActivatorRemainingDays     = 365 * 10;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';
if (
	activator_admin_notice_ignored()
	|| activator_admin_notice_plugin_install( 'wordfence/wordfence.php', 'wordfence', 'Wordfence Security', WORDFENCE_ACTIVATOR_NAME, WORDFENCE_ACTIVATOR_DOMAIN )
	|| activator_admin_notice_plugin_activate( 'wordfence/wordfence.php', WORDFENCE_ACTIVATOR_NAME, WORDFENCE_ACTIVATOR_DOMAIN )
) {
	return;
}

function initWordfenceActivator() {
	try {
		wfOnboardingController::_markAttempt1Shown();
		wfConfig::set( 'onboardingAttempt3', wfOnboardingController::ONBOARDING_LICENSE );
		if ( empty( wfConfig::get( 'apiKey' ) ) ) {
			wordfence::ajax_downgradeLicense_callback();
		}
		wfConfig::set( 'isPaid', true );
		wfConfig::set( 'keyType', wfLicense::KEY_TYPE_PAID_CURRENT );
		wfConfig::set( 'premiumNextRenew', time() + ActivatorRemainingDays * 86400 );
		wfWAF::getInstance()->getStorageEngine()->setConfig( 'wafStatus', wfFirewall::FIREWALL_MODE_ENABLED );
	} catch ( Exception $exception ) {
		add_action( 'admin_notices', function () use ( $exception ) { ?>
            <div class="notice notice-error">
            <p><?php
				printf(
				/* translators: %s: plugin name */
					esc_html__( WORDFENCE_ACTIVATOR_NAME . ' error: %s', 'wordfence-activator' ),
					esc_html( $exception->getMessage() )
				); ?></p>
            </div><?php
		} );
	}
}

add_action( 'plugins_loaded', function () {
	if ( class_exists( 'wfLicense' ) ) {
		initWordfenceActivator();
		wfLicense::current()->setType( wfLicense::TYPE_RESPONSE );
		wfLicense::current()->setPaid( true );
		wfLicense::current()->setRemainingDays( ActivatorRemainingDays );
		wfLicense::current()->setConflicting( false );
		wfLicense::current()->setDeleted( false );
		wfLicense::current()->getKeyType();
	}
} );

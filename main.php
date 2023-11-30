<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Wordfence Security Activ@tor
 * Plugin URI:        https://bit.ly/wf-act
 * Description:       Wordfence Security Plugin Activ@tor ✨ (Let's Play a Game)
 * Version:           1.4.0
 * Requires at least: 5.9.0
 * Requires PHP:      7.2
 * Author:            moh@medhk2
 * Author URI:        https://bit.ly/medhk2
 **/

defined( 'ABSPATH' ) || exit;
$PLUGIN_NAME   = 'Wordfence Activ@tor';
$PLUGIN_DOMAIN = 'wordfence-activ@tor';
$RemainingDays = 365 * 10;
extract( require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php' );
if (
	$admin_notice_ignored()
	|| $admin_notice_plugin_install( 'wordfence/wordfence.php', 'wordfence', 'Wordfence Security', $PLUGIN_NAME, $PLUGIN_DOMAIN )
	|| $admin_notice_plugin_activate( 'wordfence/wordfence.php', $PLUGIN_NAME, $PLUGIN_DOMAIN )
) {
	return;
}

$init = function () use ( $RemainingDays, $PLUGIN_NAME ) {
	try {
		wfOnboardingController::_markAttempt1Shown();
		wfConfig::set( 'onboardingAttempt3', wfOnboardingController::ONBOARDING_LICENSE );
		if ( empty( wfConfig::get( 'apiKey' ) ) ) {
			wordfence::ajax_downgradeLicense_callback();
		}
		wfConfig::set( 'isPaid', true );
		wfConfig::set( 'keyType', wfLicense::KEY_TYPE_PAID_CURRENT );
		wfConfig::set( 'premiumNextRenew', time() + $RemainingDays * 86400 );
		wfWAF::getInstance()->getStorageEngine()->setConfig( 'wafStatus', wfFirewall::FIREWALL_MODE_ENABLED );
	} catch ( Exception $exception ) {
		add_action( 'admin_notices', function () use ( $exception, $PLUGIN_NAME ) { ?>
            <div class="notice notice-error">
            <p><?php
				printf(
				/* translators: %s: plugin name */
					esc_html__( $PLUGIN_NAME . ' error: %s', 'wordfence-activ@tor' ),
					esc_html( $exception->getMessage() )
				); ?></p>
            </div><?php
		} );
	}
};

add_action( 'plugins_loaded', function () use ( $RemainingDays, $init ) {
	if ( class_exists( 'wfLicense' ) ) {
		$init();
		wfLicense::current()->setType( wfLicense::TYPE_RESPONSE );
		wfLicense::current()->setPaid( true );
		wfLicense::current()->setRemainingDays( $RemainingDays );
		wfLicense::current()->setConflicting( false );
		wfLicense::current()->setDeleted( false );
		wfLicense::current()->getKeyType();
	}
} );

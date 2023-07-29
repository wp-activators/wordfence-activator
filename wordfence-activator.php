<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Wordfence Activator
 * Plugin URI:        https://github.com/wp-activators/wordfence-activator
 * Description:       Wordfence Security Plugin Activator
 * Version:           1.1.0
 * Requires at least: 3.9
 * Requires PHP:      5.5
 * Author:            mohamedhk2
 * Author URI:        https://github.com/mohamedhk2
 **/

defined( 'ABSPATH' ) || exit;
require_once( ABSPATH . 'wp-includes/pluggable.php' );
require_once( ABSPATH . 'wp-admin/includes/screen.php' );
const WORDFENCE_ACTIVATOR_NAME         = 'Wordfence Activator';
const ActivatorRemainingDays = 365 * 10;
if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! function_exists( 'is_plugin_installed' ) ) {
    function is_plugin_installed( $plugin ) {
        $installed_plugins = get_plugins();

        return isset( $installed_plugins[ $plugin ] );
    }
}
$screen                      = get_current_screen();
if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
    return;
}
if ( ! is_plugin_installed( 'wordfence/wordfence.php' ) ) {
    if ( ! current_user_can( 'install_plugins' ) ) {
        return;
    }
    $install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=wordfence' ), 'install-plugin_wordfence' );
    $message     = '<h3>' . esc_html__( WORDFENCE_ACTIVATOR_NAME . ' plugin requires installing the Wordfence Security plugin', 'wordfence-activator' ) . '</h3>';
    $message     .= '<p>' . esc_html__( 'Install and activate the Wordfence Security plugin to access all the ' . WORDFENCE_ACTIVATOR_NAME . ' features.', 'wordfence-activator' ) . '</p>';
    $message     .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, esc_html__( 'Install Now', 'wordfence-activator' ) ) . '</p>';
    add_action( 'admin_notices', function () use ( $message ) {
        ?>
        <div class="notice notice-error">
        <p><?= $message ?></p>
        </div><?php
    } );

    return;
} elseif ( ! is_plugin_active( 'wordfence/wordfence.php' ) ) {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    $plugin_file     = 'wordfence/wordfence.php';
    $plugin_data     = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
    $activate_action = sprintf(
        '<a href="%s" id="activate-%s" class=button-primary aria-label="%s">%s</a>',
        wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . urlencode( $plugin_file ) . '&amp;plugin_status=all&amp;paged=1&amp;s=', 'activate-plugin_' . $plugin_file ),
        esc_attr( 'wordfence' ),
        /* translators: %s: Plugin name. */
        esc_attr( sprintf( _x( 'Activate %s', 'plugin' ), $plugin_data['Name'] ) ),
        __( 'Activate Now' )
    );
    $message         = '<h3>' . esc_html__( "You're not using {$plugin_data['Name']} plugin yet!", 'wordfence-activator' ) . '</h3>';
    $message         .= '<p>' . esc_html__( "Activate the {$plugin_data['Name']} plugin to start using all of " . WORDFENCE_ACTIVATOR_NAME . ' plugin’s features.', 'wordfence-activator' ) . '</p>';
    $message         .= '<p>' . $activate_action . '</p>';
    add_action( 'admin_notices', function () use ( $message ) {
        ?>
        <div class="notice notice-warning">
        <p><?= $message ?></p>
        </div><?php
    } );

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

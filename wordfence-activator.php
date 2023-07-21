<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Wordfence Activator
 * Plugin URI:        https://github.com/mohamedhk2/wordfence-activator
 * Description:       Wordfence Security Plugin Activator
 * Version:           1.0.0
 * Requires at least: 3.9
 * Requires PHP:      5.5
 * Author:            mohamedhk2
 * Author URI:        https://github.com/mohamedhk2
 **/

defined('ABSPATH') || exit;
const ACTIVATOR_NAME = 'Wordfence Activator';
const ActivatorRemainingDays = 365;
if (!function_exists('is_plugin_active'))
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
if (!is_plugin_active('wordfence/wordfence.php')) {
    add_action('admin_notices', function () {
        ?>
        <div class="notice notice-error">
        <p><?php
            printf(
            /* translators: %s: plugin name */
                esc_html__(ACTIVATOR_NAME . ': %s plugin not found.', 'wordfence-activator'),
                esc_html('Wordfence Security')
            ); ?></p>
        </div><?php
    });
    return;
}
function initActivator()
{
    try {
        wfOnboardingController::_markAttempt1Shown();
        wfConfig::set('onboardingAttempt3', wfOnboardingController::ONBOARDING_LICENSE);
        if (empty(wfConfig::get('apiKey')))
            wordfence::ajax_downgradeLicense_callback();
        wfConfig::set('isPaid', true);
        wfConfig::set('keyType', wfLicense::KEY_TYPE_PAID_CURRENT);
        wfConfig::set('premiumNextRenew', time() + ActivatorRemainingDays * 86400);
        wfWAF::getInstance()->getStorageEngine()->setConfig('wafStatus', wfFirewall::FIREWALL_MODE_ENABLED);
    } catch (Exception $exception) {
        add_action('admin_notices', function () use ($exception) { ?>
            <div class="notice notice-error">
            <p><?php
                printf(
                /* translators: %s: plugin name */
                    esc_html__(ACTIVATOR_NAME . ' error: %s', 'wordfence-activator'),
                    esc_html($exception->getMessage())
                ); ?></p>
            </div><?php
        });
    }
}

add_action('plugins_loaded', function () {
    if (class_exists('wfLicense')) {
        initActivator();
        wfLicense::current()->setType(wfLicense::TYPE_RESPONSE);
        wfLicense::current()->setPaid(true);
        wfLicense::current()->setRemainingDays(ActivatorRemainingDays);
        wfLicense::current()->setConflicting(false);
        wfLicense::current()->setDeleted(false);
        wfLicense::current()->getKeyType();
    }
});

<?php
/**
 * Plugin Name: 10WEB manager
 * Plugin URI: https://10web.io/
 * Description: This plugin is ideal to effortlessly manage your website.
 * Version: 1.3.22
 * Author: 10Web
 * Author URI: https://10web.io/
 * License: GPLv2 or later
 */

use Tenweb_Manager\Helper;

if (!defined('ABSPATH')) {
    exit;
}

function tenweb_check_plugin_requirements()
{
    global $wp_version;
    $php_version = explode("-", PHP_VERSION);
    $php_version = $php_version[0];
    $result = (
        version_compare($wp_version, '4.4', ">=") &&
        version_compare($php_version, '5.3.0', ">=")
    );

    return $result;
}

//use Tenweb_Manager\Manager;

if (tenweb_check_plugin_requirements()) {

    include_once dirname(__FILE__) . '/config.php';
    include_once TENWEB_INCLUDES_DIR . '/class-helper.php';
    include_once dirname(__FILE__) . '/manager.php';

    add_action('plugins_loaded', array('Tenweb_Manager\Manager', 'get_instance'), 1);
}


register_activation_hook(__FILE__, 'tenweb_activate');
register_deactivation_hook(__FILE__, 'tenweb_deactivate');
add_action('admin_init', 'tenweb_plugin_redirect');

function tenweb_plugin_redirect()
{
    if (get_site_option('tenweb_plugin_do_activation_redirect', false)) {
        delete_option('tenweb_plugin_do_activation_redirect');

        if (!is_multisite()) {
            wp_redirect(admin_url('admin.php?page=' . TENWEB_PREFIX . '_menu'));
        }

    }
}

function tenweb_activate($to_die = "1")
{

    //when tenweb_check_plugin_requirements() return false
    include_once dirname(__FILE__) . '/config.php';

    $error_msg = array();
    if (tenweb_check_plugin_requirements() == false) {
        array_push($error_msg, "PHP or Wordpress version not compatible with plugin.");
    }

    if (plugin_basename(__FILE__) !== TENWEB_SLUG) {
        array_push($error_msg, "Plugin foldername/filename.php must be " . TENWEB_SLUG);
    }

    //send new state
    delete_site_transient(TENWEB_PREFIX . '_send_states_transient');
    update_site_option(TENWEB_PREFIX . '_version', TENWEB_VERSION);
    update_site_option(TENWEB_PREFIX . '_activated', '1');

    if (!is_file(WPMU_PLUGIN_DIR . '/10web-manager/10web-manager.php')) {
        update_site_option('tenweb_plugin_do_activation_redirect', true);
    }

    if (!empty($error_msg) && ($to_die == "1" || $to_die == "")) {
        $error_msg = implode("<br/>", $error_msg);
        die($error_msg);
    } else {
        return $error_msg;
    }
}


function tenweb_deactivate()
{
    if (tenweb_check_plugin_requirements() == false) {
        return;
    }

    call_user_func(array('\Tenweb_Manager\Helper', 'send_state_before_deactivation'));
}

function tenweb_plugin_add_new_image_size() {
    add_image_size( 'tenweb_optimizer_mobile', 320, 640, true );
    add_image_size( 'tenweb_optimizer_tablet', 768, 1024, true );
}


if (is_file(WPMU_PLUGIN_DIR . '/10web-manager/10web-manager.php')) {
    if (file_exists(WPMU_PLUGIN_DIR . '/10web-manager/mu/10web_constants.php')) {
        include_once WPMU_PLUGIN_DIR . '/10web-manager/mu/10web_constants.php';
    }

    if (file_exists(WPMU_PLUGIN_DIR . '/10web-manager/mu/10web_redirect.php') && TW_REDIRECT === true) {
        include_once WPMU_PLUGIN_DIR . '/10web-manager/mu/10web_redirect.php';
    }

    if (file_exists(WPMU_PLUGIN_DIR . '/10web-manager/mu/10web_cli.php')) {
        include_once WPMU_PLUGIN_DIR . '/10web-manager/mu/10web_cli.php';
    }

    if (file_exists(WPMU_PLUGIN_DIR . '/10web-manager/mu/10web_login_protection/login_protection.php')) {
        include_once WPMU_PLUGIN_DIR . '/10web-manager/mu/10web_login_protection/login_protection.php';
    }

    if (file_exists(WPMU_PLUGIN_DIR . '/10web-manager/mu/10web_redeclare.php')) {
        include_once WPMU_PLUGIN_DIR . '/10web-manager/mu/10web_redeclare.php';
    }

    add_action('init', 'tenweb_plugin_add_new_image_size');
}

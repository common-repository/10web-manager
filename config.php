<?php

if (!defined('ABSPATH')) {
    exit;
}

define('TENWEB_VERSION', '1.3.22');

//Directories
define('TENWEB_PREFIX', 'tenweb');
define('TENWEB_DIR', dirname(__FILE__));
define('TENWEB_FILE', TENWEB_DIR . '/10web-manager.php');
define('TENWEB_INCLUDES_DIR', TENWEB_DIR . '/includes');
define('TENWEB_VIEWS_DIR', TENWEB_DIR . '/views');
define('TENWEB_WHITELABEL_DIR', TENWEB_DIR . '/assets/whitelabel');
define('TENWEB_URL', plugins_url(plugin_basename(dirname(__FILE__))));
define('TENWEB_URL_CSS', plugins_url(plugin_basename(dirname(__FILE__))) . '/assets/css');
define('TENWEB_URL_JS', plugins_url(plugin_basename(dirname(__FILE__))) . '/assets/js');
define('TENWEB_URL_IMG', plugins_url(plugin_basename(dirname(__FILE__))) . '/assets/images');
define('TENWEB_URL_WHITELABEL', plugins_url(plugin_basename(dirname(__FILE__))) . '/assets/whitelabel');
define('TENWEB_SLUG', "10web-manager/10web-manager.php");
define('TENWEB_LANG', "tenweb");
define('TENWEB_USERNAME', "tenweb_manager_plugin");
define('TENWEB_COMPANY_NAME', '10WEB');
define('TENWEB_REST_NAMESPACE', 'tenweb/v1');
define('TENWEB_LO_SCRITP_PATH', '/10web-manager-scripts/lo.js');

// in seconds
if (!defined('TW_IN_PROGRESS_LOCK')) {
    define('TW_IN_PROGRESS_LOCK', 300);
}

require_once 'env.php';

require_once TENWEB_INCLUDES_DIR . '/class-helper.php';
<?php

define('TENWEB_ENV', 'TENWEB_ENV_VALUE');


if (!defined('TW_FAILED_LOGIN_ATTEMPTS_COUNT')) {
    define('TW_FAILED_LOGIN_ATTEMPTS_COUNT', 5);
}

// in seconds
if (!defined('TW_LOCKOUT_TIME')) {
    define('TW_LOCKOUT_TIME', 10800);
}

// in seconds
if (!defined('TW_FAILED_ATTEMPTS_TIME')) {
    define('TW_FAILED_ATTEMPTS_TIME', 300);
}

if (!defined('TW_LOCKOUT_MESSAGE')) {
    define('TW_LOCKOUT_MESSAGE', 'You have been locked out due to too many invalid login attempts.');
}

if (!defined('TW_BLACKLIST_OPTION_SIZE')) {
    define('TW_BLACKLIST_OPTION_SIZE', 200);
}

if (!defined('TW_REDIRECT')) {
    define('TW_REDIRECT', true);
}

if (!defined('TW_NGX_PAGESPEED') && isset($_SERVER['NGX_PAGESPEED']) && !empty($_SERVER['NGX_PAGESPEED'])) {

    define('TW_NGX_PAGESPEED', 'enabled');

    if (!defined('TW_NGX_PAGESPEED_FILTERS')) {
        define('TW_NGX_PAGESPEED_FILTERS', explode(',', $_SERVER['NGX_PAGESPEED']));
    }

}
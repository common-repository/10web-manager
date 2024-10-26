<?php

$php_version = explode("-", PHP_VERSION);
$php_version = $php_version[0];


if (version_compare($php_version, '8.0.0', ">=")) {
    if (!function_exists('escapeshellcmd')) {
        function escapeshellcmd() {}
    }
}

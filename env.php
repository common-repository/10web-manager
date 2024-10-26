<?php
define('TENWEB_SITE_URL', "https://10web.io");
define('TENWEB_DASHBOARD', "https://my.10web.io");
define('TENWEB_API_URL', 'https://manager.10web.io/api');
define('TENWEB_S3_BUCKET', '10web-products-production');
define('TENWEB_MANAGER_ID', 51);

global $tenweb_services;

$tenweb_services = array(
  'optimizer.10web.io',
  'security.10web.io',
  'seo.10web.io',
  'backup.10web.io',
  'manager.10web.io',
  'core.10web.io',
  'lxd.10web.io'
);
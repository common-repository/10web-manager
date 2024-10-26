<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 9/14/17
 * Time: 3:12 PM
 */

namespace Tenweb_Manager {


    class Helper
    {

        private static $plugins_state = array();
        private static $themes_state = array();
        private static $addons_state = array();
        private static $site_state = array();
        private static $error_logs = array();
        private static $migration_logs = array();
        private static $user_info = null;
        private static $user_agreements = null;
        private static $notices = array();
        private static $installed_plugins_wp_info = null;
        private static $installed_themes_wp_info = null;
        private static $expiration = array(
            'send_states'     => array(
                'expiration' => 43200,//12 hour 43200
                'block_time' => 300,//5 minute 300
            ),
            'user_info'       => array(
                'expiration' => 43200,//12 hour
                'block_time' => 300,//5 minute
            ),
            'user_agreements' => array(
                'expiration' => 43200,//12 hour
                'block_time' => 300,//5 minute
            ),
            'user_products'   => array(
                'expiration' => 43200,//12 hour
                'block_time' => 300,//5 minute
            ),
        );


        public static function get_products_objects($plugins_data = array(), $themes_data = array(), $addons_data = array())
        {

            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            self::$plugins_state = array();
            self::$addons_state = array();
            self::$themes_state = array();

            $site_installed_plugins = get_plugins();
            //if domain hosted on 10Web
            $mu_plugins = get_mu_plugins();
            if (isset($mu_plugins['tenweb-init.php'])) {
                $site_installed_plugins['10web-manager/10web-manager.php'] = $mu_plugins['tenweb-init.php'];
            }

            $site_installed_themes = wp_get_themes(array('errors' => null));

            $plugins = self::get_plugins_objects($plugins_data, $site_installed_plugins);
            $addons = self::get_plugins_objects($addons_data, $site_installed_plugins, true);
            $themes = self::get_themes_objects($themes_data, $site_installed_themes);

            $result = array(
                'plugins' => $plugins,
                'addons'  => $addons,
                'themes'  => $themes
            );
            self::add_more_states($site_installed_plugins, $site_installed_themes);

            return $result;
        }

        private static function add_more_states($site_installed_plugins, $site_installed_themes)
        {

            foreach ($site_installed_plugins as $file_name => $installed_plugin) {
                $slug = explode('/', $file_name);
                $slug = $slug[0];

                $found = false;
                foreach (self::$plugins_state as $state) {
                    if ($state->slug === $slug) {
                        $found = true;
                        break;
                    }
                }


                if ($found === false) {
                    foreach (self::$addons_state as $state) {
                        if ($state->slug === $slug) {
                            $found = true;
                            break;
                        }
                    }
                }

                if ($found === false) {
                    $state = new ProductState(
                        0,
                        $slug,
                        $installed_plugin['Title'],
                        $installed_plugin['Description'],
                        'plugin',
                        $installed_plugin['Version'],
                        1
                    );

                    $state->set_active($file_name);
                    $state->set_tenweb_product(false);
                    $state->set_other_wp_info($file_name, $installed_plugin, self::get_installed_plugins_wp_info());

                    self::$plugins_state[] = $state;
                }

            }

            foreach ($site_installed_themes as $slug => $installed_theme) {

                $found = false;
                foreach (self::$themes_state as $state) {
                    if ($state->slug === $slug) {
                        $found = true;
                        break;
                    }
                }

                if ($found === false) {
                    $state = new ProductState(
                        0,
                        $slug,
                        $installed_theme['Name'],
                        $installed_theme->get('Description'),
                        'theme',
                        $installed_theme['Version'],
                        1
                    );

                    $state->set_active($slug);
                    $state->set_screenshot(self::get_theme_screenshot_url($slug));
                    $state->set_tenweb_product(false);
                    $state->set_other_wp_info($slug, $installed_theme, self::get_installed_themes_wp_info());

                    self::$themes_state[] = $state;
                }

            }
        }

        private static function get_themes_objects($themes_data, $site_installed_themes)
        {


            $themes = array();
            $installed_themes = array();

            foreach ($themes_data as $theme_data) {

                if (isset($site_installed_themes[$theme_data['slug']])) {

                    $installed_theme = $site_installed_themes[$theme_data['slug']];

                    $state = new ProductState(
                        $theme_data['product_id'],
                        $theme_data['slug'],
                        $installed_theme['Name'],
                        $installed_theme->get('Description'),
                        'theme',
                        $installed_theme['Version'],
                        1
                    );

                    $state->set_active($theme_data['slug']);
                    $state->set_screenshot(self::get_theme_screenshot_url($theme_data['slug']));
                    $state->set_is_paid($theme_data['current_version']);
                    $state->set_other_wp_info($theme_data['slug'], $installed_theme, self::get_installed_themes_wp_info());

                    self::$themes_state[] = $state;

                    $theme = new InstalledTheme(
                        $state,
                        $theme_data['product_id'],
                        $theme_data['slug'],
                        $theme_data['title'],
                        $theme_data['description']
                    );

                    $theme->set_product_data($theme_data);
                    $installed_themes[] = $theme;

                } else {

                    $theme = new Product(
                        $theme_data['product_id'],
                        $theme_data['slug'],
                        $theme_data['title'],
                        $theme_data['description'],
                        'theme'
                    );

                    $theme->set_product_data($theme_data);
                    $themes[] = $theme;

                }

            }

            return array(
                'installed_products' => $installed_themes,
                'products'           => $themes
            );
        }

        private static function get_plugins_objects($plugins_data, $site_installed_plugins, $addons = false)
        {

            $plugins = array();
            $installed_plugins = array();

            $manager_exists = false;
            $installed_plugins_info = self::get_installed_plugins_wp_info();
            foreach ($plugins_data as $plugin_data) {

                $plugin_slug = null;
                foreach ($site_installed_plugins as $slug => $plugin) {
                    $slug_data = explode('/', $slug);
                    if ($slug_data[0] == $plugin_data['slug']) {
                        $plugin_slug = $slug;
                        break;
                    }
                }

                if ($plugin_slug != null) {
                    $installed_plugin = $site_installed_plugins[$plugin_slug];

                    if ($plugin_data['product_id'] == TENWEB_MANAGER_ID) {
                        $manager_exists = true;
                    }

                    $state = new ProductState(
                        $plugin_data['product_id'],
                        $plugin_data['slug'],
                        $installed_plugin['Title'],
                        $installed_plugin['Description'],
                        'plugin',
                        $installed_plugin['Version'],
                        1
                    );

                    $state->set_active($plugin_slug);
                    $state->set_is_paid($plugin_data['current_version']);
                    $state->set_other_wp_info($plugin_slug, $installed_plugin, $installed_plugins_info);


                    if ($addons == true) {
                        self::$addons_state[] = $state;
                    } else {
                        self::$plugins_state[] = $state;
                    }


                    $plugin = new InstalledPlugin(
                        $state,
                        $plugin_data['product_id'],
                        $plugin_data['slug'],
                        $plugin_data['title'],
                        $plugin_data['description'],
                        $plugin_slug
                    );

                    $plugin->set_product_data($plugin_data);
                    $installed_plugins[] = $plugin;
                } else {

                    $plugin = new Product(
                        $plugin_data['product_id'],
                        $plugin_data['slug'],
                        $plugin_data['title'],
                        $plugin_data['description']
                    );

                    $plugin->set_product_data($plugin_data);
                    $plugins[] = $plugin;
                }

            }

            if ($manager_exists == false && $addons == false && is_admin()) {
                $plugin = self::create_manager_plugin_object();
                $installed_plugins[] = $plugin;
                $notice = "Fail on connection with api. <a href='#' class='tenweb_clear_cache_button'>Try again</a>";
                Helper::add_notices($notice);
            }

            return array(
                'installed_products' => $installed_plugins,
                'products'           => $plugins
            );
        }

        private static function create_manager_plugin_object()
        {
            $plugin_slug = explode('/', TENWEB_SLUG);
            $plugin_slug = $plugin_slug[0];


            $state = new ProductState(
                TENWEB_MANAGER_ID,
                $plugin_slug,
                "10WEB Manager",
                "",
                'plugin',
                "0.0.0",
                1
            );

            $state->active = true;
            $state->is_paid = false;

            self::$plugins_state[] = $state;

            $plugin = new InstalledPlugin(
                $state,
                TENWEB_MANAGER_ID,
                $plugin_slug,
                "10WEB Manager",
                "",
                TENWEB_SLUG
            );

            return $plugin;
        }

        public static function check_site_state($force_send = false)
        {
            if (is_multisite()) {
                self::check_site_state_single($force_send, 'multisite');
                $sites = get_sites();
                foreach ($sites as $site) {
                    switch_to_blog($site->blog_id);
                    self::check_site_state_single($force_send, $site->blog_id);
                    restore_current_blog();
                }
            } else {
                self::check_site_state_single($force_send);
            }
        }

        private static function check_site_state_single($force_send, $blog_id = null)
        {
            //refresh products object and products state
            Manager::get_instance()->set_products();
            self::site_state($force_send, $blog_id);
        }

        private static function site_state($force_send = false, $current_blog_id = null)
        {
            $plugins_hash = get_option(TENWEB_PREFIX . '_plugins_state_hash');
            $themes_hash = get_option(TENWEB_PREFIX . '_themes_state_hash');
            $addons_hash = get_option(TENWEB_PREFIX . '_addons_state_hash');
            if ($current_blog_id == 'multisite') {
                $site_hash = get_site_option(TENWEB_PREFIX . '_site_state_hash');
            } else {
                $site_hash = get_option(TENWEB_PREFIX . '_site_state_hash');
            }
            $plugins_current_state = md5(json_encode(self::$plugins_state));
            $themes_current_state = md5(json_encode(self::$themes_state));
            $addons_current_state = md5(json_encode(self::$addons_state));
            $site_info = self::get_site_info($current_blog_id);
            $site_current_state = md5(json_encode($site_info));

            if ($force_send === false) {
                /* transient expired after 12 hour*/
                $transient = get_site_transient(TENWEB_PREFIX . '_send_states_transient');
            } else {
                $transient = false;
            }

            $state_data = array("blog_id" => $current_blog_id);
            if ($current_blog_id != 'multisite') {
                if ($plugins_hash !== $plugins_current_state || $transient == false) {

                    $state_data['plugins_info'] = array(
                        "is_network" => ((is_multisite()) ? 1 : 0),
                        "products"   => self::states_to_array(self::$plugins_state)
                    );
                    update_option(TENWEB_PREFIX . '_plugins_state_hash', $plugins_current_state);
                }
                if ($themes_hash !== $themes_current_state || $transient == false) {

                    $state_data['themes_info'] = array(
                        "is_network" => ((is_multisite()) ? 1 : 0),
                        "products"   => self::states_to_array(self::$themes_state)
                    );
                    update_option(TENWEB_PREFIX . '_themes_state_hash', $themes_current_state);
                }

                if ($addons_hash !== $addons_current_state || $transient == false) {

                    $state_data['addons_info'] = array(
                        "is_network" => ((is_multisite()) ? 1 : 0),
                        "products"   => self::states_to_array(self::$addons_state)
                    );
                    update_option(TENWEB_PREFIX . '_addons_state_hash', $addons_current_state);
                }
            }
            if ($site_hash !== $site_current_state || $transient == false) {

                $state_data['site_info'] = $site_info;
                if ($current_blog_id == 'multisite') {
                    update_site_option(TENWEB_PREFIX . '_site_state_hash', $site_current_state);
                } else {
                    update_option(TENWEB_PREFIX . '_site_state_hash', $site_current_state);
                }
            }

            $api = Api::get_instance();
            if (!empty($state_data)) {
                if ($current_blog_id == 'multisite') {
                    $domain_id = get_site_option('tenweb_domain_id');
                } else {
                    $domain_id = get_option('tenweb_domain_id');
                }
                $api->set_domain_id($domain_id);

                $result = $api->send_site_state($state_data);

                $send_all_data = (
                    !empty($state_data['plugins_info']) &&
                    !empty($state_data['themes_info']) &&
                    !empty($state_data['addons_info']) &&
                    !empty($state_data['site_info'])
                );

                if ($send_all_data == true && $result == true) {
                    $expiration = self::$expiration['send_states']['expiration'];
                    Helper::calc_request_block('send_states', true);
                } else {
                    $block_count = Helper::calc_request_block('send_states');
                    $expiration = self::$expiration['send_states']['block_time'] * $block_count;
                }

                set_site_transient(TENWEB_PREFIX . '_send_states_transient', '1', $expiration);
                do_action('tenweb_state_changed');
            }
        }

        public static function send_state_before_deactivation()
        {
            if (is_multisite()) {
                $sites = get_sites();
                foreach ($sites as $site) {
                    switch_to_blog($site->blog_id);
                    self::send_state_before_deactivation_single();
                    restore_current_blog();
                }
            } else {
                self::send_state_before_deactivation_single();
            }
        }

        private static function send_state_before_deactivation_single()
        {
            Manager::get_instance()->set_products();

            foreach (self::$plugins_state as $i => $state) {
                if ($state->product_id == TENWEB_MANAGER_ID) {
                    $state->active = false;
                }
            }

            $manager_info = self::get_manager_info();
            $result = Api::get_instance()->send_site_state($manager_info);
        }

        public static function get_blogs_info()
        {
            $domains = array();
            if (is_multisite()) {
                $sites = get_sites();
                foreach ($sites as $site) {
                    $blog_time_zone = get_blog_option($site->blog_id, 'timezone_string');
                    if (empty($blog_time_zone)) {
                        $blog_time_zone = date_default_timezone_get();
                        if (!$blog_time_zone || empty($blog_time_zone)) {
                            $blog_time_zone = "America/Los_Angeles";
                        }
                    }

                    $blog_details = get_blog_details($site->blog_id);
                    $domains[$site->blog_id]['site_url'] = get_home_url($site->blog_id);
                    $domains[$site->blog_id]['admin_url'] = get_admin_url($site->blog_id);
                    $domains[$site->blog_id]['site_title'] = $blog_details->blogname;
                    $domains[$site->blog_id]['timezone'] = $blog_time_zone;
                    $domains[$site->blog_id]['name'] = get_home_url($site->blog_id);
                }
            }

            return $domains;
        }


        public static function get_site_info($blog_id = null, $reset = false)
        {
            if (self::$site_state != null && $reset === false && is_null($blog_id)) {
                return self::$site_state;
            }

            global $wp_version, $wpdb;

            if ((is_multisite() && is_null($blog_id)) || (is_multisite() && $blog_id == 'multisite')) {
                $home_url = network_admin_url();// or site_url
                $admin_url = network_admin_url();
                $site_title = get_site_option('site_name');
            } else {
                $home_url = get_home_url($blog_id);
                $admin_url = get_admin_url($blog_id);
                $site_title = get_bloginfo('name');
            }

            $sql_version = $wpdb->get_var("SELECT VERSION() AS version");

            if (is_multisite() && $blog_id && $blog_id != 'multisite') {
                $time_zone = get_blog_option($blog_id, 'timezone_string');
            } else {
                $time_zone = get_option('timezone_string');
            }

            if (empty($time_zone)) {
                $time_zone = date_default_timezone_get();
                if (!$time_zone || empty($time_zone)) {
                    $time_zone = "America/Los_Angeles";
                }
            }

            $server_software = isset($_SERVER['SERVER_SOFTWARE']) && trim($_SERVER['SERVER_SOFTWARE']) !== '' ? $_SERVER['SERVER_SOFTWARE'] : 'unknown';

            $site_info = array(
                'platform'            => 'wordpress',
                'site_url'            => $home_url,
                'admin_url'           => $admin_url,
                'name'                => $home_url,
                'site_title'          => $site_title,
                'site_screenshot_url' => $home_url,
                'platform_version'    => $wp_version,
                'php_version'         => PHP_VERSION,
                'mysql_version'       => $sql_version,
                'timezone'            => $time_zone,//todo check on multisite
                'server_type'         => $server_software,
                'server_version'      => $server_software,
                'other_data'          => array(
                    'file_system'     => array(
                        'method' => self::get_fs_method(),
                        'config' => self::check_fs_configs() ? 1 : 0
                    ),
                    "is_network"      => ((is_multisite()) ? 1 : 0),
                    "blog_id"         => $blog_id,
                    "manager_version" => TENWEB_VERSION
                ),
                "is_network"          => ((is_multisite()) ? 1 : 0),
                "manager_version"     => TENWEB_VERSION
            );

            if (is_multisite() && is_numeric($blog_id)) {
                $blog_details = get_blog_details($blog_id);
                if (!empty($blog_details)) {
                    $site_info['other_data']['multisite_data'] = array(
                        'registered'   => $blog_details->registered,
                        'last_updated' => $blog_details->last_updated,
                    );
                }
            }

            self::$site_state = $site_info;

            return self::$site_state;
        }

        public static function get_manager_info()
        {
            return array(
                'site_info'    => self::get_site_info(),
                'plugins_info' => array(
                    "is_network" => ((is_multisite()) ? 1 : 0),
                    "products"   => self::states_to_array(self::$plugins_state)
                ),
                'themes_info'  => array(
                    "is_network" => ((is_multisite()) ? 1 : 0),
                    "products"   => self::states_to_array(self::$themes_state)
                ),
                'addons_info'  => array(
                    "is_network" => ((is_multisite()) ? 1 : 0),
                    "products"   => self::states_to_array(self::$addons_state)
                )
            );
        }

        public static function get_site_full_state()
        {

            $plugins_state = array();
            $themes_state = array();

            $plugins = get_plugins();

            foreach ($plugins as $slug => $plugin) {
                $state = new ProductState($slug, $slug, $plugin['Title'], $plugin['Description'], 'plugin', $plugin['Version'], 1);
                $state->set_active($slug);
                $plugins_state[] = $state->get_wp_info();
            }

            $themes = wp_get_themes(array('errors' => null));
            foreach ($themes as $slug => $theme) {
                $state = new ProductState($slug, $slug, $theme['Name'], $theme->get('Description'), 'theme', $theme['Version'], 1);
                $state->set_active($slug);
                $state->set_screenshot(self::get_theme_screenshot_url($slug));
                $themes_state[] = $state->get_wp_info();
            }

            return array(
                'site_info' => self::get_site_info(),
                'plugins'   => $plugins_state,
                'themes'    => $themes_state
            );

        }

        /**
         * @return string direct|ssh2|ftpext|ftpsockets
         */
        public static function get_fs_method()
        {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/misc.php'); // extract_from_markers() wp-super-cache deactivation fatal error fix

            return get_filesystem_method();
        }

        public static function check_fs_configs()
        {

            $fs_method = self::get_fs_method();

            if ($fs_method == "direct") {
                return true;
            }

            $credentials['connection_type'] = $fs_method;
            $credentials['hostname'] = (defined('FTP_HOST')) ? FTP_HOST : "";
            $credentials['username'] = (defined('FTP_USER')) ? FTP_USER : "";
            $credentials['password'] = (defined('FTP_PASS')) ? FTP_PASS : "";
            $credentials['public_key'] = (defined('FTP_PUBKEY')) ? FTP_PUBKEY : "";
            $credentials['private_key'] = (defined('FTP_PRIKEY')) ? FTP_PRIKEY : "";

            if (
                (!empty($credentials['password']) && !empty($credentials['username']) && !empty($credentials['hostname'])) ||
                ('ssh' == $credentials['connection_type'] && !empty($credentials['public_key']) && !empty($credentials['private_key']))
            ) {
                return true;
            } else {
                return false;
            }

        }


        public static function store_migration_log($key, $msg)
        {
            if (self::get_configs('TENWEB_MIGRATION_DEBUG')) {
                self::set_migration_log($key, $msg);
            }
        }

        public static function set_error_log($key, $msg)
        {
            $logs = self::get_error_logs();
            $logs[$key] = array('msg' => $msg, 'date' => date('Y-m-d H:i:s'));
            $expiration = 31 * 24 * 60 * 60;
            set_site_transient(TENWEB_PREFIX . '_error_logs', $logs, $expiration);
            self::$error_logs = $logs;
        }

        public static function set_migration_log($key, $msg)
        {
            $logs = self::get_migration_logs();
            $logs[$key] = array('msg' => $msg, 'date' => date('Y-m-d H:i:s'), 'timestamp' => current_time('timestamp'));
            $expiration = 3 * 24 * 60 * 60;
            set_site_transient(TENWEB_PREFIX . '_migration_logs', $logs, $expiration);
            self::$migration_logs = $logs;
        }

        public static function update_migration_state($key, $value)
        {
            $state = get_site_option(TENWEB_PREFIX . '_migration_state', array());
            $state[$key] = $value;
            update_site_option(TENWEB_PREFIX . '_migration_state', $state);
        }

        public static function flush_migration_log()
        {
            delete_site_transient(TENWEB_PREFIX . '_migration_logs');
            self::$migration_logs = array();
        }

        public static function get_error_logs()
        {

            if (self::$error_logs == null) {
                $logs = get_site_transient(TENWEB_PREFIX . '_error_logs');
                if (!is_array($logs)) {
                    $logs = array();
                }
                self::$error_logs = $logs;
            }

            return self::$error_logs;
        }

        public static function get_migration_logs()
        {

            if (self::$migration_logs == null) {
                $logs = get_site_transient(TENWEB_PREFIX . '_migration_logs');
                if (!is_array($logs)) {
                    $logs = array();
                }
                self::$migration_logs = $logs;
            }

            return self::$migration_logs;
        }

        public static function states_to_array($states = array())
        {
            foreach ($states as $key => $state) {
                if ($state instanceof ProductState) {
                    $states[$key] = $state->get_info();
                } else {
                    unset($states[$key]);
                }
            }

            if (!is_array($states)) {
                return array();
            }

            return $states;
        }

        public static function get_tenweb_user_info($refresh = false, $forceLocalData = false)
        {

            if (self::$user_info != null && $refresh == false) {
                return self::$user_info;
            }

            $transient = get_site_transient(TENWEB_PREFIX . '_user_info_transient');
            $user_info_cache = get_site_option(TENWEB_PREFIX . '_user_info');

            if (!$forceLocalData &&  ( $transient == false || $user_info_cache == false || $refresh == true ) ) {

                $user_info = Api::get_instance()->get_user_info();

                if (!is_null($user_info)) {
                    $expiration = self::$expiration['user_info']['expiration'];
                    Helper::calc_request_block('user_info', true);
                } else {
                    $user_info = array(
                        'name'            => 'Unknown',
                        'timezone_offset' => 0
                    );

                    $block_count = Helper::calc_request_block('user_info');
                    $expiration = self::$expiration['user_info']['block_time'] * $block_count;
                }

                set_site_transient(TENWEB_PREFIX . '_user_info_transient', '1', $expiration);
                update_site_option(TENWEB_PREFIX . '_user_info', $user_info);
            }

            self::$user_info = (!empty($user_info)) ? $user_info : $user_info_cache;

            return self::$user_info;
        }

        public static function get_user_agreements_info($refresh = false, $forceLocalData = false)
        {

            if (self::$user_agreements != null && $refresh == false) {
                return self::$user_agreements;
            }

            $transient = get_site_transient(TENWEB_PREFIX . '_user_agreements_transient');
            $user_agreements_cache = get_site_option(TENWEB_PREFIX . '_user_agreements');

            if (!$forceLocalData &&  ($transient == false || $user_agreements_cache == false || $refresh == true)) {

                $user_agreements = Api::get_instance()->get_user_agreements_info();

                if (!is_null($user_agreements)) {
                    update_site_option(TENWEB_PREFIX . '_user_agreements', $user_agreements);

                    $expiration = self::$expiration['user_agreements']['expiration'];
                    Helper::calc_request_block('user_agreements', true);
                } else {

                    $block_count = Helper::calc_request_block('user_agreements');
                    $expiration = self::$expiration['user_agreements']['block_time'] * $block_count;
                }

                set_site_transient(TENWEB_PREFIX . '_user_agreements_transient', '1', $expiration);

            }

            self::$user_agreements = (!empty($user_agreements)) ? $user_agreements : $user_agreements_cache;

            return self::$user_agreements;
        }

        public static function calc_request_block($key, $reset = false)
        {

            $blocks = get_site_option(TENWEB_PREFIX . '_requests_block');

            if (!is_array($blocks)) {
                $blocks = array();
            }

            if ($reset == true) {
                $blocks[$key] = 0;
                update_site_option(TENWEB_PREFIX . '_requests_block', $blocks);

                return 1;
            }

            if (!isset($blocks[$key]) || $blocks[$key] < 0) {
                $blocks[$key] = 0;
            }

            $blocks[$key] = ($blocks[$key] == 0) ? 1 : $blocks[$key] * 2;


            if ($blocks[$key] > 200) {
                $blocks[$key] = 200;
            }

            update_site_option(TENWEB_PREFIX . '_requests_block', $blocks);

            return $blocks[$key];
        }

        public static function get_products_state()
        {
            return array(
                'plugins' => self::$plugins_state,
                'addons'  => self::$addons_state,
                'themes'  => self::$themes_state
            );
        }

        public static function clear_cache()
        {
            delete_site_transient(TENWEB_PREFIX . '_client_products_transient');
            delete_site_transient(TENWEB_PREFIX . '_send_states_transient');
            delete_site_transient(TENWEB_PREFIX . '_user_info_transient');
            delete_site_transient(TENWEB_PREFIX . '_user_agreements_transient');
            delete_site_option(TENWEB_PREFIX . '_requests_block');
        }

        public static function save_configs($data)
        {
            $config_json = file_get_contents(TENWEB_DIR . '/config.json');
            $config = json_decode($config_json, true);
            if (isset($data['migration_debug'])) {
                $config['TENWEB_MIGRATION_DEBUG'] = $data['migration_debug'];
            }
            if (isset($data['migration_encrypt_db'])) {
                $config['TENWEB_MIGRATION_ENCRYPT_DB'] = $data['migration_encrypt_db'];
            }
            if (isset($data['migration_archive_type'])) {
                $config['TENWEB_MIGRATION_ARCHIVE_TYPE'] = $data['migration_archive_type'];
            }
            if (isset($data['migration_max_files_restart'])) {
                $config['TENWEB_MIGRATION_MAX_FILES_RESTART'] = ceil($data['migration_max_files_restart']);
            }
            if (isset($data['migration_max_db_rows_restart'])) {
                $config['TENWEB_MIGRATION_MAX_DB_ROWS_RESTART'] = ceil($data['migration_max_db_rows_restart']);
            }
            if (isset($data['migration_bulk_files_count'])) {
                $config['TENWEB_MIGRATION_BULK_FILES_COUNT'] = ceil($data['migration_bulk_files_count']);
            }
            if (isset($data['migration_bulk_db_rows_count'])) {
                $config['TENWEB_MIGRATION_BULK_DB_ROWS_COUNT'] = ceil($data['migration_bulk_db_rows_count']);
            }
            if (isset($data['migration_file_size_limit'])) {
                $config['TENWEB_MIGRATION_FILE_SIZE_LIMIT'] = ceil($data['migration_file_size_limit']);
            }
            if (isset($data['migration_multiple_archives'])) {
                $config['TENWEB_MIGRATION_MULTIPLE_ARCHIVES'] = ceil($data['migration_multiple_archives']);
            }
            if (isset($data['migration_exec_time_offset'])) {
                $config['TENWEB_MIGRATION_EXEC_TIME_OFFSET'] = ceil($data['migration_exec_time_offset']);
            }
            if (isset($data['migration_upload_archive_s3'])) {
                $config['TENWEB_MIGRATION_UPLOAD_ARCHIVE_S3'] = $data['migration_upload_archive_s3'];
            }
            if (isset($data['migration_multipart_upload_chunk_size'])) {
                $config['TENWEB_MIGRATION_MULTIPART_UPLOAD_CHUNK_SIZE'] = ceil($data['migration_multipart_upload_chunk_size']);
            }

            file_put_contents(TENWEB_DIR . '/config.json', json_encode($config));
        }

        public static function delete_banned_ip($ips)
        {
            if (is_file(WPMU_PLUGIN_DIR . '/10web-manager/10web-manager.php')) {
                if (class_exists('TWIPBanning')) {
                    $banning = new \TWIPBanning();
                    $ips = explode(',', $ips);
                    foreach ($ips as $ip) {
                        $banning->setIp($ip);
                        $banning->remove();
                    }
                }
            }
        }


        public static function get_tmp_dir($force_upload_dir = false)
        {
            $sys_tmp_dir = sys_get_temp_dir();
            if (!$force_upload_dir && !self::get_configs('TENWEB_MIGRATION_DEBUG') && is_dir($sys_tmp_dir) && is_readable($sys_tmp_dir)) {
                $tmp_dir = $sys_tmp_dir . "/10web_tmp";

            } else {
                $tmp_dir = self::get_uploads_dir() . "/10web_tmp";
            }


            if (!is_dir($tmp_dir)) {
                mkdir($tmp_dir);
            }

            return $tmp_dir;
        }


        public static function get_uploads_dir()
        {
            $uploads_dir = wp_upload_dir();

            return $uploads_dir["basedir"];
        }


        public static function get_configs($option = null)
        {
            $config_json = array();
            if (file_exists(TENWEB_DIR . '/config.json')) {
                $config_json = file_get_contents(TENWEB_DIR . '/config.json');
                $config_json = json_decode($config_json, true);
            }

            $configs = array();

            $configs['TENWEB_MIGRATION_DEBUG'] = isset($config_json['TENWEB_MIGRATION_DEBUG']) ? $config_json['TENWEB_MIGRATION_DEBUG'] : 1;
            $configs['TENWEB_MIGRATION_ENCRYPT_DB'] = isset($config_json['TENWEB_MIGRATION_ENCRYPT_DB']) ? $config_json['TENWEB_MIGRATION_ENCRYPT_DB'] : 1;
            $configs['TENWEB_MIGRATION_ARCHIVE_TYPE'] = isset($config_json['TENWEB_MIGRATION_ARCHIVE_TYPE']) ? $config_json['TENWEB_MIGRATION_ARCHIVE_TYPE'] : "nelexa_zip";
            $configs['TENWEB_MIGRATION_MAX_FILES_RESTART'] = isset($config_json['TENWEB_MIGRATION_MAX_FILES_RESTART']) ? $config_json['TENWEB_MIGRATION_MAX_FILES_RESTART'] : 10000;
            $configs['TENWEB_MIGRATION_MAX_DB_ROWS_RESTART'] = isset($config_json['TENWEB_MIGRATION_MAX_DB_ROWS_RESTART']) ? $config_json['TENWEB_MIGRATION_MAX_DB_ROWS_RESTART'] : 100000;
            $configs['TENWEB_MIGRATION_BULK_FILES_COUNT'] = isset($config_json['TENWEB_MIGRATION_BULK_FILES_COUNT']) ? $config_json['TENWEB_MIGRATION_BULK_FILES_COUNT'] : 10000;
            $configs['TENWEB_MIGRATION_BULK_DB_ROWS_COUNT'] = isset($config_json['TENWEB_MIGRATION_BULK_DB_ROWS_COUNT']) ? $config_json['TENWEB_MIGRATION_BULK_DB_ROWS_COUNT'] : 1000;
            $configs['TENWEB_MIGRATION_FILE_SIZE_LIMIT'] = isset($config_json['TENWEB_MIGRATION_FILE_SIZE_LIMIT']) ? $config_json['TENWEB_MIGRATION_FILE_SIZE_LIMIT'] : 300000000;
            $configs['TENWEB_MIGRATION_MULTIPLE_ARCHIVES'] = isset($config_json['TENWEB_MIGRATION_MULTIPLE_ARCHIVES']) ? $config_json['TENWEB_MIGRATION_MULTIPLE_ARCHIVES'] : 1;
            $configs['TENWEB_MIGRATION_EXEC_TIME_OFFSET'] = isset($config_json['TENWEB_MIGRATION_EXEC_TIME_OFFSET']) ? $config_json['TENWEB_MIGRATION_EXEC_TIME_OFFSET'] : 10;
            $configs['TENWEB_MIGRATION_UPLOAD_ARCHIVE_S3'] = isset($config_json['TENWEB_MIGRATION_UPLOAD_ARCHIVE_S3']) ? $config_json['TENWEB_MIGRATION_UPLOAD_ARCHIVE_S3'] : 0;
            $configs['TENWEB_MIGRATION_MULTIPART_UPLOAD_CHUNK_SIZE'] = isset($config_json['TENWEB_MIGRATION_MULTIPART_UPLOAD_CHUNK_SIZE']) ? $config_json['TENWEB_MIGRATION_MULTIPART_UPLOAD_CHUNK_SIZE'] : 5;

            if ($option !== null) {
                return isset($configs[$option]) ? $configs[$option] : false;
            }

            return $configs;
        }

        public static function get_default_configs()
        {
            return array(
                'TENWEB_MIGRATION_DEBUG_DEFAULT'               => 1,
                'TENWEB_MIGRATION_ENCRYPT_DB_DEFAULT'          => 1,
                'TENWEB_MIGRATION_ARCHIVE_TYPE_DEFAULT'        => 'nelexa_zip',
                'TENWEB_MIGRATION_MAX_FILES_RESTART_DEFAULT'   => 10000,
                'TENWEB_MIGRATION_MAX_DB_ROWS_RESTART_DEFAULT' => 100000,
                'TENWEB_MIGRATION_BULK_FILES_COUNT_DEFAULT'    => 10000,
                'TENWEB_MIGRATION_BULK_DB_ROWS_COUNT_DEFAULT'  => 1000,
                'TENWEB_MIGRATION_FILE_SIZE_LIMIT_DEFAULT'     => 300000000,
                'TENWEB_MIGRATION_MULTIPLE_ARCHIVES_DEFAULT'   => 1,
                'TENWEB_MIGRATION_EXEC_TIME_OFFSET_DEFAULT'    => 10,
                'TENWEB_MIGRATION_RETRY_ARCHIVE_TYPE'          => 'gzip',
                'TENWEB_MIGRATION_UPLOAD_ARCHIVE_S3'           => 1,
                'TENWEB_MIGRATION_MULTIPART_UPLOAD_CHUNK_SIZE' => 5,
            );
        }

        public static function add_notices($notice_text, $error = true)
        {
            $container_class = "notice is-dismissible";
            if ($error) {
                $container_class .= " error";
            }
            if (!function_exists('get_current_screen')) {
                return false;
            }

            $screen = get_current_screen();
            $notice = '<div class="' . $container_class . ' tenweb_manager_notice ' . ($screen->parent_base == "tenweb_menu" ? "tenweb_menu_notice" : "") . '">'
                . '<p>' . $notice_text . '</p>'
                . '</div>';
            self::$notices[] = $notice;
        }

        public static function get_notices()
        {
            return self::$notices;
        }

        public static function get_expiration($key)
        {
            return (isset(self::$expiration[$key])) ? self::$expiration[$key] : null;
        }

        public static function get_installed_plugins_wp_info()
        {

            if (self::$installed_plugins_wp_info === null) {

                include_once ABSPATH . WPINC . '/update.php';
                wp_update_plugins();
                self::$installed_plugins_wp_info = get_site_transient('update_plugins');
                self::filter_installed_plugins_wp_info();
            }

            return self::$installed_plugins_wp_info;
        }

        public static function get_installed_themes_wp_info()
        {

            if (self::$installed_themes_wp_info === null) {

                include_once ABSPATH . WPINC . '/update.php';
                wp_update_themes();
                self::$installed_themes_wp_info = get_site_transient('update_themes');
                self::filter_installed_themes_wp_info();
            }

            return self::$installed_themes_wp_info;
        }


        private static function filter_installed_plugins_wp_info()
        {
            $slugs = array(
                'js_composer/js_composer.php',
                'elementor-pro/elementor-pro.php',
                'wordpress-seo-premium/wp-seo-premium.php'
            );

            foreach ($slugs as $slug) {

                if (isset(self::$installed_plugins_wp_info->response[$slug])) {
                    unset(self::$installed_plugins_wp_info->response[$slug]);
                }

                if (isset(self::$installed_plugins_wp_info->no_update[$slug])) {
                    unset(self::$installed_plugins_wp_info->no_update[$slug]);
                }
            }
        }

        private static function filter_installed_themes_wp_info()
        {
            $slugs = array('divi');

            foreach ($slugs as $slug) {
                if (isset(self::$installed_themes_wp_info->response[$slug])) {
                    unset(self::$installed_themes_wp_info->response[$slug]);
                }
            }

        }

        private static function get_theme_screenshot_url($slug)
        {
            $theme_folder = get_theme_root();
            $theme_folder .= '/' . $slug;

            //file extensions https://codex.wordpress.org/Theme_Development#Screenshot
            $file_name = "";
            if (file_exists($theme_folder . '/screenshot.png')) {
                $file_name = 'screenshot.png';
            } else if (file_exists($theme_folder . '/screenshot.jpg')) {
                $file_name = 'screenshot.jpg';
            } else if (file_exists($theme_folder . '/screenshot.jpeg')) {
                $file_name = 'screenshot.jpeg';
            } else if (file_exists($theme_folder . '/screenshot.gif')) {
                $file_name = 'screenshot.gif';
            }

            if (!empty($file_name)) {
                $file = get_theme_root_uri();
                $file .= '/' . $slug . '/' . $file_name;

                return $file;
            } else {
                return "";
            }

        }

        public static function check_if_manager_mu()
        {
            if (is_file(WPMU_PLUGIN_DIR . '/10web-manager/10web-manager.php')) {
                return true;
            }

            return false;
        }

        public static function get_migration_archive_type()
        {
            $configs = self::get_configs();


            if (isset($configs['TENWEB_MIGRATION_ARCHIVE_TYPE'])) {
                return $configs['TENWEB_MIGRATION_ARCHIVE_TYPE'];
            }

            if (class_exists('ZipArchive') && extension_loaded('zip')) {
                return 'zip';
            }

            return 'gzip';
        }

        public static function get_company_name()
        {

            if (defined('TENWEB_COMPANY_NAME') && TENWEB_COMPANY_NAME != '' && strtolower(TENWEB_COMPANY_NAME) != '10web manager') {
                return TENWEB_COMPANY_NAME;
            }

            return '10WEB';

        }

        public static function get_cache_text()
        {
            if (defined('TENWEB_WHITELABEL_DIR') && is_file(TENWEB_WHITELABEL_DIR . '/text/cache_text.txt')) {
                $content = file_get_contents(TENWEB_WHITELABEL_DIR . '/text/cache_text.txt');

                if ($content != '') {
                    return htmlentities($content);
                }
            }

            return '<span>We use NGINX FastCGI Static Page Caching to cache everything from pages to feeds to 301-redirects on sub-domains.<br class="line-break"/> It helps your site to load super fast for your non-logged-in visitors.</span>';

        }

        public static function get_cache_icon()
        {
            if (defined('TENWEB_WHITELABEL_DIR') && is_dir(TENWEB_WHITELABEL_DIR . '/images')) {
                $icon_dir = TENWEB_WHITELABEL_DIR . '/images';
                $icons = scandir($icon_dir);

                //return first element
                if (isset($icons[2]) && $icons[2] != '.' && $icons != '..') {
                    return TENWEB_URL_WHITELABEL . '/images/' . $icons[2];
                }
            }


            return TENWEB_URL_IMG . '/cache-logo.svg';
        }

        /**
         * Check if plugin or theme activate
         *
         * @return bool
         */
        public static function get_products_diff()
        {
            $active_products_list = get_option("tenweb_active_products_list");
            $active_products_current = get_option('active_plugins');
            $active_theme = get_option('stylesheet');
            array_push($active_products_current, $active_theme);
            if (is_multisite()) {
                $active_plugins_network = get_site_option('active_sitewide_plugins');
                $active_products_current = array_merge($active_products_current, $active_plugins_network);
            }
            if (is_array($active_products_list) && is_array($active_products_current)) {
                $diff = array_merge(array_diff($active_products_current, $active_products_list), array_diff($active_products_list, $active_products_current));
                if (!empty($diff)) {
                    update_option("tenweb_active_products_list", $active_products_current);

                    return true;
                } else {
                    return false;
                }
            } else {
                update_option("tenweb_active_products_list", $active_products_current);
            }

            return true;
        }

    }


}
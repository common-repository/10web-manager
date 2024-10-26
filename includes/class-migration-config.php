<?php

namespace Tenweb_Manager {

    class MigrationConfig extends Migration
    {

        /**
         * @throws \Exception
         */
        public function run()
        {
            $config = $this->get_site_info();
            $file_to_save_json = Helper::get_tmp_dir() . "/" . self::MIGRATION_CONFIG_FILE_NAME;
            $data = json_encode($config);
            if (file_put_contents($file_to_save_json, $data) === false) {
                throw new \Exception("Unable to write config.json");
            }

            return $file_to_save_json;
        }

        /**
         * @return array
         */
        private function get_site_info()
        {
            Helper::store_migration_log('start_get_site_info', 'Starting get_site_info function.');
            global $wp_version;

            // set config
            $config = array();

            // set home and site url
            $config["site_url"] = site_url();
            $config["home_url"] = home_url();

            // set wordpress version and content dir
            $config["wp_version"] = $wp_version;
            $config["wp_content_dir"] = WP_CONTENT_DIR;
            $config["wp_uploads_dir"] = str_replace(ABSPATH, '', Helper::get_uploads_dir());

            // set mysql version
            global $wpdb;
            $sql_version = $wpdb->get_var("SELECT VERSION() AS version");
            $config["sql_version"] = $sql_version;
            $config["db_prefix"] = $wpdb->prefix;

            // set php version
            $config["php_version"] = PHP_VERSION;

            // set plugins, themes data
           // $config["plugins"] = $this->get_plugins();
            //$config["themes"] = $this->get_themes();

            // set php config
            $config["php_extensions"] = get_loaded_extensions();
            $config["php_memory_limit"] = ini_get('memory_limit');
            $config["php_disable_functions"] = ini_get('disable_functions');
            $config["php_max_execution_time"] = ini_get('max_execution_time');

            //set db file path
            $config["db_file_path"] = self::MIGRATION_DB_FILE_NAME;

            Helper::store_migration_log('end_get_site_info', 'End get_site_info function.');

            return $config;
        }

        /**
         * @return array
         */
        private function get_plugins()
        {
            $installed_plugins = get_plugins();
            $installed_plugins = array_map(function ($value) {
                $data = array(
                    "Name"       => $value["Name"],
                    "PluginURI"  => $value["PluginURI"],
                    "Version"    => $value["Version"],
                    "AuthorName" => $value["AuthorName"],
                );

                return $data;
            }, $installed_plugins);

            return $installed_plugins;
        }

        /**
         * @return array
         */
        private function get_themes()
        {
            $installed_themes = array();
            $themes = wp_get_themes();
            foreach ($themes as $slug => $theme) {
                $installed_themes[$slug] = array(
                    "Name"       => $theme->Name,
                    "ThemeURI"   => $theme->ThemeURI,
                    "Version"    => $theme->Version,
                    "AuthorName" => $theme->AuthorName,
                );
            }

            return $installed_themes;
        }


    }
}
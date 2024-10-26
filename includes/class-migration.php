<?php

namespace Tenweb_Manager {

    class Migration
    {
        const MIGRATION_DB_FILE_NAME     = "10web_migration_db.sql";
        const MIGRATION_CONFIG_FILE_NAME = "10web_migration_config.json";
        const MIGRATION_ARCHIVE          = "10web_migration";
        protected $archive_dir = null;
        protected $configs = null;

        /**
         * Migration constructor.
         *
         */
        public function __construct()
        {
            $this->archive_dir = Helper::get_tmp_dir();
            $this->configs = Helper::get_configs();
        }

        /**
         * @param $i
         * @return string
         */
        public static function getMigrationArchive($i = '')
        {
            // we need zlib to use gzencode function
            if (Helper::get_migration_archive_type() == 'gzip') {
                return self::MIGRATION_ARCHIVE . $i . '.tar.gz';
            } else {
                return self::MIGRATION_ARCHIVE . $i . '.zip';
            }
        }


        /**
         * @param $delete_db_options
         *
         * function for removing  files, if something goes wrong
         */
        public static function rollback($delete_db_options = true)
        {
            self::recursive_remove_dir(Helper::get_tmp_dir());
            if ($delete_db_options === true) {
                self::rollback_db();
            }
        }

        public static function scan_archive_dir()
        {
            $archive_dir = Helper::get_tmp_dir();
            $files = scandir($archive_dir);
            $result = array();

            if ($files) {
                foreach ($files as $file) {
                    if ($file != "." && $file != "..") {
                        $result[$file] = (integer)filesize( $archive_dir . "/" . $file);
                    }
                }
            }

            return $result;
        }

        public static function rollback_db()
        {
            delete_site_transient('tenweb_subdomain');
            delete_site_transient('tenweb_migrate_live');
            delete_site_transient('tenweb_migrate_domain_id');
            delete_site_transient('tenweb_migrate_region');
            delete_site_transient('tenweb_tp_domain_name');
        }

        public static function recursive_remove_dir($dir)
        {
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (is_dir($dir . "/" . $object))
                            rmdir($dir . "/" . $object);
                        else
                            unlink($dir . "/" . $object);
                    }
                }
                rmdir($dir);
            }
        }

        public function restart()
        {
            Helper::store_migration_log('start_restart_' . current_time('timestamp'), 'Starting restart.');
            update_site_option('tenweb_migration_restart', 1);

            $url = add_query_arg(array('rest_route' => '/' . TENWEB_REST_NAMESPACE . '/restart_migration_file'), get_home_url() . "/");
            wp_remote_post($url, array('method' => 'POST', 'timeout' => 0.1, 'body' => array('tenweb_nonce' => wp_create_nonce('wp_rest'))));

            Helper::store_migration_log('end_restart_' . current_time('timestamp'), 'End restart.');
            die('{"tenweb_restart": "1"}');
        }

        public static function get_object_file_content()
        {
            $content = file_get_contents(Helper::get_tmp_dir() . '/content_object.txt');

            return unserialize($content);
        }

    }
}
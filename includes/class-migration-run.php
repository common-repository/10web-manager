<?php

namespace Tenweb_Manager {

    class MigrationRun
    {

        private $error = null;
        private $archive_path;


        private $retry_count = 0;
        private $migrate_ended = 0;
        private $configs;
        private $default_configs;

        public $predefined_configs;


        public function __construct()
        {
            ini_set("memory_limit", "-1");
            ini_set("max_execution_time", "500");
            $this->configs = Helper::get_configs();
            $this->default_configs = Helper::get_default_configs();

            $this->predefined_configs = array(
                array(
                    'migration_debug'             => 1,
                    'migration_archive_type'      => $this->default_configs['TENWEB_MIGRATION_ARCHIVE_TYPE_DEFAULT'],
                    'migration_max_files_restart' => $this->default_configs['TENWEB_MIGRATION_MAX_FILES_RESTART_DEFAULT'] / 2,
                    'migration_bulk_files_count'  => $this->default_configs['TENWEB_MIGRATION_BULK_FILES_COUNT_DEFAULT'] / 2
                ),
                array(
                    'migration_debug'             => 1,
                    'migration_archive_type'      => $this->default_configs['TENWEB_MIGRATION_ARCHIVE_TYPE_DEFAULT'],
                    'migration_max_files_restart' => $this->default_configs['TENWEB_MIGRATION_MAX_FILES_RESTART_DEFAULT'] / 4,
                    'migration_bulk_files_count'  => $this->default_configs['TENWEB_MIGRATION_BULK_FILES_COUNT_DEFAULT'] / 4
                ),
                array(
                    'migration_debug'             => 1,
                    'migration_archive_type'      => $this->default_configs['TENWEB_MIGRATION_ARCHIVE_TYPE_DEFAULT'],
                    'migration_max_files_restart' => $this->default_configs['TENWEB_MIGRATION_MAX_FILES_RESTART_DEFAULT'] * 4,
                    'migration_bulk_files_count'  => $this->default_configs['TENWEB_MIGRATION_BULK_FILES_COUNT_DEFAULT'] * 4
                ),
                array(
                    'migration_debug'             => 1,
                    'migration_archive_type'      => $this->default_configs['TENWEB_MIGRATION_ARCHIVE_TYPE_DEFAULT'],
                    'migration_max_files_restart' => $this->default_configs['TENWEB_MIGRATION_MAX_FILES_RESTART_DEFAULT'] * 2,
                    'migration_bulk_files_count'  => $this->default_configs['TENWEB_MIGRATION_BULK_FILES_COUNT_DEFAULT'] * 2
                ),
                array(
                    'migration_debug'             => 1,
                    'migration_archive_type'      => $this->default_configs['TENWEB_MIGRATION_RETRY_ARCHIVE_TYPE'],
                    'migration_max_files_restart' => $this->default_configs['TENWEB_MIGRATION_MAX_FILES_RESTART_DEFAULT'],
                    'migration_bulk_files_count'  => $this->default_configs['TENWEB_MIGRATION_MAX_FILES_RESTART_DEFAULT']
                ),
                array(
                    'migration_debug'             => 1,
                    'migration_archive_type'      => $this->default_configs['TENWEB_MIGRATION_RETRY_ARCHIVE_TYPE'],
                    'migration_max_files_restart' => $this->default_configs['TENWEB_MIGRATION_MAX_FILES_RESTART_DEFAULT'] / 2,
                    'migration_bulk_files_count'  => $this->default_configs['TENWEB_MIGRATION_BULK_FILES_COUNT_DEFAULT'] / 2
                ),
                array(
                    'migration_debug'             => 1,
                    'migration_archive_type'      => $this->default_configs['TENWEB_MIGRATION_RETRY_ARCHIVE_TYPE'],
                    'migration_max_files_restart' => $this->default_configs['TENWEB_MIGRATION_MAX_FILES_RESTART_DEFAULT'] / 4,
                    'migration_bulk_files_count'  => $this->default_configs['TENWEB_MIGRATION_BULK_FILES_COUNT_DEFAULT'] / 4
                ),
                array(
                    'migration_debug'             => 1,
                    'migration_archive_type'      => $this->default_configs['TENWEB_MIGRATION_RETRY_ARCHIVE_TYPE'],
                    'migration_max_files_restart' => $this->default_configs['TENWEB_MIGRATION_MAX_FILES_RESTART_DEFAULT'] * 4,
                    'migration_bulk_files_count'  => $this->default_configs['TENWEB_MIGRATION_BULK_FILES_COUNT_DEFAULT'] * 4
                ),
                array(
                    'migration_debug'             => 1,
                    'migration_archive_type'      => $this->default_configs['TENWEB_MIGRATION_RETRY_ARCHIVE_TYPE'],
                    'migration_max_files_restart' => $this->default_configs['TENWEB_MIGRATION_MAX_FILES_RESTART_DEFAULT'] * 2,
                    'migration_bulk_files_count'  => $this->default_configs['TENWEB_MIGRATION_BULK_FILES_COUNT_DEFAULT'] * 2
                ),
            );

        }


        /**
         *
         */
        public function retry()
        {
            /* Helper::store_migration_log('retry_start', 'Entering retry function');

             $config = $this->predefined_configs[$this->retry_count];
             $this->retry_count++;

             $is_restart = get_site_option('tenweb_migration_restart', 0);
             $is_ended = get_site_option('tenweb_migration_ended', 0);*/

            $last_error = error_get_last();
            $error = 'Cannot get last error';
            if (isset($last_error['message'])) {
                $error = $last_error['message'] . ' in ' . $last_error['file'] . ' on line ' . $last_error['line'];
            }
            Helper::store_migration_log('migration_shutdown', 'Trying to retry migration, last error: ' . $error);

            /* if ($this->retry_count < count($this->predefined_configs) && $is_ended == 0 && $is_restart == 0) {
                 Helper::store_migration_log('current_retry_count', $this->retry_count - 1);
                 Helper::store_migration_log('current_retry_config', 'Archive Type: ' . $config['migration_archive_type'] . '. Bulk Files: ' . $config['migration_bulk_files_count'] . '. Max Files Restart: ' . $config['migration_max_files_restart']);

                 Helper::store_migration_log('retry_is_restart_db_param', $is_restart);
                 Helper::store_migration_log('retry_is_ended_db_param', $is_ended);

                 Helper::store_migration_log('retry_migration', 'Trying to retry migration, last error: ' . $error);
                 Helper::save_configs($config);

                 Helper::store_migration_log('retry_save_configs', 'Configs saved');

                 $this->full_restart();
             } else {
                 Helper::store_migration_log('retry_no_need', 'Entering retry function');
             }*/
        }


        /**
         *
         */
        public function full_restart()
        {
            Helper::store_migration_log('retry_full_restart', 'Enter full restart');
            $this->write_migration_run_file();
            Helper::store_migration_log('retry_write_migration_run_file', 'Done');

            $url = add_query_arg(array('rest_route' => '/' . TENWEB_REST_NAMESPACE . '/create_migration_file'), get_home_url() . "/");
            Helper::store_migration_log('retry_add_query_arg', 'Done');
            wp_remote_post($url, array(
                    'method'  => 'POST',
                    'timeout' => 0.1,
                    'body'    => array(
                        'tenweb_nonce'    => wp_create_nonce('wp_rest'),
                        'migration_retry' => 1,
                    )
                )
            );
            Helper::store_migration_log('retry_wp_remote_post', 'Done');
        }


        /**
         *
         */
        public function write_migration_run_file()
        {
            $content = serialize($this);
            file_put_contents(Helper::get_tmp_dir(true) . '/migration_run.txt', $content);

        }

        /**
         * @param MigrationConfig $migration_config
         *
         * @return bool
         */
        public function run_config(MigrationConfig $migration_config)
        {
            try {
                Helper::store_migration_log('start_run_config', 'Starting run_config function.');
                // run config migration
                $config_json_path = $migration_config->run();

                if (!file_exists($config_json_path)) {
                    throw new \Exception("Config file not found");
                }

                Helper::store_migration_log('end_run_config', 'End run_config function.');

                return true;

            } catch (\Exception $e) {
                // response errors
                $this->set_error($e->getMessage());
                // log errors
                Helper::store_migration_log('run_config_exception', $e->getMessage());
                Helper::set_error_log('migration_error', $e->getMessage());

                // if something is wrong we execute role back
                MigrationConfig::rollback();
            }

            return false;
        }


        /**
         * @param string $run_type
         * @param string $password
         * @param string $iv
         *
         * @return bool
         */
        public function run_db($run_type, $password = null, $iv = null)
        {
            try {
                if ($run_type == "run") {
                    Helper::store_migration_log('start_run_db', 'Starting run_db function.');

                    $migration_db = new MigrationDB($password, $iv);
                    $db_path = $migration_db->run($run_type);

                    if (!file_exists($db_path)) {
                        throw new \Exception("Db file not found");
                    }

                    Helper::store_migration_log('end_run_db', 'End run_db function.');

                    return true;
                }

                if ($run_type == 'restart') {
                    Helper::store_migration_log('run_type_db_' . current_time('timestamp'), 'Run type is restart in db.');
                    $migration_db = Migration::get_object_file_content();
                    $migration_db->run($run_type);
                }

            } catch (\Exception $e) {
                // response errors
                $this->set_error($e->getMessage());
                // log errors
                Helper::store_migration_log('run_db_exception', $e->getMessage());
                Helper::set_error_log('migration_error', $e->getMessage());

                // if something is wrong we execute role back
                MigrationDB::rollback();
            }

            return false;
        }

        /**
         * @param $run_type
         *
         * @return bool|string
         */
        public function run_content($run_type)
        {
            try {
                Helper::store_migration_log('start_run_content_' . current_time('timestamp'), 'Starting run_content function.');

                // run content migration
                MigrationContent::set_up($run_type);

                Helper::store_migration_log('end_run_content_' . current_time('timestamp'), 'End run_content function.');

                return Helper::get_tmp_dir() . "/" . MigrationContent::getMigrationArchive();

            } catch (\Exception $e) {
                // response errors
                $this->set_error($e->getMessage());

                // log errors
                Helper::store_migration_log('run_content_exception', $e->getMessage());
                Helper::set_error_log('migration_error', $e->getMessage());
            }

            return false;
        }

        /**
         *
         * @param string $run_type
         *
         * @return bool|array
         */
        public function run_upload_archive_to_s3($run_type = 'run')
        {
            if ($this->configs['TENWEB_MIGRATION_UPLOAD_ARCHIVE_S3']) {
                $archives_count = get_site_option('tenweb_migration_archive_count');
                Helper::store_migration_log('started_upload_archive_to_s3_' . current_time('timestamp'), 'Start run_upload_archive_to_s3 function.');

                $s3_archive_keys = get_site_transient('tenweb_s3_archive_keys', array());
                try {
                    include_once TENWEB_INCLUDES_DIR . '/class-migration-multipart-upload.php';
                    $migration_files = [];
                    if ($this->configs['TENWEB_MIGRATION_MULTIPLE_ARCHIVES']) {
                        for ($i = 1; $i <= $archives_count; $i++) {
                            $migration_files[] = Helper::get_tmp_dir() . "/" . MigrationContent::getMigrationArchive($i);
                        }
                    } else {
                        $migration_files[] = Helper::get_tmp_dir() . "/" . MigrationContent::getMigrationArchive();
                    }
                    $archive_index = (int)get_site_option('tenweb_migration_archive_current_index_for_s3', 0);

                    if ($run_type == 'run') {
                        $aws_credentials = MigrationMultiPartUpload::getAmazonCredentials();
                        $initiate_upload = true;
                    } else {
                        $aws_credentials = null;
                        $initiate_upload = false;
                    }

                    for ($i = $archive_index; $i < count($migration_files); $i++) {
                        update_site_option('tenweb_migration_archive_current_index_for_s3', $i);
                        $archive_url = MigrationMultiPartUpload::run($migration_files[$i], $aws_credentials, $run_type, $initiate_upload);
                        Helper::store_migration_log('archive_to_s3_url' . current_time('timestamp'), $archive_url);
                        $parsed_archive_url = parse_url($archive_url);
                        if ($parsed_archive_url['path']) {
                            $s3_archive_keys[] = ltrim($parsed_archive_url['path'], '/');
                        }

                        set_site_transient('tenweb_s3_archive_keys', $s3_archive_keys);

                        $initiate_upload = true;
                    }

                    Helper::store_migration_log('end_upload_archive_to_s3_' . current_time('timestamp'), 'End run_upload_archive_to_s3 function.');

                    return $s3_archive_keys;
                } catch (\Exception $e) {
                    // response errors
                    $this->set_error($e->getMessage());

                    Helper::store_migration_log('error_migration_archive_uploading_to_s3', $e->getMessage());
                    Helper::set_error_log('migration_error', $e->getMessage());
                }

                return false;
            }

            return true;
        }

        /**
         * @param      $migration_file
         * @param      $start_byte
         * @param      $chunk_size
         * @param bool $is_test
         */
        public function migrate($migration_file, $start_byte, $chunk_size, $is_test = false)
        {
            try {
                if (!file_exists($migration_file)) {
                    throw new \Exception("Migration archive not found");
                }
                $this->download_archive($migration_file, $start_byte, $chunk_size, $is_test);

            } catch (\Exception $e) {
                $this->set_error($e->getMessage());
            }

        }


        /**
         * function for loading migration classes
         */
        public function load_classes()
        {
            add_action('shutdown', array($this, 'retry'));

            include_once TENWEB_INCLUDES_DIR . '/class-migration.php';
            include_once TENWEB_INCLUDES_DIR . '/class-migration-db.php';
            include_once TENWEB_INCLUDES_DIR . '/class-migration-config.php';
            include_once TENWEB_INCLUDES_DIR . '/class-migration-content.php';
            include_once TENWEB_DIR . '/vendor/autoload.php';
        }


        /**
         * @param $error
         */
        public function set_error($error)
        {
            $this->error = $error;
        }

        /**
         * @return array
         */
        public function get_error()
        {
            return $this->error;
        }

        /**
         * @param $archives_count
         *
         * @return bool|string
         *
         * @throws \Exception
         */
        private function migration_file_created($archives_count)
        {
            include_once(TENWEB_INCLUDES_DIR . '/class-tenweb-services.php');

            $migrate_domain_id = get_site_transient('tenweb_migrate_domain_id');
            if (!$migrate_domain_id) {
                $url = TENWEB_API_URL . '/migrate-files';
            } else {
                $url = TENWEB_API_URL . '/domains/' . $migrate_domain_id . '/migrate-files';
            }
            $subdomain = get_site_transient('tenweb_subdomain');
            $region = get_site_transient('tenweb_migrate_region');
            $live = get_site_transient('tenweb_migrate_live');
            $tp_domain_name = get_site_transient('tenweb_tp_domain_name');

            if (!$subdomain) {
                $subdomain = null;
            }

            if (!$region) {
                $region = null;
            }

            $file_extension_array = explode('.', MigrationContent::getMigrationArchive());
            $file_extension = $file_extension_array[1];


            $args = array(
                "method"  => "POST",
                "timeout" => 50000,
                "body"    => array(
                    "domain_id"      => \TenwebServices::get_domain_id(),
                    "domain_name"    => site_url(),
                    "subdomain"      => $subdomain,
                    "region"         => $region,
                    "live"           => $live,
                    "file_extension" => $file_extension,
                    "encrypted"      => 0
                )
            );

            if (!empty($tp_domain_name)) {
                $args['body']['tp_domain_name'] = $tp_domain_name;
            }

            if ($this->configs['TENWEB_MIGRATION_ENCRYPT_DB'] && function_exists('openssl_encrypt')) {
                $args['body']['encrypted'] = 1;
            }

            $tmp_dir = Helper::get_tmp_dir();

            if ($this->configs['TENWEB_MIGRATION_MULTIPLE_ARCHIVES']) {
                $args['body']['archives_count'] = $archives_count;
                $max_archive_size = 0;

                for ($i = 1; $i <= $archives_count; $i++) {
                    $current_archive_size = (integer)filesize($tmp_dir . "/" . MigrationContent::getMigrationArchive($i));
                    if ($current_archive_size > $max_archive_size) {
                        $max_archive_size = $current_archive_size;
                    }
                }

                $args['body']['file_size'] = $max_archive_size;
            } else {
                $args['body']['file_size'] = (integer)filesize($tmp_dir . "/" . MigrationContent::getMigrationArchive());
            }

            if ($this->configs['TENWEB_MIGRATION_UPLOAD_ARCHIVE_S3']) {
                $s3_archive_keys = get_site_transient('tenweb_s3_archive_keys');
                if ($s3_archive_keys) {
                    $args['body']['s3_archive_keys'] = $s3_archive_keys;
                }
            }

            $result = \TenwebServices::do_request($url, $args);

            if (is_wp_error($result) || wp_remote_retrieve_response_code($result) !== 200) {
                $this->set_error($result["response"]["message"]);
                throw new \Exception("Error while sending status to manager service: " . $result["response"]["message"]);
            }

            delete_site_transient('tenweb_subdomain');
            delete_site_transient('tenweb_migrate_live');
            delete_site_transient('tenweb_migrate_domain_id');
            delete_site_transient('tenweb_migrate_region');
            delete_site_transient('tenweb_tp_domain_name');
            delete_site_transient('tenweb_s3_archive_keys');

            return true;
        }

        /**
         * @param      $file
         *
         * @param      $start_byte
         * @param      $chunk_size
         *
         * @param bool $is_test
         *
         * @throws \Exception
         */
        private function download_archive($file, $start_byte, $chunk_size, $is_test = false)
        {
            @error_reporting(0);
            $handle = fopen($file, 'rb');
            if ($handle === false) {
                throw new \Exception("Can not open " . $file);
            }

            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            // set headers

            header("Content-Description: File Transfer");
            header("Content-Type:  application/octet-stream");
            header("Content-disposition: attachment; filename=\"" . basename($file) . "\"");
            header("Content-Transfer-Encoding: Binary");
            header("Connection: Keep-Alive");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: public");
            header("Content-Length: " . filesize($file));
            header("Access-Control-Allow-Headers: Authorization, Content-Type, Accept-Ranges");
            header("Accept-Ranges: bytes");

            //OLD WAY
            //readfile($file);

            fseek($handle, $start_byte);
            #readfile($file);
            echo fread($handle, $chunk_size);

            fclose($handle);

            //fclose($handle);

            // after all remove file
            //$this->remove_file($file);
            if (!$is_test) {
                exit();
            }
        }

        /**
         * function for removing file
         *
         * @param bool $path
         */
        private function remove_file($path)
        {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        public function end_migration()
        {
            try {
                $archives_count = get_site_option('tenweb_migration_archive_count');
                // tell manager service, that migration file was created
                $this->migration_file_created($archives_count);

                // remove files
                $this->remove_file(Helper::get_tmp_dir() . "/" . MigrationContent::MIGRATION_CONFIG_FILE_NAME);

                $this->remove_file(Helper::get_tmp_dir() . "/" . MigrationContent::MIGRATION_DB_FILE_NAME);

                $this->remove_file(Helper::get_tmp_dir() . "/content_object.txt");

                update_site_option('tenweb_migration_ended', 1);


            } catch (\Exception $e) {
                // response errors
                $this->set_error($e->getMessage());

                // log errors
                Helper::store_migration_log('end_migration_exception', $e->getMessage());
                Helper::set_error_log('migration_error', $e->getMessage());
            }

            return false;
        }

    }
}
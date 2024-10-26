<?php


namespace Tenweb_Manager {

    use Araqel\Archive\Archive as Archive;

    class MigrationContent extends Migration
    {

        private $archive_path = null;
        private $files = null;
        private $files_count = null;
        private $total_files_count = null;
        private $method;
        private $archive;
        private $uploads_dir;
        private $current_archive_index;


        public function __construct()
        {

            parent::__construct();
            // if everything ok, create zip or tar archive
            $this->archive_path = $this->archive_dir . "/" . self::getMigrationArchive();
            $this->method = Helper::get_migration_archive_type();
            $this->current_archive_index = 1;
        }

        /**
         * @param string $run_type
         *
         * @return int
         * @throws \Exception
         */
        public static function set_up($run_type = 'run')
        {
            if ($run_type == 'run') {
                Helper::store_migration_log('run_type_' . current_time('timestamp'), 'Run type is run.');
                $migration_content = new self();

                return $migration_content->run($run_type);
            }

            if ($run_type == 'restart') {
                Helper::store_migration_log('run_type_' . current_time('timestamp'), 'Run type is restart.');
                $migration_content = Migration::get_object_file_content();

                return $migration_content->run($run_type);
            }
        }


        /**
         * @param $run_type
         *
         * @return int
         * @throws \Exception
         */
        public function run($run_type)
        {
            $this->uploads_dir = Helper::get_uploads_dir();

            if ($this->configs['TENWEB_MIGRATION_MULTIPLE_ARCHIVES']) {
                $this->archive_path = $this->archive_dir . "/" . self::getMigrationArchive($this->current_archive_index);
            }

            $this->archive = Archive::create($this->archive_path, $this->method);

            if ($run_type == "run") {
                Helper::store_migration_log('wp_content_dir', WP_CONTENT_DIR);
                Helper::store_migration_log('uploads_dir', $this->uploads_dir);

                // check if zip or zlib extension exists
                if ($this->check_if_zip_extension_exists() === false && $this->check_if_zlib_extension_exists() === false) {
                    throw new \Exception("PHP zip or zlib extension is missing");
                }

                // check if config json exists
                if (!file_exists($this->archive_dir . "/" . self::MIGRATION_CONFIG_FILE_NAME)) {
                    throw new \Exception("Config json file is missing");
                }

                // check if db sql exists
                if (!file_exists($this->archive_dir . "/" . self::MIGRATION_DB_FILE_NAME)) {
                    throw new \Exception("Database file is missing");
                }
                $this->files = $this->get_content_files();

                Helper::store_migration_log('add_meta_data_to_archive', "Adding meta data..");
                $conf = $this->archive_dir . "/" . self::MIGRATION_CONFIG_FILE_NAME;
                $db = $this->archive_dir . "/" . self::MIGRATION_DB_FILE_NAME;
                Helper::store_migration_log('meta_conf', $conf);
                Helper::store_migration_log('meta_db', $db);

                $this->archive->addFiles(array($conf, $db), '10web_meta', $this->archive_dir);
                Helper::store_migration_log('added_meta_data_to_archive', "Meta data added..");
            }

            Helper::store_migration_log('start_create_archive', $this->archive_path);
            $this->archive->setIgnoreRegexp($this->get_exclude_regex());
            $this->create_archive();

            update_site_option('tenweb_migration_archive_count', $this->current_archive_index);

            return $this->current_archive_index;
        }

        /**
         * @return bool
         */
        private function check_if_zip_extension_exists()
        {
            if (!extension_loaded('zip')) {
                return false;
            }

            Helper::store_migration_log('zip_extension_exists', 'Zip extension exists.');

            return true;
        }

        /**
         * @return bool
         */
        private function check_if_zlib_extension_exists()
        {
            if (!extension_loaded('zlib')) {
                return false;
            }

            Helper::store_migration_log('zlib_extension_exists', 'Zlib extension exists.');

            return true;
        }

        /**
         * @return array
         */
        private function get_content_files()
        {
            Helper::store_migration_log('start_get_content_files', 'Starting get_content_files function.');
            $all_files = array();

            // get wp-content files
            $all_files['wp-content'] = $this->get_chunks(WP_CONTENT_DIR, 'wpcontent');

            // get media files
            if (strpos($this->uploads_dir, WP_CONTENT_DIR) === false) {
                $uploads_dir_basename = str_replace(ABSPATH, '', $this->uploads_dir);
                $all_files[$uploads_dir_basename] = $this->get_chunks($this->uploads_dir, 'uploads');
            }

            Helper::store_migration_log('end_get_content_files', 'End get_content_files function.');

            return $all_files;
        }

        /**
         * @param $dir
         *
         * @return array
         */
        private function get_files($dir)
        {
            $files = array();
            $skipped_files = array();

            $innerIterator = new \RecursiveDirectoryIterator($dir, \RecursiveIteratorIterator::LEAVES_ONLY);
            $filter = $this->get_filter();
            $iterator = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator($innerIterator, $filter));

            foreach ($iterator as $file) {
                $file_path = $file->getRealPath();

                if (!is_dir($file_path)) {
                    try {
                        $file_size = $file->getSize();
                    } catch (\Exception $e) {
                        $file_size = -1;
                    }

                    if ($file_size != -1 && $file_size < $this->configs['TENWEB_MIGRATION_FILE_SIZE_LIMIT']) {
                        $files[] = $file_path;
                    } else {
                        $skipped_files[] = $file_path;
                    }

                } else if ($this->dir_is_empty($file_path)) {
                    $files[] = $file_path;
                }
            }

            if (count($skipped_files)) {
                Helper::store_migration_log('skipped_files', json_encode($skipped_files));
            }

            return array_unique($files);
        }

        /**
         * @param $dir
         *
         * @return array
         */
        private function get_chunks($dir, $type)
        {
            $unique_files = $this->get_files($dir);

            $files = array();
            $i = 0;
            $total_files_count = 0;
            $bulk_files = array();

            if (!empty($unique_files)) {
                foreach ($unique_files as $key => $file_path) {
                    $bulk_files[] = $file_path;
                    $i++;

                    if ($i == $this->configs['TENWEB_MIGRATION_BULK_FILES_COUNT']) {
                        $files[] = $bulk_files;
                        $total_files_count += $i;
                        $i = 0;
                        $bulk_files = array();
                    }
                }
            }

            if ($i > 0) {
                $files[] = $bulk_files;
                $total_files_count += $i;
            }

            $this->total_files_count = $total_files_count;
            Helper::update_migration_state('total_files_count', $total_files_count);
            Helper::store_migration_log('total_files_count_in_archive_in_' . $type, $total_files_count . ' files in ' . $dir);

            return $files;
        }

        private function get_exclude_regex()
        {
            $excluded_files = array(
                'imagecache',
                'wp\-content\/w3tc',
                'wp\-content\/w3\-',
                'wp\-content\/wflogs',
                'wp\-content\/mu\-plugins\/sg\-cachepress',
                'wp\-content\/plugins\/sg\-cachepress',
                'wp\-content\/mu\-plugins\/wpmudev\-hosting\.php',
                'wp\-content\/mu\-plugins\/wpmudev\-hosting',
                'wp\-content\/mu\-plugins\/wpengine\-security\-auditor\.php',
                'wp\-content\/mu\-plugins\/wpe\-wp\-sign\-on\-plugin',
                'wp\-content\/mu\-plugins\/wpe\-wp\-sign\-on\-plugin\.php',
                'wp\-content\/mu\-plugins\/wpe_bnseosnvlsoier_private_ips\.php',
                'wp\-content\/mu\-plugins\/slt\-force\-strong\-passwords\.php',
                'wp\-content\/mu\-plugins\/stop\-long\-comments\.php',
                'wp\-content\/mu\-plugins\/force\-strong\-passwords',
                'wp\-content\/object\-cache\.php$',
                'wp\-content\/envato\-backups',
                'wp\-content\/Dropbox_Backup',
                'wp\-content\/et\-cache',
                'wp\-content\/backup\-db',
                'wp\-content\/backup$',
                'wp\-content\/upready$',
                'wp\-content\/db$',
                "wp\-content\/.*\-wprbackups$",
                "wp\-content\/.*\-backups$",
                'wp\-content\/updraft$',
                'wp\-content\/updraftplus$',
                'wp\-content\/wpvividbackups',
                'wp\-content\/ew\-backup',
                'wp\-content\/wphb\-cache',
                'wp\-content\/wpo\-cache',
                '\.htaccess',
                '\._htaccess',
                'wp\-config\-sample\.php',
                'mu\-plugins\/wpengine\-common',
                'mu\-plugins\/mu\-plugin\.php',
                'mu\-plugins\/kinsta\-mu\-plugins\.php',
                '\.svn$',
                '\.git$',
                '\.log$',
                '\.tmp$',
                '\.listing$',
                '\.cache$',
                '\.bak$',
                '\.swp$',
                '\~',
                '_wpeprivate',
                'wp\-content\/cache',
                'wp\-content\/cache_old',
                'ics\-importer\-cache',
                'gt\-cache',
                'plugins\/wpengine\-snapshot\/snapshots',
                'wp\-content\/backups',
                'wp\-content\/managewp',
                'wp\-content\/upgrade',
                'kinsta\-mu\-plugins',
                'wp\-content\/advanced\-cache\.php',
                'wp\-content\/wp\-cache\-config\.php',
                'wp\-content\/advanced\-cache\.php',
                'wp\-content\/wp\-cache\-config\.php',
                'ai1wm\-backups$',
                'uploads\/snapshots',
                'uploads\/backup',
                'uploads\/backups',
                'uploads\/em\-cache',
                'uploads\/mainwp\/backup',
                'uploads\/wp\-file\-manager\-pro\/fm_backup',
                'uploads\/ewpt_cache',
                'uploads\/ShortpixelBackups',
                'uploads\/backupbuddy_backups',
                'uploads\/backupbuddy_temp',
                'uploads\/webarx\-backup',
                'uploads\/iw\-backup',
                'uploads\/fw\-backup',
                'uploads\/10web_tmp',
                'uploads\/wp\-clone',
                'uploads\/cache',
                'wp\-content\/bps\-backup',
                'wp\-content\/wptouch\-data',
                'aiowps_backups$',
                'aiowps\-backups$',
                'mu\-plugins\/wp\-stack\-cache\.php',);

            $excluded_files[] = preg_quote($this->archive_dir, '/');

            return "/(" . implode("|", $excluded_files) . ")/i";
        }

        private function get_filter()
        {
            $path_regex = $this->get_exclude_regex();

            $filter = function ($file, $key, $iterator) use ($path_regex) {
                return !preg_match($path_regex, $file->getRealPath(), $matches);
            };

            return $filter;
        }

        private function create_archive()
        {
            foreach ($this->files as $type => &$files) {
                if (!empty($files)) {
                    foreach ($files as $key => $files_chunk) {
                        unset($files[$key]);

                        if ($type == "wp-content") {
                            $addDir = 'wp-content';

                            if (WP_CONTENT_DIR == '/wp-content') {
                                $rmdir = "/";
                                $addDir = "";
                            } else if (WP_CONTENT_DIR == '//wp-content' || WP_CONTENT_DIR == '//wp-content/') {
                                $rmdir = "//";
                                $addDir = "";
                            } else {
                                $rmdir = WP_CONTENT_DIR;
                            }
                        } else {
                            $addDir = 'wp-content/uploads';

                            if ($this->uploads_dir == '/wp-content/uploads') {
                                $rmdir = "/";
                                $addDir = "";
                            } else if ($this->uploads_dir == '//wp-content/uploads' || $this->uploads_dir == '//wp-content/uploads/') {
                                $rmdir = "//";
                                $addDir = "";
                            } else {
                                $rmdir = $this->uploads_dir;
                            }
                        }

                        $this->archive->addFiles($files_chunk, $addDir, $rmdir);

                        $this->files_count += count($files_chunk);
                        Helper::update_migration_state('current_files_count', $this->files_count);
                        Helper::store_migration_log('current_files_count_in_archive', $this->files_count);

                        if ($this->files_count >= $this->total_files_count) {
                            //update_site_option('tenweb_migration_ended', 1);
                            update_site_option('tenweb_migration_content_ended', 1);
                        } else {
                            $this->check_for_restart();
                        }
                    }
                }
            }

            $this->archive = null;
            Helper::store_migration_log('end_create_archive', 'End create archive function.');
        }

        /**
         * @return bool
         */
        private function check_for_restart()
        {
            $max_exec_time_server = ini_get('max_execution_time');
            $start = get_site_transient(TENWEB_PREFIX . "_migration_start_time");
            $script_exec_time = microtime(true) - $start;

            if ($script_exec_time >= ((int)$max_exec_time_server - $this->configs['TENWEB_MIGRATION_EXEC_TIME_OFFSET']) || ($this->files_count != 0 && $this->files_count % $this->configs['TENWEB_MIGRATION_MAX_FILES_RESTART'] == 0)) {
                $this->write_object_file();
                $this->restart();

                return false;
            }
        }

        private function write_object_file()
        {
            $t = current_time('timestamp');
            Helper::store_migration_log('start_write_object_file_' . $t, 'Starting write object file in content.');
            $this->archive = null;
            $this->current_archive_index++;
            $content = serialize($this);
            Helper::store_migration_log('serialized_object_content' . $t, 'Object serialized.');
            file_put_contents($this->archive_dir . '/content_object.txt', $content);
            Helper::store_migration_log('end_write_object_file_' . $t, 'End write object file in content.');
        }

        /**
         * @param $dir
         *
         * @return bool
         */
        public function dir_is_empty($dir)
        {
            $handle = opendir($dir);
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    return false;
                }
            }

            return true;
        }
    }
}
<?php

namespace Tenweb_Manager {

    class MigrationDB extends Migration
    {

        private $password;
        private $iv;
        private $tables;
        private $current_table_key;
        private $current_insert_data_key;
        private $current_rows_count = 0;
        private $wpdb = null;


        public function __construct($password, $iv)
        {
            parent::__construct();

            $this->password = $password;
            $this->iv = $iv;
            $this->tables = null;
            $this->current_table_key = 0;
            $this->current_insert_data_key = 0;
        }

        /**
         * @param $run_type
         *
         * @return bool
         * @throws \Exception
         */
        public function run($run_type)
        {
            global $wpdb;
            $this->wpdb = $wpdb;

            if ($run_type == 'run') {
                Helper::store_migration_log('run_db_run_type', 'Run db type is run');
                $this->tables = $this->get_tables();
            } else {
                Helper::store_migration_log('run_db_run_type', 'Run db type is restart. current_table_key: ' . $this->current_table_key . '; current_insert_data_key: ' . $this->current_insert_data_key);
            }

            $file_to_save_database_data = $this->archive_dir . "/" . self::MIGRATION_DB_FILE_NAME;

            if (($db_file = fopen($file_to_save_database_data, "a+")) !== false) {
                if ($run_type == 'run') {
                    Helper::update_migration_state('db_tables_count', count($this->tables));
                    // add sql headers
                    $sql_header_string = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";" . PHP_EOL . "SET time_zone = \"+00:00\";" . PHP_EOL . "SET foreign_key_checks = 0;" . PHP_EOL;
                    $this->add_to_file($db_file, $sql_header_string . PHP_EOL);
                }

                $this->current_rows_count = 0;
                foreach ($this->tables as $key => $table) {
                    if ($this->current_table_key > $key) {
                        Helper::store_migration_log('skip_table_' . $key, 'Skip table ' . $table);
                        continue;
                    }

                    if (!$this->current_insert_data_key) {
                        // get create table syntax
                        if (($create_table = $this->get_create_table_str($table)) !== false) {
                            // change table engine to innodb
                            $create_table = $this->get_altered_engine_str($create_table);

                            // run drop table if table exists
                            $drop_string = "DROP TABLE IF EXISTS `{$table}`";
                            $this->add_to_file($db_file, $drop_string . ';' . PHP_EOL);
                            // run create table
                            $this->add_to_file($db_file, $create_table . ';' . PHP_EOL . PHP_EOL);

                            // if table has data, run insert data
                            $this->insert_data($table, $db_file);
                        }
                    } else {
                        $this->insert_data($table, $db_file);
                    }


                    $this->current_table_key++;
                    Helper::update_migration_state('inserted_tables_count', $this->current_table_key);
                }

                if ($this->configs['TENWEB_MIGRATION_ENCRYPT_DB'] && function_exists('openssl_encrypt') && !empty($this->password) && !empty($this->iv)) {
                    $this->encrypt_db_file($db_file, $file_to_save_database_data);
                } else {
                    // after all close file
                    fclose($db_file);
                }

                update_site_option('tenweb_migration_db_backup_ended', 1);

                return $file_to_save_database_data;
            } else {
                throw new \Exception("Unable open database sql file");
            }
        }


        /**
         * @param $table_name
         *
         * @return bool|string
         * @throws \Exception
         */
        private function insert_data($table_name, $db_file)
        {
            $columns = $this->get_table_structure($table_name);
            $table_columns = $this->get_table_columns($columns);

            $table_name = stripslashes($table_name);
            $table_record_count = $this->wpdb->get_row('SELECT count(1) FROM ' . $table_name, ARRAY_N);
            $select_limit = $this->configs['TENWEB_MIGRATION_BULK_DB_ROWS_COUNT'];
            $select_count = (int)ceil($table_record_count[0] / $select_limit);

            for ($select_i = 0; $select_i < $select_count; $select_i++) {
                if ($this->current_insert_data_key > $select_i) {
                    continue;
                }

                $offset = $select_i * $select_limit;

                if ($select_count == 1 || $offset == 0) {
                    Helper::store_migration_log('insert_data_table_' . $table_name, 'Adding data for ' . $table_name . ' table ');
                } else {
                    if ($offset >= $table_record_count[0]) {
                        $backed_up_rows = $table_record_count[0];
                    } else {
                        $backed_up_rows = $offset;
                    }

                    Helper::store_migration_log('insert_data_table_' . $select_i . '_' . $table_name, 'Adding data for ' . $table_name . ' table (' . $backed_up_rows . '/' . $table_record_count[0] . ' rows) ');
                }

                $table_data = $this->wpdb->get_results('SELECT * FROM ' . $table_name . ' LIMIT ' . $offset . ', ' . $select_limit, ARRAY_A);

                $insert_data = "INSERT INTO `{$table_name}` (" . implode(",", $table_columns) . ") VALUES ";

                if ($table_data) {
                    $i = 0;
                    $j = 1;
                    $current_insert_data = $insert_data;

                    foreach ($table_data as $table_row) {
                        $insert_values = array();

                        foreach ($table_row as $field_name => $field_value) {
                            if (empty($field_value)) {
                                if ($columns[$field_name]["Null"] != "NO") {
                                    $insert_values[$field_name] = 'NULL';
                                } else {
                                    if (strpos(strtolower($columns[$field_name]["Type"]), "int") !== false || $field_value === '0') {
                                        $insert_values[$field_name] = '0';
                                    } else {
                                        $insert_values[$field_name] = "''";
                                    }
                                }
                            } else {
                                $insert_values[$field_name] = "'" . addslashes($field_value) . "'";
                            }
                        }

                        $current_insert_data .= "(" . implode(",", array_values($insert_values)) . "),";

                        $i++;

                        if ($i == $this->configs['TENWEB_MIGRATION_BULK_DB_ROWS_COUNT']) {
                            Helper::store_migration_log('row_limit_' . $j, 'Rows limit reached, adding to file.');
                            $current_insert_data = rtrim($current_insert_data, ",");
                            $this->add_to_file($db_file, $current_insert_data . ';' . PHP_EOL . PHP_EOL);
                            $i = 0;
                            $j++;
                            $current_insert_data = $insert_data;
                            $this->current_insert_data_key++;
                            $this->current_rows_count += $this->configs['TENWEB_MIGRATION_BULK_DB_ROWS_COUNT'];
                            $this->check_for_restart();
                        }
                    }

                    if ($i > 0) {
                        Helper::store_migration_log('rows_remain', 'Rows remain, adding to file.');
                        $current_insert_data = rtrim($current_insert_data, ",");
                        $this->add_to_file($db_file, $current_insert_data . ';' . PHP_EOL . PHP_EOL);
                        $this->current_insert_data_key++;
                        $this->current_rows_count += $i;
                    }
                }

            }

            $this->current_insert_data_key = 0;

            Helper::store_migration_log('end_insert_data', 'End insert_data function for table ' . $table_name);
        }

        /**
         * @param $table_structure
         *
         * @return array
         */
        private function get_table_columns($table_structure)
        {
            $columns = array_keys($table_structure);
            $columns = array_map(function ($value) {
                return "`" . $value . "`";
            }, $columns);

            return $columns;
        }

        /**
         * @param $table_name
         *
         * @return array
         */
        private function get_table_structure($table_name)
        {
            $table_columns = array();
            $table_structure = $this->wpdb->get_results("DESCRIBE `{$table_name}`", ARRAY_A);

            foreach ($table_structure as $column) {
                $table_columns[$column["Field"]] = $column;
            }

            return $table_columns;
        }

        /**
         * @param $table_name
         *
         * @return mixed
         */
        private function get_create_table_str($table_name)
        {
            // get create table syntax
            $create_table = $this->wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);

            return !empty($create_table[1]) ? $create_table[1] : false;
        }

        /**
         * @return array
         */
        private function get_tables()
        {
            Helper::store_migration_log('start_get_tables', 'Start get_tables function.');
            $tables = array();
            $data = $this->wpdb->get_results("SHOW FULL TABLES FROM `{$this->wpdb->dbname}` WHERE Table_Type != 'VIEW'");
            if (!empty($data)) {
                $key = 'Tables_in_' . $this->wpdb->dbname;
                foreach ($data as $table) {
                    if (strpos($table->$key, $this->wpdb->prefix) === 0) {
                        array_push($tables, $table->$key);
                    }
                }
            }

            Helper::store_migration_log('end_get_tables', 'End get_tables function.');

            return $tables;
        }

        /**
         * @param $file
         * @param $data
         *
         * @throws \Exception
         */
        private function add_to_file($file, $data)
        {
            if (fwrite($file, $data) === false) {
                throw new \Exception("Unable write data to db file");
            }
        }


        private function encrypt_db_file($file, $db_file_path)
        {
            $dest = tempnam($this->archive_dir, 'db');

            $key = $this->password;
            //initialization vector
            $iv = $this->iv;
            fseek($file, 0);
            $error = false;
            if ($fpOut = fopen($dest, 'w')) {
                while (!feof($file)) {
                    $plaintext = fread($file, 16 * 1000);
                    $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, null, $iv);
                    fwrite($fpOut, $ciphertext);
                }
                fclose($fpOut);
            } else {
                $error = true;
            }

            unlink($db_file_path);
            rename($dest, $db_file_path);

            return $error ? false : true;
        }

        private function check_for_restart()
        {
            $max_exec_time_server = ini_get('max_execution_time');
            $start = get_site_transient(TENWEB_PREFIX . "_migration_start_time");
            $script_exec_time = microtime(true) - $start;

            if ($this->current_rows_count >= $this->configs['TENWEB_MIGRATION_MAX_DB_ROWS_RESTART'] || $script_exec_time >= ((int)$max_exec_time_server - $this->configs['TENWEB_MIGRATION_EXEC_TIME_OFFSET'])) {
                $this->write_object_file();
                $this->restart();

                return false;
            }
        }

        private function write_object_file()
        {
            Helper::store_migration_log('start_write_object_file_db_' . current_time('timestamp'), 'Starting write object file in db.');
            $this->wpdb = null;
            $content = serialize($this);
            file_put_contents($this->archive_dir . '/content_object.txt', $content);
            Helper::store_migration_log('end_write_object_file_db_' . current_time('timestamp'), 'End write object file in db.');
        }

        private function get_altered_engine_str($create_str, $from_engine = array('MyISAM', 'utf8mb4_0900_ai_ci'), $to_engine = array('InnoDB', 'utf8mb4_general_ci'))
        {
            return str_ireplace($from_engine, $to_engine, $create_str);
        }
    }
}
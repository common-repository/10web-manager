<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 9/13/17
 * Time: 4:33 PM
 */

namespace Tenweb_Manager {


    class RestApi
    {

        private static $instance = null;

        private $namespace = TENWEB_REST_NAMESPACE;

        private $bases = array(

            // these 2 endpoints are for checking tenweb rest api
            'get_check'  => array('/get_check', true, false, false),//get,post,delete
            'post_check' => array('/post_check', false, true, false),//get,post,delete

            'tenweb_state' => array('/tenweb_state', true, false, false),//get,post,delete
            'site_state'   => array('/site_state', true, false, false),//get,post,delete
            'action'       => array('/action', false, true, false),
            'wp_update'    => array('/wp_update', false, true, false),
            'check_domain' => array('/check_domain', false, true, false),
            'templates'    => array('/templates', false, true, false),

            'restart_migration_file' => array('/restart_migration_file', false, true, false),
            'create_migration_file'  => array('/create_migration_file', false, true, false),
            'migrate'                => array('/migrate', true, false, false),
            'logout'                 => array('/logout', false, true, false),
            'remove_migration_file'  => array('/remove_migration_file', false, true, false),
            'check_download'         => array('/check_download', true, false, false),
            'get_migration_state'    => array('/get_migration_state', true, false, false)
        );

        /**
         * Register the routes for the objects of the controller.
         */
        public function register_routes()
        {
            foreach ($this->bases as $key => $route_config) {
                $endpoint = $this->get_endpoint($key);

                // readable
                if ($route_config[1]) {

                    register_rest_route($this->namespace, $endpoint,
                        array(
                            'methods'             => \WP_REST_Server::READABLE,
                            'callback'            => array($this, 'callback'),
                            'permission_callback' => array($this, 'get_items_permissions_check'),
                            'args'                => array(),
                        )
                    );

                }

                // writable
                if ($route_config[2]) {

                    register_rest_route($this->namespace, $endpoint,
                        array(
                            'methods'             => \WP_REST_Server::CREATABLE,
                            'callback'            => array($this, 'callback'),
                            'permission_callback' => array($this, 'create_item_permissions_check'),
                            'args'                => array(),
                        )
                    );

                }

                // deletable
                if ($route_config[3]) {

                    register_rest_route($this->namespace, $endpoint,
                        array(
                            'methods'             => \WP_REST_Server::DELETABLE,
                            'callback'            => array($this, 'callback'),
                            'permission_callback' => array($this, 'delete_item_permissions_check'),
                            'args'                => array(),
                        )
                    );

                }
            }

        }

        public function callback($request)
        {
            $parameters = self::wp_unslash_conditional($request->get_body_params());

            if (is_multisite()) {
                if (!empty($parameters["blog_id"])) {
                    $blog_id = $parameters["blog_id"];
                } else {
                    $blog_id = get_current_blog_id();
                }
                switch_to_blog($blog_id);
            }

            $request_method = $request->get_method();
            if ($request_method == "DELETE") {
                return $this->delete_item($request);
            } else if ($request_method == "POST") {
                return $this->update_item($request);
            } else if ($request_method == "GET") {
                return $this->get_item($request);
            }

            if (is_multisite()) {
                restore_current_blog();
            }

            return false;
        }

        /**
         * Get a collection of items
         *
         * @param \WP_REST_Request $request Full data about the request.
         *
         * @return \WP_Error|\WP_REST_Response
         */
        public function get_item($request)
        {
            $data_for_response = array();
            $headers_for_response = array();

            $route = $request->get_route();
            $parameters = self::wp_unslash_conditional($request->get_query_params());
            $endpoint = $this->parse_endpoint($route);

            if ($endpoint != 'get_check') {
                $login_instance = Login::get_instance();

                if (!$login_instance->check_logged_in()) {
                    $data_for_response = array(
                        "code"    => "unauthorized",
                        "message" => "manager unauthorized, please login",
                        "data"    => array(
                            "status" => 401
                        )
                    );

                    return new \WP_REST_Response($data_for_response, 401);
                }


                $authorize = $this->authorize($request);
                if (is_array($authorize)) {
                    return new \WP_REST_Response($authorize, 401);

                }

                if (get_site_option(TENWEB_PREFIX . '_is_available') !== '1') {
                    update_site_option(TENWEB_PREFIX . '_is_available', '1');
                }
            }
            switch ($endpoint) {
                case 'get_check':
                    $status = 200;
                    $data_for_response = array(
                        "code" => "ok",
                    );
                    break;
                case 'tenweb_state':

                    $status = 200;
                    $data_for_response = array(
                        "code" => "ok",
                        "data" => Helper::get_manager_info()
                    );
                    break;
                case 'site_state':
                    $status = 200;
                    $data_for_response = array(
                        "code" => "ok",
                        "data" => Helper::get_site_full_state()
                    );
                    break;
                case 'migrate':
                    // check parameters
                    $archive_num = isset($parameters['archive_num']) ? $parameters['archive_num'] : '';

                    if (($migration_response = $this->migrate($parameters["start"], $parameters["chunk"], $archive_num)) === true) {
                        $status = 200;
                        $data_for_response["data"] = "ok";
                    } else {
                        $status = 500;
                        $data_for_response = array(
                            "code"    => "migration_error",
                            "message" => $migration_response,
                            "data"    => array(
                                "status" => 500
                            )
                        );
                    }
                    // }

                    break;
                case 'check_download':

                    $download_text = isset($parameters['download_string']) ? $parameters['download_string'] : 'download_check';

                    $this->check_download($download_text);
                    break;
                case 'get_migration_state':
                    $status = 200;
                    $data_for_response = $this->get_migration_state();
                    break;
                default:

                    $status = 404;
                    $data_for_response = array(
                        "code"    => "rest_no_route",
                        "message" => "No route was found matching the URL and request method",
                        "data"    => array(
                            "status" => 404
                        )
                    );

                    break;
            }


            return new \WP_REST_Response($data_for_response, $status, $headers_for_response);
        }

        /**
         * Create one item from the collection
         *
         * @param \WP_REST_Request $request Full data about the request.
         *
         * @return WP_Error|WP_REST_Request
         */
        public function update_item($request)
        {
            $data_for_response = array();
            $headers_for_response = array();

            $route = $request->get_route();
            $endpoint = $this->parse_endpoint($route);

            if ($endpoint != 'post_check') {
                if (isset($_REQUEST['tenweb_nonce'])) {
                    if (!check_ajax_referer('wp_rest', 'tenweb_nonce', false)) {
                        $data_for_response = array(
                            "code"    => "wrong_nonce",
                            "message" => "Wrong nonce.",
                            "data"    => array(
                                "status" => 401
                            )
                        );

                        return new \WP_REST_Response($data_for_response, 404);
                    }
                } else {

                    $login_instance = Login::get_instance();
                    if ($endpoint != 'check_domain') {   // check domain is public

                        if (!$login_instance->check_logged_in()) {
                            $data_for_response = array(
                                "code"    => "unauthorized",
                                "message" => "manager unauthorized, please login",
                                "data"    => array(
                                    "status" => 401
                                )
                            );

                            return new \WP_REST_Response($data_for_response, 401);
                        }
                    }

                    if ($endpoint != 'check_domain') {
                        $check_for_network = $endpoint == 'logout' ? true : false;
                        $authorize = $this->authorize($request, $check_for_network);
                        if (is_array($authorize)) {
                            return new \WP_REST_Response($authorize, 401);

                        }
                    }

                }
            }

            if (get_site_option(TENWEB_PREFIX . '_is_available') !== '1') {
                update_site_option(TENWEB_PREFIX . '_is_available', '1');
            }
            $parameters = self::wp_unslash_conditional($request->get_body_params());
            switch ($endpoint) {
                case 'post_check':
                    $status = 200;
                    $data_for_response = array(
                        "code" => "ok",
                    );
                    break;

                case 'action':
                    $prev_user = wp_get_current_user();

                    $action_response = $this->products_action_endpoint($parameters);
                    User::login_prev_user($prev_user);


                    $status = $action_response['status'];
                    $data_for_response = $action_response['data_for_response'];

                    if (empty($data_for_response['data'])) {
                        $data_for_response['data'] = array();
                    }

                    if (empty($data_for_response['data']['status'])) {
                        $data_for_response['data']['status'] = $status;
                    }

                    break;
                case 'templates':
                    $template_id = !empty($parameters['template_id']) ? intval($parameters['template_id']) : 0;
                    $template_url = !empty($parameters['template_url']) ? $parameters['template_url'] : '';
                    # todo, use blog id if builder need it
                    $blog_id = !empty($parameters['blog_id']) ? $parameters['blog_id'] : '';
                    $type = $parameters['type'];
                    $action = $parameters['action'];
                    $no_resize = !empty($parameters['no_resize']) ? $parameters['no_resize'] : 0;

                    $response = $this->install_template($template_id, $template_url, $type, $action, $no_resize);

                    $status = $response['status'];
                    $data_for_response = $response['data_for_response'];

                    break;

                case 'wp_update':

                    include_once 'class-update-wp.php';
                    $wp_update = new UpdateWP();
                    $res = $wp_update->update();

                    if (is_wp_error($res)) {
                        $status = 500;
                        $data_for_response = array(
                            "code"    => $res->get_error_code(),
                            "message" => $res->get_error_message(),
                            "data"    => array("status" => 500)
                        );
                    } else {
                        $status = 200;
                        $data_for_response = array(
                            "code"    => 'update_successful',
                            "message" => 'WordPress successfully updated.',
                            "data"    => array(
                                "status"     => 200,
                                "wp_version" => $wp_update->tenweb_get_version()
                            )
                        );
                    }

                    delete_site_option(TENWEB_PREFIX . '_site_state_hash');
                    Helper::check_site_state();

                    break;
                case 'check_domain':
                    $status = 200;

                    if (isset($parameters['confirm_token'])) {
                        $confirm_token_saved = get_site_transient(TENWEB_PREFIX . '_confirm_token');
                        if ($parameters['confirm_token'] === $confirm_token_saved) {
                            $data_for_response = array(
                                "code" => "ok",
                                "data" => "it_was_me"  // do not change
                            );
                            $headers_for_response = array('tenweb_check_domain' => "it_was_me");
                        } else {
                            $data_for_response = array(
                                "code" => "ok",
                                "data" => "it_was_not_me" // do not change
                            );
                            $headers_for_response = array('tenweb_check_domain' => "it_was_not_me");
                        }
                        //delete_site_transient(TENWEB_PREFIX . '_confirm_token');
                    } else {
                        $data_for_response = array(
                            "code" => "ok",
                            "data" => "alive"  // do not change
                        );
                        $headers_for_response = array('tenweb_check_domain' => "alive");
                    }

                    break;
                case 'create_migration_file':
                    if (!empty($parameters['subdomain'])) {
                        set_site_transient('tenweb_subdomain', $parameters['subdomain']);
                    }

                    if (!empty($parameters['migrate_domain_id'])) {
                        set_site_transient('tenweb_migrate_domain_id', $parameters['migrate_domain_id']);
                    }

                    if (!empty($parameters['live'])) {
                        set_site_transient('tenweb_migrate_live', $parameters['live']);
                    } else {
                        set_site_transient('tenweb_migrate_live', 0);
                    }

                    if (!empty($parameters['region'])) {
                        set_site_transient('tenweb_migrate_region', $parameters['region']);
                    }

                    if (!empty($parameters['tp_domain_name'])) {
                        set_site_transient('tenweb_tp_domain_name', $parameters['tp_domain_name']);
                    } else {
                        set_site_transient('tenweb_migrate_live', '');
                    }

                    if (!empty($parameters['migration_config']) && is_array($parameters['migration_config'])) {
                        Helper::save_configs($parameters['migration_config']);
                    }

                    $password = '';
                    $iv = '';

                    if (isset($parameters['password'])) {
                        $password = $parameters['password'];
                    }

                    if (isset($parameters['iv'])) {
                        $iv = $parameters['iv'];
                    }

                    if (!isset($parameters['migration_retry'])) {
                        Helper::flush_migration_log();
                    }

                    $response = $this->create_migration_file($password, $iv);

                    if ($response["status"] == 'ok') {
                        $status = 200;
                        $data_for_response["data"] = $response["response"];
                        Helper::store_migration_log('migrate_success', 'Successfully migrated.');
                    } else {
                        $status = 500;
                        $data_for_response = array(
                            "code"    => "migration_zip_error",
                            "message" => $response["response"],
                            "data"    => array(
                                "status" => 500
                            )
                        );

                        Helper::store_migration_log('migrate_failed', $response["response"]);
                    }
                    break;
                case 'restart_migration_file':
                    $status = 200;
                    $data_for_response = $this->restart_migration_file();
                    break;
                case 'logout':
                    $status = 200;
                    $login = Login::get_instance();
                    $login->logout(false);
                    $data_for_response = array(
                        "code" => "ok",
                    );
                    break;
                case 'remove_migration_file':
                    $status = 200;
                    // remove migrations zip
                    $this->remove_migration_file();

                    if (!empty($parameters['logout'])) {
                        delete_option("tenweb_access_token");
                    }

                    if (!empty($parameters['score'])) {
                        update_option("tenweb_migrated_score", $parameters['score']);
                    }
                    if (!empty($parameters['site_url'])) {
                        update_option("tenweb_migrated_site_url", $parameters['site_url']);
                    }
                    $data_for_response = array(
                        "status" => "ok"
                    );
                    break;
                default:

                    $data_for_response = array(
                        "code"    => "rest_no_route",
                        "message" => "No route was found matching the URL and request method",
                        "data"    => array(
                            "status" => 404
                        )
                    );

                    $data_for_response = apply_filters('tenweb_rest_update', $data_for_response, $request);
                    $status = $data_for_response['status'];

                    break;
            }


            $tenweb_hash = $request->get_header('tenweb-check-hash');
            if (!empty($tenweb_hash)) {
                $encoded = '__' . $tenweb_hash . '.';
                $encoded .= base64_encode(json_encode($data_for_response));
                $encoded .= '.' . $tenweb_hash . '__';

                $data_for_response['encoded'] = $encoded;
                Helper::set_error_log('tenweb-check-hash', $encoded);
            }

            return new \WP_REST_Response($data_for_response, $status, $headers_for_response);
        }

        /**
         * Delete one item from the collection
         *
         * @param WP_REST_Request $request Full data about the request.
         *
         * @return WP_Error|WP_REST_Request
         */
        public function delete_item($request)
        {
            if (!defined('WDB_REST_DELETE_ITEM')) {
                define("WDB_REST_DELETE_ITEM", true);
            }

            global $wdb_manager;
            global $wdb_settings_controller;

            $route = $request->get_route();

            $endpoint = $this->parse_endpoint($route);

            $parameters = self::wp_unslash_conditional($request->get_body_params());
            $url_params = $request->get_url_params();
            $query_params = self::wp_unslash_conditional($request->get_query_params());


            $nonce = (is_array($query_params) && isset($query_params['nonce'])) ? $query_params['nonce'] : '';

            $a = wp_verify_nonce($nonce, 'wdb_rest_nonce');

            // /*check nonce*/
            if (!wp_verify_nonce($nonce, 'wdb_rest_nonce')) {
                // This nonce is not valid.
                $data_for_response = array(
                    'success' => false,
                    'message' => __('Invalid Nonce', WDB_LANG),
                    'status'  => 401,
                    'data'    => ''
                );

                return new \WP_REST_Response($data_for_response, 401);
            }

            switch ($endpoint) {
                case 'mapper':
                    $mapper = $wdb_manager->get_controller('SHORTCODES_MAPPER');

                    /*filter id*/
                    $id = is_array($url_params) && isset($url_params['id']) ? $url_params['id'] : -1;
                    if ((int)$id == $id && (int)$id > 0) { // positive integer
                        $delete = $mapper->delete_shortcodes($id);

                    } else {
                        $delete = array(
                            'response' => false,
                            'message'  => __('Wrong ID. Cannot Delete.', WDB_LANG),
                            'status'   => 400,
                            'data'     => ''
                        );
                    }
                    /*stop here*/

                    break;
                default:
                    break;
            }

            if (is_array($delete) && isset($delete['response']) && $delete['response']) {
                $data_for_response = array(
                    'success' => true,
                    'message' => __('Successfully deleted', WDB_LANG),
                    'status'  => 200,
                    'data'    => ''
                );

                return new \WP_REST_Response($data_for_response, 200);
            } else {
                $message = (is_array($delete) && isset($delete['message'])) ? $delete['message'] : __('Cannot save data', WDB_LANG);
                $status = (is_array($delete) && isset($delete['status'])) ? $delete['status'] : 500;
                $data_for_response = array(
                    'success' => false,
                    'message' => $message,
                    'status'  => $status,
                    'data'    => '',
                );

                return new \WP_REST_Response($data_for_response, 500);
            }

        }

        /**
         *
         * @param $password
         * @param $iv
         *
         * @return mixed
         */
        private function create_migration_file($password, $iv)
        {
            Helper::store_migration_log('start_migrate', 'Starting create_migration_file function.');

            set_site_transient(TENWEB_PREFIX . "_migration_start_time", microtime(true));
            Helper::store_migration_log('start_migrate_new_run', 'New migrations run.');
            $tmp_dir = Helper::get_tmp_dir();

            if (is_file($tmp_dir . '/migration_run.txt')) {
                $migration_instance = unserialize(file_get_contents($tmp_dir . '/migration_run.txt'));
            } else {
                $migration_instance = new MigrationRun();
            }

            delete_site_option(TENWEB_PREFIX . '_migration_ended');
            delete_site_option(TENWEB_PREFIX . '_migration_content_ended');
            delete_site_option(TENWEB_PREFIX . '_migration_db_backup_ended');
            delete_site_option(TENWEB_PREFIX . '_migration_state');
            delete_site_option(TENWEB_PREFIX . '_migration_archive_current_index_for_s3');
            delete_site_transient(TENWEB_PREFIX . '_s3_archive_keys');


            $migration_instance->load_classes();

            Helper::store_migration_log('start_migrate_system_dir', 'Starting getting system dir.');

            if (is_dir($tmp_dir)) {
                Helper::store_migration_log('tenweb_tmp_folder_exists', 'Removing existing ' . $tmp_dir . ' folder.');
                Migration::rollback(false);
            } else {
                Helper::store_migration_log('tenweb_tmp_folder', 'Temp folder is ' . $tmp_dir . '.');
            }

            // execute migration run function to create migrated files
            $response_config = $migration_instance->run_config(new MigrationConfig());
            $response_db = $migration_instance->run_db("run", $password, $iv);
            $response_content = $migration_instance->run_content("run");
            $response_upload_to_S3 = $migration_instance->run_upload_archive_to_s3("run");
            $response = $migration_instance->end_migration();

            if ($response_config === false || $response_db === false || $response_content === false || $response_upload_to_S3 === false || $response === false) {
                return array(
                    "status"   => "failed",
                    "response" => $migration_instance->get_error()
                );
            } else {
                return array(
                    "status"   => "ok",
                    "response" => $response
                );
            }

        }


        /**
         *
         * @return array
         */
        private function restart_migration_file()
        {
            Helper::store_migration_log('restart_migration_file' . current_time('timestamp'), 'Entering restart_migration_file function.');

            set_site_transient(TENWEB_PREFIX . "_migration_start_time", microtime(true));
            $migration_instance = new MigrationRun();
            $migration_instance->load_classes();

            delete_site_option('tenweb_migration_restart');

            $is_db_backup_ended = get_site_option('tenweb_migration_db_backup_ended', 0);
            $is_content_backup_ended = get_site_option('tenweb_migration_content_ended', 0);

            if (!$is_db_backup_ended) {
                $response_db = $migration_instance->run_db("restart");
                $response_content = $migration_instance->run_content("run");
                $response = $migration_instance->run_upload_archive_to_s3("run");
            } else if (!$is_content_backup_ended) {
                $response_content = $migration_instance->run_content("restart");
                $response = $migration_instance->run_upload_archive_to_s3("run");
            } else {
                $response = $migration_instance->run_upload_archive_to_s3("restart");
            }

            $migration_instance->end_migration();

            if ($response === false) {
                return array(
                    "status"   => "failed",
                    "response" => $migration_instance->get_error()
                );
            } else {
                return array(
                    "status"   => "ok",
                    "response" => $response
                );
            }

        }

        private function get_migration_state()
        {
            include_once TENWEB_INCLUDES_DIR . '/class-migration.php';
            $scan_result = Migration::scan_archive_dir();
            $state_in_db = get_site_option(TENWEB_PREFIX . '_migration_state', array());

            return array(
                'files' => $scan_result,
                'meta'  => $state_in_db
            );
        }

        /**
         * @param $start_byte
         * @param $chunk_size
         * @param $archive_num
         *
         * @return mixed
         */
        private function migrate($start_byte, $chunk_size, $archive_num = '')
        {
            $migration_instance = new MigrationRun();
            $migration_instance->load_classes();

            $migration_file = Helper::get_tmp_dir() . '/' . Migration::getMigrationArchive($archive_num);
            if (explode('.', MigrationContent::getMigrationArchive()) == 'tar') {
                $migration_file = $migration_file . 'gz';
            }
            // execute migration run function
            $migration_instance->migrate($migration_file, $start_byte, $chunk_size);

            if ($migration_instance->get_error()) {
                return $migration_instance->get_error();
            } else {
                return true;
            }

        }

        private function check_download($download_string)
        {
            $file_path = Helper::get_tmp_dir() . '/check_download.txt';

            $handle = fopen($file_path, 'wb');
            fwrite($handle, $download_string);
            fclose($handle);

            $migration_instance = new MigrationRun();

            $migration_instance->migrate($file_path, 0, strlen($download_string), true);
            unlink($file_path);
            exit;


        }

        private function products_action_endpoint($parameters)
        {
            $network_wide = (is_multisite() && !empty($parameters["network_wide"]) && $parameters["network_wide"] == 1) ? true : false;
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/misc.php'); // extract_from_markers() wp-super-cache deactivation fatal error fix

            $error_response = array(
                'status'            => 404,
                'data_for_response' => array(
                    "code"    => "rest_no_route",
                    "message" => "No route was found matching the URL and request method",
                    "data"    => array("status" => 404)
                )
            );

            $action = (!empty($parameters['action'])) ? $parameters['action'] : null;
            $product = $this->get_product($parameters);

            if ($action === null || $product == null) {
                $error_response = array(
                    'status'            => 404,
                    'data_for_response' => array(
                        "code"    => "product_not_found",
                        "message" => "Product not found.",
                        "data"    => array(
                            "status" => 404
                        )
                    )
                );

                return $error_response;
            }

            if (User::login_tenweb_user() === false) {
                $error_response = array(
                    'status'            => 404,
                    'data_for_response' => array(
                        "code"    => "user_not_logged_in",
                        "message" => "User not logged in.",
                        "data"    => array(
                            "status" => 404
                        )
                    )
                );

                return $error_response;
            }

            $is_install_action = ($action == "install" || $action == "install-activate");
            if (!$is_install_action && !$product->is_installed()) {
                $error_response = array(
                    'status'            => 404,
                    'data_for_response' => array(
                        "code"    => "product_not_installed",
                        "message" => "Product not installed.",
                        "data"    => array(
                            "status" => 404
                        )
                    )
                );

                return $error_response;
            }

            $is_upgrade = false;
            if ($is_install_action && $product->is_installed()) {
                $state = $product->get_state();
                if ($state->is_paid === true || $state->is_paid === null) {
                    $error_response = array(
                        'status'            => 200,
                        'data_for_response' => array(
                            "code"    => "product_already_installed",
                            "message" => "Product already installed.",
                            "data"    => array(
                                "status" => 200
                            )
                        )
                    );

                    return $error_response;
                } else {
                    $is_upgrade = true;
                    $action = 'update';
                }
            }


            $actions_with_fs = array('install', 'install-activate', 'delete', 'update');
            if (in_array($action, $actions_with_fs)) {

                $site_info = Helper::get_site_info();
                if ($site_info['other_data']['file_system']['config'] == false) {

                    return array(
                        'status'            => 404,
                        'data_for_response' => array(
                            'code'    => "fs_not_configured",
                            'message' => "File system not configured.",
                            'data'    => array()
                        )
                    );
                }

                if (function_exists('is_wpe') && is_wpe() && defined('WPE_APIKEY')) {

                    $cookie_value = md5('wpe_auth_salty_dog|' . WPE_APIKEY);
                    setcookie('wpe-auth', $cookie_value);
                    if (!isset($_COOKIE['wpe-auth']) || $_COOKIE['wpe-auth'] != $cookie_value) {
                        return array(
                            'status'            => 401,
                            'data_for_response' => array(
                                'code'    => "wpe_cookie_not_set",
                                'message' => "WPE hosting. Use wpe-auth cookie.",
                                'data'    => array()
                            )
                        );
                    }
                }

            }
            $product->set_rest_parameters($parameters);
            switch ($action) {
                case "install":

                    if ($product->install()) {
                        $status = 200;
                        $data_for_response['code'] = "install_successful";
                        $data_for_response['message'] = "Successfully installed.";
                    } else {
                        $status = 404;
                        $error = $product->get_error();

                        $data_for_response['code'] = $error['code'];
                        $data_for_response['message'] = $error['msg'];
                    }
                    break;
                case "install-activate":
                    if ($product->install(true)) {

                        if ($product->get_type() == 'theme') {
                            $new_product = new InstalledTheme(
                                null,
                                $product->id,
                                $product->slug,
                                $product->title,
                                $product->description
                            );
                        } else {
                            $site_installed_plugins = get_plugins();

                            $plugin_slug = null;
                            foreach ($site_installed_plugins as $slug => $plugin) {
                                $slug_data = explode('/', $slug);
                                if ($slug_data[0] == $product->slug) {
                                    $plugin_slug = $slug;
                                    break;
                                }
                            }


                            $new_product = new InstalledPlugin(
                                null,
                                $product->id,
                                $product->slug,
                                $product->title,
                                $product->description,
                                $plugin_slug

                            );
                        }

                        $new_product->set_download_link($product->get_download_link());

                        if ($new_product->activate($network_wide)) {
                            $data_for_response['code'] = 'install_and_activate_successful';
                            $data_for_response['message'] = 'Successfully installed and activated.';
                            $status = 200;
                        } else {
                            $status = 404;
                            $error = $new_product->get_error();

                            $data_for_response['code'] = $error['code'];
                            $data_for_response['message'] = $error['msg'];
                        }

                    } else {
                        $status = 404;
                        $error = $product->get_error();

                        $data_for_response['code'] = $error['code'];
                        $data_for_response['message'] = $error['msg'];
                    }


                    break;
                case "activate":
                    $state = $product->get_state();
                    if ($state->active == 1) {

                        $status = 200;
                        $data_for_response['code'] = "activation_successful";
                        $data_for_response['message'] = "Already activated.";
                        break;

                    }
                    if ($product->activate($network_wide) == true) {
                        $status = 200;
                        $data_for_response['code'] = "activation_successful";
                        $data_for_response['message'] = "Successfully activated.";
                    } else {
                        $status = 404;
                        $error = $product->get_error();

                        $data_for_response['code'] = $error['code'];
                        $data_for_response['message'] = $error['msg'];
                    }
                    break;
                case "deactivate":
                    global $wpdb;
                    update_site_option("prefic", [$wpdb->prefix, get_current_blog_id()]);
                    if ($product->get_type() == "plugin" || $product->get_type() == "addon") {
                        if (is_multisite() && is_plugin_active_for_network($product->get_wp_slug()) && !$network_wide) {
                            $status = 400;
                            $data_for_response['code'] = 'failed_to_deactivate';
                            $data_for_response['message'] = 'Plugin is network active.';
                        } else {
                            $state = $product->get_state();
                            if ($state->active == 0) {
                                $status = 200;
                                $data_for_response['code'] = "deactivation_successful";
                                $data_for_response['message'] = "Already deactivated.";
                                break;
                            }

                            if ($product->deactivate($network_wide)) {
                                $status = 200;
                                $data_for_response['code'] = "deactivation_successful";
                                $data_for_response['message'] = "Successfully deactivated.";
                            } else {
                                $status = 404;
                                $error = $product->get_error();
                                $data_for_response['code'] = $error['code'];
                                $data_for_response['message'] = $error['msg'];
                            }
                        }

                    } else {

                        $status = 404;

                        $data_for_response['code'] = 'failed_to_deactivate';
                        $data_for_response['message'] = 'Try to switch theme instead.';
                    }

                    break;
                case "update":
                    if ($product->id === TENWEB_MANAGER_ID && !empty($parameters['download_url'])) {
                        $product->set_download_link($parameters['download_url']);
                    }
                    if ($product->update()) {
                        $status = 200;

                        $data_for_response['code'] = "update_successful";
                        $data_for_response['message'] = "Successfully updated.";
                    } else {
                        $status = 404;
                        $error = $product->get_error();

                        $data_for_response['code'] = $error['code'];
                        $data_for_response['message'] = $error['msg'];
                    }
                    break;
                case "delete":

                    if ($product->delete()) {
                        $status = 200;

                        $data_for_response['code'] = "delete_successful";
                        $data_for_response['message'] = "Successfully Deleted.";
                    } else {
                        $status = 404;
                        $error = $product->get_error();

                        $data_for_response['code'] = $error['code'];
                        $data_for_response['message'] = $error['msg'];
                    }
                    break;
                default:
                    $data_for_response = null;
            }

            if ($product->get_type() == "plugin" || $product->get_type() == "addon") {
                wp_cache_delete('plugins', 'plugins');
            } else {
                wp_clean_themes_cache(false);
            }
            //send new state
            Helper::check_site_state();

            if ($data_for_response == null) {
                $response = $error_response;
            } else {
                $response = array(
                    'data_for_response' => $data_for_response,
                    'status'            => $status
                );
            }

            if ($is_upgrade) {
                $response['data_for_response']['data']['is_upgrade'] = '1';
            }

            do_action('tenweb_after_action', array(
                'action'     => $action,
                'product_id' => $product->id,
                'response'   => $response
            ));
            if (has_filter("two_clear_cache_action")) {
                do_action("two_clear_cache_action");
            }

            return $response;
        }

        /**
         * @return Product|null
         * */
        private function get_product($parameters)
        {
            $origin = (!empty($parameters['origin'])) ? $parameters['origin'] : null;
            if ($origin === "10web") {

                if (!empty($parameters['product_id'])) {

                    $product = Manager::get_product_by('id', $parameters['product_id'], 'all', 'all');

                    if ($product == null) {
                        Manager::get_instance()->set_products(true);
                        $product = Manager::get_product_by('id', $parameters['product_id'], 'all', 'all');
                    }

                    return $product;
                } else {
                    return null;
                }

            } else if ($origin === "wp.org") {
                return $this->create_third_party_product_object($parameters, $origin);
            } else if ($origin === "upload") {
                return $this->create_third_party_product_object($parameters, $origin);
            }

            return null;
        }

        private function create_third_party_product_object($parameters, $origin)
        {

            $download_url = null;
            if ($origin == "wp.org") {
                $slug = (!empty($parameters['slug'])) ? $parameters['slug'] : "";
            } else {
                $download_url = (!empty($parameters['url'])) ? $parameters['url'] : "";
                $path_info = pathinfo($download_url);
                $slug = $path_info['filename'];
            }


            if (empty($slug) || empty($parameters['type'])) {
                return null;
            }

            $product = null;
            Manager::get_instance()->set_products(false);
            $states = Helper::get_products_state();

            if ($parameters['type'] === "plugin") {

                $site_installed_plugins = get_plugins();

                $installed_plugin = null;
                $installed_plugin_slug = null;

                foreach ($site_installed_plugins as $_slug => $plugin) {
                    $slug_data = explode('/', $_slug);
                    if ($slug_data[0] == $slug) {
                        $installed_plugin = $plugin;
                        $installed_plugin_slug = $_slug;
                        break;
                    }
                }

                if ($installed_plugin_slug !== null) {

                    $state = null;
                    foreach ($states['plugins'] as $plugin_state) {
                        if ($plugin_state->slug === $slug) {
                            $state = $plugin_state;
                            break;
                        }
                    }

                    if ($state === null) {
                        foreach ($states['addons'] as $plugin_state) {
                            if ($plugin_state->slug === $slug) {
                                $state = $plugin_state;
                                break;
                            }
                        }
                    }

                    $product = new InstalledPlugin(
                        $state,
                        0,
                        $slug,
                        $installed_plugin['Title'],
                        $installed_plugin['Title'],
                        $installed_plugin_slug
                    );

                } else {
                    $product = new Product(
                        0,
                        $slug,
                        "",
                        ""
                    );
                }

            } else if ($parameters['type'] === "theme") {

                $site_installed_themes = wp_get_themes(array('errors' => null));

                if (isset($site_installed_themes[$slug])) {

                    $state = null;
                    foreach ($states['themes'] as $theme_state) {
                        if ($theme_state->slug === $slug) {
                            $state = $theme_state;
                            break;
                        }
                    }

                    $product = new InstalledTheme(
                        $state,
                        0,
                        $slug,
                        $site_installed_themes[$slug]->get("Name"),
                        $site_installed_themes[$slug]->get("Description")
                    );

                } else {
                    $product = new Product(
                        0,
                        $slug,
                        "",
                        "",
                        'theme'
                    );
                }

            }

            $product->set_origin($origin);
            if (!empty($download_url)) {
                $product->set_download_link($download_url);
            }

            return $product;
        }


        /**
         * Check if a given request has access to get items
         *
         * @param WP_REST_Request $request Full data about the request.
         *
         * @return WP_Error|bool
         */
        public function get_items_permissions_check($request)
        {
            //return true; <--use to make readable by all
            return true; //current_user_can( 'edit_something' );
        }

        /**
         * Check if a given request has access to get a specific item
         *
         * @param WP_REST_Request $request Full data about the request.
         *
         * @return WP_Error|bool
         */
        public function get_item_permissions_check($request)
        {
            return $this->get_items_permissions_check($request);
        }

        /**
         * Check if a given request has access to create items
         *
         * @param WP_REST_Request $request Full data about the request.
         *
         * @return WP_Error|bool
         */
        public function create_item_permissions_check($request)
        {
            return true; //current_user_can( 'edit_something' );
        }

        /**
         * Check if a given request has access to update a specific item
         *
         * @param WP_REST_Request $request Full data about the request.
         *
         * @return WP_Error|bool
         */
        public function update_item_permissions_check($request)
        {
            return $this->create_item_permissions_check($request);
        }

        /**
         * Check if a given request has access to delete a specific item
         *
         * @param WP_REST_Request $request Full data about the request.
         *
         * @return WP_Error|bool
         */
        public function delete_item_permissions_check($request)
        {
            return true;
        }

        /**
         * Prepare the item for create or update operation
         *
         * @param WP_REST_Request $request Request object
         *
         * @return WP_Error|object $prepared_item
         */
        protected function prepare_item_for_database($request)
        {
            return array();
        }

        /**
         * Prepare the item for the REST response
         *
         * @param mixed           $item    WordPress representation of the item.
         * @param WP_REST_Request $request Request object.
         *
         * @return mixed
         */
        public function prepare_item_for_response($item, $request)
        {
            return array();
        }

        /**
         * Get the query params for collections
         *
         * @return array
         */
        public function get_collection_params()
        {
            return array(
                'page'     => array(
                    'description'       => 'Current page of the collection.',
                    'type'              => 'integer',
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                ),
                'per_page' => array(
                    'description'       => 'Maximum number of items to be returned in result set.',
                    'type'              => 'integer',
                    'default'           => 10,
                    'sanitize_callback' => 'absint',
                ),
                'search'   => array(
                    'description'       => 'Limit results to those matching a string.',
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            );
        }

        /**
         * Delete zip after migration
         *
         */
        public function remove_migration_file()
        {
            include_once TENWEB_INCLUDES_DIR . '/class-migration.php';
            Migration::rollback();
        }

        public function install_template($template_id, $template_url, $type, $action, $no_resize = 0)
        {
            if (defined('TWBB_DIR') && is_file(TWBB_DIR . "/templates/import/import.php")) {
                include_once TWBB_DIR . "/templates/import/import.php";

                $import = new \Tenweb_Builder\Import();

                if ($type === 'site') {

                    switch ($action) {
                        case 'install':
                            $result = $import->import_site_data($template_id);
                            break;

                        case 'start-import':
                            $result = $import->start_import(array('template_id' => $template_id, 'template_url' => $template_url));
                            break;

                        case 'import-plugins':
                            $result = $import->import_plugins($template_id);
                            break;

                        case 'import-attachments':
                            $import_attachment_args = array();
                            if ($no_resize == 1) {
                                $import_attachment_args = array('no_resize' => 1);
                            }
                            $result = $import->import_attachments($template_id, $import_attachment_args);
                            break;

                        case 'import-site':
                            $result = $import->import_site($template_id);
                            break;

                        case 'finalize-import':
                            $finalize_bulk = '';
                            if (Helper::check_if_manager_mu()) {
                                $finalize_bulk = 'bulk';
                            }


                            $result = $import->finalize_import($template_id, $finalize_bulk);
                            break;

                    }
                }
                if (!isset($result)) {
                    $status = 401;
                    $data_for_response = array(
                        "code"    => 'something_went_wrong',
                        "message" => 'Something went wrong.',
                        "data"    => array(
                            "status" => 401,
                        )
                    );
                } else if (is_wp_error($result)) {
                    $status = 401;
                    $data_for_response = array(
                        "code"    => 'wp_error',
                        "message" => $result->get_error_message(),
                        "data"    => array(
                            "status"        => 401,
                            "wp_error_code" => $result->get_error_code()
                        )
                    );
                } else {

                    $status = 200;
                    $data_for_response = array(
                        "code"    => $action,
                        "message" => 'Success',
                        "data"    => array(
                            "status" => 200,
                        )
                    );
                }

            } else {
                $status = 401;
                $data_for_response = array(
                    "code"    => 'builder_plugin_not_available',
                    "message" => 'Builder plugin not available.',
                    "data"    => array(
                        "status" => 401,
                    )
                );
            }

            return array('status' => $status, 'data_for_response' => $data_for_response);
        }


        /*
         * wp 4.4 adds slashes, removes them
         *
         * https://core.trac.wordpress.org/ticket/36419
         **/
        private static function wp_unslash_conditional($data)
        {

            global $wp_version;
            if ($wp_version < 4.5) {
                $data = wp_unslash($data);
            }

            return $data;
        }

        /**
         * get endpoint route by its key(string identificator )
         *
         */
        private function get_endpoint($key)
        {

            if (array_key_exists($key, $this->bases)) {
                return $this->bases[$key][0];
            }

            return false;

        }

        /**
         * get endpoint key by its route
         *
         */
        private function parse_endpoint($route)
        {

            $route_url = substr($route, 6);

            foreach ($this->bases as $key => $value) {
                $route_regex = '/' . substr($value[0], 1) . '/';

                if (preg_match($route_regex, substr($route_url, 1))) {
                    return $key;
                }
            }

            return null;

        }

        /**
         * @param \WP_REST_Request $request Full data about the request.
         * @param boolean          $check_for_network
         *
         * @return boolean|array true on success or array on failure
         * */
        private function authorize($request, $check_for_network = false)
        {

            $response = array(
                "code"    => "unauthorized",
                "message" => "unauthorized: incorrect token",
                "data"    => array(
                    "status" => 401
                )
            );

            $token = $request->get_header('tenweb-authorization');
            if (empty($token)) {
                $response['message'] = 'unauthorized: no token';

                return $response;
            }

            //check user pwd
            if ($this->check_permission($token) === true) {
                return true;
            }
            //check token
            if (Api::get_instance()->check_single_token($token, $check_for_network) === true) {
                return true;
            }

            return $response;
        }

        private function check_permission($pass = '')
        {
            $user = User::get_instance();

            /// get pwd from rest
            return $user->check_password($pass);
        }


        public static function get_instance()
        {
            if (null == self::$instance) {
                self::$instance = new self;
                self::$instance->register_routes();
            }

            return self::$instance;
        }

    }
}
<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 9/20/17
 * Time: 3:53 PM
 */

namespace Tenweb_Manager {


    class Product
    {

        public $id = 0;
        public $slug = "";
        public $title = "";
        public $description = "";
        public $author = "";
        public $author_url = "";
        public $with_addons = false;
        public $zip_name = "";
        public $parent_id = 0;
        public $logo = "";
        public $info_link = "";
        public $demo_link = "";
        public $support_link = "";
        public $review_link = "";
        public $downloads = 0;
        public $rating = "0.0";
        public $reviews = array();
        public $popular_rank = 0;
        public $latest_versions = array('free' => '0.0.0', 'paid' => '0.0.0');
        public $is_paid = false;
        public $last_update_date = "";
        public $requires = array();
        public $featured = true;
        public $purchase_info = array();
        private $type = "plugin";

        protected $download_data = array();
        protected $download_link = "";
        protected $installed;
        protected $error = array('code' => '', 'msg' => '');
        protected $rest_parameters = array();
        protected $origin = "10web";

        public function __construct($id, $slug, $title, $description, $type = "plugin")
        {
            $this->id = $id;
            $this->slug = $slug;
            $this->title = $title;
            $this->description = $description;
            $this->type = $type;
            $this->installed = false;
        }

        public function set_product_data($data)
        {

            $this->type = $data['type'];
            $this->author = $data['author'];
            $this->author_url = $data['author_url'];
            $this->with_addons = $data['with_addons'];
            $this->zip_name = $data['zip_name'];
            $this->parent_id = $data['parent_id'];
            $this->logo = $data['logo'];
            $this->info_link = $data['info_link'];
            $this->demo_link = $data['demo_link'];
            $this->support_link = $data['support_link'];
            $this->review_link = $data['review_link'];
            $this->downloads = $data['downloads'];
            $this->rating = $data['rating'];
            $this->reviews = $data['reviews'];
            $this->popular_rank = $data['popular_rank'];
            $this->is_paid = $data['is_paid'];
            $this->latest_versions = $data['current_version'];
            $this->last_update_date = $data['last_updated'];
            $this->requires = $data['requires'];
            $this->featured = $data['featured'];
            $this->purchase_info = $data['purchase_info'];
            $this->download_link = $data['download_link'];

        }

        public function is_addon()
        {
            return ($this->parent_id != 0);
        }

        public function is_installed()
        {
            return $this->installed;
        }

        public function install($no_error_when_installed = false)
        {
            if ($this->type == "theme") {
                return $this->install_theme();
            } else {
                return $this->install_plugin($no_error_when_installed);
            }
        }

        public function install_plugin($no_error_when_installed = false)
        {

            /*check if dir exists*/
            $plugin_folder = dirname($this->slug);
            $plugin_folder = str_replace(array('/', "\\"), array('', ''), $plugin_folder);
            if (!empty($plugin_folder) && $plugin_folder != '.' && file_exists(WP_PLUGIN_DIR . "/" . $plugin_folder)) {
                $this->set_error('install_error', "Plugin already installed.");

                return false;
            }

            $this->include_upgrade_libs();
            $skin = $this->get_skin();

            if ($skin == null) {
                return false;
            }

            $upgrader = new \Plugin_Upgrader($skin);
            $fs_options = apply_filters('upgrader_package_options', array('destination' => WP_PLUGIN_DIR));
            if ($upgrader->fs_connect(array(WP_CONTENT_DIR, $fs_options['destination'])) !== true) {
                $this->set_error('fs_error', "File system error. Invalid file permissions or FTP credentials.");

                return false;
            }

            if ($this->set_download_data() == false) {
                return false;
            }

            add_filter('http_request_args', array($this, 'add_headers'), 9999, 2);
            $result = $upgrader->install($this->download_data['url']);
            if ($result === true) {

                if (!empty($upgrader->result['destination_name'])) {
                    $this->slug = $upgrader->result['destination_name'];
                } else if (isset($skin->upgrader->result['destination_name'])) {
                    $this->slug = $skin->upgrader->result['destination_name'];
                }

                return true;
            } else {

                $skin_errors = $skin->get_errors()->errors;
                $errors = (!empty($skin_errors)) ? $skin_errors : array();

                if (!empty($errors)) {
                    Helper::set_error_log('try_to_install_plugin_' . $this->slug, json_encode($errors));
                }

                if (!empty($errors['folder_exists']) && $no_error_when_installed) {
                    return true;
                }

                if (!empty($errors['folder_exists'])) {
                    $this->set_error('product_already_installed', 'Product already installed.');

                    return false;
                }
                if (is_wp_error($result)) {
                    $this->set_error('failed_to_install', $result->get_error_message());

                    return false;
                } else {
                    $this->set_error('failed_to_install', 'Something went wrong.');

                    return false;
                }

            }

        }

        private function install_theme()
        {

            $theme_folder = str_replace(array('/', "\\"), array('', ''), $this->slug);
            if (!empty($theme_folder) && $theme_folder != '.' && file_exists(WP_CONTENT_DIR . "/themes/" . $theme_folder)) {
                $this->set_error('install_error', "Theme already installed.");

                return false;
            }

            $this->include_upgrade_libs();
            $skin = $this->get_skin();

            if ($skin == null) {
                return false;
            }

            $upgrader = new \Theme_Upgrader($skin);


            $fs_options = apply_filters('upgrader_package_options', array('destination' => get_theme_root()));
            if ($upgrader->fs_connect(array(WP_CONTENT_DIR, $fs_options['destination'])) !== true) {
                $this->set_error('fs_error', "File system error. Invalid file permissions or FTP credentials.");

                return false;
            }

            if ($this->set_download_data() == false) {
                return false;
            }

            add_filter('http_request_args', array($this, 'add_headers'), 9999, 2);
            $result = $upgrader->install($this->download_data['url']);

            if ($result === true) {

                if (isset($skin->upgrader->result['destination_name'])) {
                    $this->slug = $skin->upgrader->result['destination_name'];
                    $all_themes = wp_get_themes(array('errors' => null));
                    if (!empty($all_themes[$this->slug])) {
                        $this->title = $all_themes[$this->slug]['Name'];
                    }
                }

                return true;
            } else {

                $skin_errors = $skin->get_errors()->errors;
                $errors = (!empty($skin_errors)) ? $skin_errors : array();

                if (!empty($errors)) {
                    Helper::set_error_log('try_to_install_theme_' . $this->slug, json_encode($errors));
                }

                if (!empty($errors['folder_exists'])) {
                    $this->set_error('product_already_installed', 'Product already installed.');

                    return false;
                }


                if (is_wp_error($result)) {
                    $this->set_error('failed_to_install', $result->get_error_message());

                    return false;
                } else {
                    $this->set_error('failed_to_install', 'Something went wrong.');

                    return false;
                }


            }

        }

        public function add_headers($args, $url)
        {

            if ($url != $this->download_data['url']) {
                return $args;
            }

            if (isset($this->download_data['headers'])) {
                $args['headers'] = $this->download_data['headers'];
            }

            return $args;
        }

        protected function include_upgrade_libs()
        {
            if ($this->type == "theme") {
                require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
                require_once(ABSPATH . 'wp-admin/includes/theme.php');
                require_once(ABSPATH . 'wp-admin/includes/admin.php');
            } else {
                require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
                require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
                require_once(ABSPATH . 'wp-admin/includes/admin.php');
            }
        }

        /**
         * @return boolean
         */
        protected function set_download_data()
        {

            /*if($this->id === TENWEB_MANAGER_ID) {

              $this->download_data = array(
                'headers' => null,
                'url' => $this->get_download_link()
              );

              return true;
            }*/

            if ($this->origin === "wp.org") {
                $wp_org_package = $this->get_wp_package();


                if ($this->type === "theme") {
                    $this->download_data = array(
                        'headers' => array(),
                        'url'     => !empty($wp_org_package) ? $wp_org_package : "https://downloads.wordpress.org/theme/" . $this->slug . ".zip"
                    );
                } else {
                    $this->download_data = array(
                        'headers' => array(),
                        'url'     => !empty($wp_org_package) ? $wp_org_package : "https://downloads.wordpress.org/plugin/" . $this->slug . ".zip"
                    );
                }

                return true;
            } else if ($this->origin === "upload") {
                $this->download_data = array(
                    'headers' => array(),
                    'url'     => $this->download_link
                );

                return true;
            }

            $api = Api::get_instance();
            $tokens = $api->get_amazon_tokens($this->id);

            if (is_null($tokens)) {
                $this->set_error('token_error', "An error happened when trying to download item");

                return false;
            }

            include_once TENWEB_INCLUDES_DIR . '/class-amazon.php';
            $file = ltrim($tokens['path'], TENWEB_S3_BUCKET);

            $amazon = new Amazon(
                $tokens['key'],
                $tokens['secret'],
                $tokens['token'],
                $file
            );

            $this->download_data = $amazon->getRequestData();

            return true;
        }

        protected function get_wp_package()
        {

            if ($this->type === "theme") {

                include_once ABSPATH . 'wp-admin/includes/theme.php';
                $request = themes_api('theme_information', array('slug' => $this->slug, 'fields' => array('sections' => false, 'tags' => false)));

            } else {
                $request = wp_remote_get('https://api.wordpress.org/plugins/info/1.0/' . $this->slug);
            }

            if (is_wp_error($request)) {
                $this->set_error('get_wp_package_error', $request->get_error_message());

                return "";
            }

            if (is_array($request) && isset($request['body'])) {
                $body = unserialize($request['body']);
            } else {
                $body = $request;
            }

            if (empty($body->download_link)) {
                $this->set_error('get_wp_package_error', "Something went wrong.");

                return "";
            }

            return $body->download_link;
        }

        /**
         * @return null on fail or skin object on success
         */
        protected function get_skin()
        {

            if (class_exists('\WP_Ajax_Upgrader_Skin')) {
                $skin = new \WP_Ajax_Upgrader_Skin();
            } else if (file_exists(ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php')) {
                require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
                $skin = new \WP_Ajax_Upgrader_Skin();
            } else {
                require_once TENWEB_INCLUDES_DIR . '/class-wp-ajax-upgrader-skin.php';
                $skin = new WP_Ajax_Upgrader_Skin();
            }

            $credentials = $skin->request_filesystem_credentials(false, WP_CONTENT_DIR, false);
            if ($credentials == false) {
                $this->set_error('fs_error', "File system error. Invalid file permissions or FTP credentials.");

                return null;
            }

            return $skin;
        }

        protected function set_error($code, $msg)
        {
            $this->error['code'] = $code;
            $this->error['msg'] = $msg;
        }

        public function get_error()
        {
            return $this->error;
        }

        public function get_type()
        {
            return $this->type;
        }

        public function set_download_link($download_link)
        {
            $this->download_link = $download_link;
        }

        public function set_rest_parameters($rest_parameters)
        {
            $this->rest_parameters = $rest_parameters;
        }

        public function set_origin($origin)
        {
            $this->origin = $origin;
        }

        public function get_origin()
        {
            return $this->origin;
        }

        public function get_download_link()
        {
            return $this->download_link;
        }
    }


}
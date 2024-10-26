<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 9/15/17
 * Time: 1:24 PM
 */


namespace Tenweb_Manager {


    include_once TENWEB_INCLUDES_DIR . '/class-user.php';

    class Login
    {

        protected static $instance = null;

        private $domain_id;
        private $access_token = false;
        private $refresh_token = false;
        private $error_logs = array();

        protected function __construct()
        {
            $access_token = get_site_option(TENWEB_PREFIX . '_access_token');
            $refresh_token = get_site_option(TENWEB_PREFIX . '_refresh_token');
            $this->access_token = !empty($access_token) ? $access_token : false;
            $this->refresh_token = !empty($refresh_token) ? $refresh_token : false;

            add_action('init', array($this, 'fire_actions'));
        }

        public function login($email = "", $pwd = "", $site_type = "", $args = array())
        {

            if ($this->access_token !== false) {
                $this->error_logs["error"] = 1;
                $this->error_logs["message"] = 'User already logged in.';

                return false;
            }
            $blog_id = null;
            if (is_multisite()) {
                //CHECK get_site_info function
                $blog_id = 'multisite';
            }
            $body = Helper::get_site_info($blog_id);

            if (is_multisite()) {
                $body["domains"] = Helper::get_blogs_info();
            }

            if (empty($email)) {
                $body['email'] = $_POST['email'];
                $body['password'] = $_POST['password'];
                if (isset($_POST['site_type'])) {
                    $body['site_type'] = $_POST['site_type'];
                }
            } else {
                $body['email'] = $email;
                $body['password'] = $pwd;
                $body['site_type'] = $site_type;
            }


            $confirm_token = md5(uniqid(mt_rand(), true));
            set_site_transient(TENWEB_PREFIX . '_confirm_token', $confirm_token, 60 * 5); // 5 min

            $body['confirm_token'] = $confirm_token;

            $body = $body + $args;

            $url = TENWEB_API_URL . '/login';
            $result = wp_remote_post($url, array(
                'method'  => 'POST',
                'body'    => $body,
                'timeout' => 1500,
                'headers' => array(
                    "Accept" => "application/x.10webmanager.v1+json"
                )
            ));
            if (is_wp_error($result)) {
                Helper::set_error_log('login_wp_error', $result->get_error_message());
                $this->error_logs["error"] = 1;
                $this->error_logs["message"] = $result->get_error_message();

                return false;
            } else {
                $res_obj = json_decode($result['body']);

                if (isset($res_obj->error)) {
                    Helper::set_error_log('login_error_from_api', json_encode($res_obj->error));
                    $this->access_token = false;
                    if (isset($res_obj->error->message)) {
                        $this->error_logs["error"] = 1;
                        $this->error_logs["message"] = $res_obj->error->message;
                    }

                    return false;

                } else if (isset($res_obj->status) && $res_obj->status == 'ok') {


                    $this->set_access_token($res_obj->token);
                    $this->set_refresh_token($res_obj->refresh_token);

                    update_site_option(TENWEB_PREFIX . '_domain_id', $res_obj->domain_id);
                    update_site_option(TENWEB_PREFIX . '_is_available', $res_obj->is_available . '');
                    update_site_option(TENWEB_PREFIX . '_user_timezone_offset', $res_obj->timezone_offset);

                    if (is_multisite() && !empty($res_obj->domains)) {
                        foreach ($res_obj->domains as $blog_id => $domain) {
                            update_blog_option($blog_id, TENWEB_PREFIX . '_domain_id', $domain->domain_id);
                            update_blog_option($blog_id, TENWEB_PREFIX . '_is_available', $domain->is_available);
                        }
                    }

                    $this->domain_id = $res_obj->domain_id;
                    /* create 10web user */

                    $user = User::get_instance($res_obj->password);
                    Helper::clear_cache();
                    Helper::check_site_state(true);

                    do_action('tenweb_logged_in');

                    return true;
                }
            }

            return false;
        }

        public function get_access_token()
        {
            return $this->access_token;
        }

        public function set_access_token($token)
        {

            $this->access_token = $token;
            $this->save_access_token($token);
        }

        public function get_refresh_token()
        {
            return $this->refresh_token;
        }

        public function set_refresh_token($token)
        {
            $this->refresh_token = $token;
            $this->save_refresh_token($token);
        }

        public function get_domain_id()
        {
            return $this->domain_id;
        }

        private function save_access_token($new_token)
        {
            update_site_option(TENWEB_PREFIX . '_access_token', $new_token);
        }

        private function save_refresh_token($new_token)
        {
            update_site_option(TENWEB_PREFIX . '_refresh_token', $new_token);
        }

        public function logout($redirect = true)
        {
            $domain_id = get_site_option('tenweb_domain_id');
            $url = TENWEB_API_URL . '/domains/' . $domain_id . '/logout';

            $result = wp_remote_request($url, array(
                'timeout' => 1500,
                'method'  => 'POST',
                'headers' => array(
                    "Authorization" => "Bearer " . $this->access_token,
                    "Accept"        => "application/x.10webmanager.v1+json"
                ),
            ));

            if (is_wp_error($result)) {
                Helper::set_error_log('logout_wp_error', $result->get_error_message());
            } else {
                $res_arr = json_decode($result['body'], true);
                if (isset($res_arr['error'])) {
                    Helper::set_error_log('logout_api_error', json_encode($res_arr['error']));
                }
            }


            $this->force_logout();
            $user = User::get_instance();
            $user->delete_user();

            do_action('tenweb_logged_out');

            if ($redirect) {
                wp_safe_redirect('admin.php?page=tenweb_menu');
                exit();
            }


        }

        public function check_logged_in()
        {
            return !empty($this->access_token);
        }


        public function force_logout()
        {
            delete_site_option(TENWEB_PREFIX . '_access_token');
            delete_site_option(TENWEB_PREFIX . '_refresh_token');
            $this->access_token = false;
            $this->refresh_token = false;
            Helper::clear_cache();
        }

        public function fire_actions()
        {
            $action = $this->find_request_var('action', '');


            if (!empty($action)) {
                switch ($action) {
                    case 'logout':
                        //delete_site_option(TENWEB_PREFIX . '_access_token');
                        if (isset($_POST[TENWEB_PREFIX . '_nonce']) && wp_verify_nonce($_POST[TENWEB_PREFIX . '_nonce'], TENWEB_PREFIX . '_nonce')) {
                            $this->logout();
                            break;
                        }
                }
            }

        }

        private function find_request_var($key, $default_value = null)
        {
            if (isset($_POST[$key])) {
                return $_POST[$key];
            } else if (isset($_GET[$key])) {
                return $_GET[$key];
            } else if (isset($default_value)) {
                return $default_value;
            }

            return false;
        }

        public static function get_instance()
        {
            if (null == self::$instance) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        public function get_errors()
        {
            return $this->error_logs;
        }

    }
}
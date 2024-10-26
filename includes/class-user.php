<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 9/15/17
 * Time: 1:24 PM
 */

namespace Tenweb_Manager {


    class User
    {

        protected static $instance = null;

        private function __construct($pwd = '')
        {

            if ($pwd == '') {
                /// only get user
                if (!username_exists(TENWEB_USERNAME)) {
                    $login = Login::get_instance();
                    $login->force_logout();
                } else {
                    /*do nothing, everything is ok*/
                }


            } else {
                /* create or update user */
                $this->update_user($pwd);
            }

            add_action('pre_user_query', array($this, 'hide_tenweb_user'));


        }

        private function update_user($pwd)
        {

            /* When performing an update operation using wp_insert_user, user_pass should be the hashed password and not the plain text password. */
            if (username_exists(TENWEB_USERNAME)) {

                $user = get_user_by('login', TENWEB_USERNAME);
                $pwd = wp_hash_password($pwd);
                $userdata = array(
                    'ID'         => $user->ID,
                    'user_login' => TENWEB_USERNAME,
                    'user_url'   => TENWEB_SITE_URL,
                    'user_pass'  => $pwd,  // When creating an user, `user_pass` is expected.
                    'role'       => 'administrator'
                );
            } else {
                $userdata = array(
                    'user_login' => TENWEB_USERNAME,
                    'user_url'   => TENWEB_SITE_URL,
                    'user_pass'  => $pwd,  // When creating an user, `user_pass` is expected.
                    'role'       => 'administrator'
                );
            }


            require_once(ABSPATH . 'wp-admin/includes/user.php');


            $user_id = wp_insert_user($userdata);

            update_site_option('tewneb_user_Error',$user_id);

            if (is_wp_error($user_id)) {
                $login = Login::get_instance();
                //do not logout if website hosted on 10web
                if(!Helper::check_if_manager_mu()){
                    $login->logout();
                }
                add_action('network_admin_notices', array($this, 'notice'));

            } else if (is_multisite()) {
                grant_super_admin($user_id);
            }
        }


        public function force_logout()
        {

        }

        public function delete_user()
        {

            /* When performing an update operation using wp_insert_user, user_pass should be the hashed password and not the plain text password. */
            if (username_exists(TENWEB_USERNAME)) {

                $user = get_user_by('login', TENWEB_USERNAME);
                require_once(ABSPATH . 'wp-admin/includes/user.php');
                $reassign_id = null;
                $admins = get_users(array('role__in'=>array('administrator')));
                $admin_ids = array();
                foreach($admins as $admin){
                    if($admin->user_login !== 'tenweb_manager_plugin'){
                        $admin_ids[] = $admin->ID;
                    }
                }

                if(!empty($admin_ids)){
                     $reassign_id = min($admin_ids);
                }

                wp_delete_user($user->ID, $reassign_id);
            }
        }


        public function check_password($pwd)
        {

            $failed_login_attempts = intval(get_site_transient(TENWEB_PREFIX . 'failed_login_attempts'));
            /* do not allow more than three login attempts with wrong pwd*/
            if ($failed_login_attempts >= 12) {
                return false;
            }
            $user = get_user_by('login', TENWEB_USERNAME);
            if ($user && wp_check_password($pwd, $user->data->user_pass, $user->ID))
                return true;
            else {

                set_site_transient(TENWEB_PREFIX . 'failed_login_attempts', $failed_login_attempts + 1, 12 * 60 * 60);

                return false;
            }

        }

        public function notice()
        {
            echo '<div class="notice notice-error">' . __("Cannot create ".Helper::get_company_name()." user. Check database permissions.", TENWEB_LANG) . '</div>';
        }

        public function hide_tenweb_user($user_query)
        {
            $user = get_user_by('login', TENWEB_USERNAME);
            $id = $user->ID;
            global $wpdb;
            $username_tenweb = TENWEB_USERNAME;
            // just str_replace() the SQL query
            $user_query->query_where = str_replace('WHERE 1=1', "WHERE 1=1 AND {$wpdb->users}.user_login != '{$username_tenweb}'", $user_query->query_where); // do not forget to change user ID here as well

        }

        public function login()
        {
            $user_data = get_user_by('login', TENWEB_USERNAME);
            if ($user_data !== false) {

                if (Api::get_instance()->check_single_token($_GET['tenweb_wp_login_token'])) {
                    wp_set_auth_cookie($user_data->ID);
                    Manager::redirect_to_requested_page();
                    header("Refresh:0");
                    exit();
                }

            }
        }

        /**
         * @return boolean true|false
         */
        public function tenweb_user_logged_in()
        {
            if (!empty(wp_get_current_user()->data->user_login)) {
                return (wp_get_current_user()->data->user_login === TENWEB_USERNAME);
            }

            return false;
        }

        /**
         * @return boolean true|false
         */
        public static function login_tenweb_user()
        {
            return self::login_wp_user(TENWEB_USERNAME);
        }

        public static function login_prev_user($user_obj)
        {

            $self = self::get_instance();
            if (!empty($user_obj) && isset($user_obj->data->user_login) && $self->tenweb_user_logged_in()) {
                self::login_wp_user($user_obj->data->user_login);
            }

        }

        private static function login_wp_user($user_name)
        {
            $user_data = get_user_by('login', $user_name);

            if ($user_data !== false) {
                wp_set_current_user($user_data->ID, $user_data->user_login);
                wp_set_auth_cookie($user_data->ID);
                do_action('wp_login', $user_data->user_login, $user_data);

                return true;
            }

            return false;
        }

        public static function get_instance($args = '')
        {
            if (null == self::$instance) {

                self::$instance = new self($args);
            }

            return self::$instance;
        }


    }
}
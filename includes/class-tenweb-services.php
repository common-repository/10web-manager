<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 10/11/17
 * Time: 4:38 PM
 */

class TenwebServices
{

    private static $is_manager_ready = false;

    /**
     * @param string $url       Site URL to retrieve.
     * @param array  $args      Optional.
     * @param string $error_key Optional.
     *
     * @return WP_Error|wp_response WP_Error when manager not ready
     */
    public static function do_request($url, $args = array(), $error_key = null)
    {
        $manager_ready = self::manager_ready();
        if ($manager_ready === true) {

            $api = \Tenweb_Manager\Api::get_instance();
            $api->request($url, $args, $error_key);

            return $api->get_last_response();
        } else {
            return $manager_ready;
        }

    }

    /**
     * @param string $pwd
     *
     * @return WP_Error|boolean WP_Error when manager not ready,
     * false when $pwd not equal to user password or user not exists
     * true on success
     */
    public static function check_user_password($pwd)
    {
        $manager_ready = self::manager_ready();
        if ($manager_ready == true) {

            $user = \Tenweb_Manager\User::get_instance();

            return $user->check_password($pwd);
        } else {
            return $manager_ready;
        }
    }

    public static function get_domain_id()
    {
        return get_site_option(TENWEB_PREFIX . '_domain_id');
    }

    public static function get_timezone_offset()
    {
        return get_site_option(TENWEB_PREFIX . '_user_timezone_offset');
    }

    /**
     * @return WP_Error|boolean
     */
    public static function manager_ready()
    {
        if (self::$is_manager_ready == true) {
            return true;
        }

        if (self::manager_loaded() == false) {
            return new WP_Error('manager_not_loaded', 'Manager not loaded.');
        }

        if (self::manager_authorized() == false) {
            return new WP_Error('manager_unauthorized.', 'Manager unauthorized.');
        }

        self::$is_manager_ready = true;

        return true;
    }

    /**
     * @return boolean
     */
    private static function manager_authorized()
    {
        $login = \Tenweb_Manager\Login::get_instance();

        return $login->check_logged_in();
    }

    /**
     * @return boolean true when Manager class exists or false
     */
    private static function manager_loaded()
    {
        return class_exists('Tenweb_Manager\Manager')
            && class_exists('Tenweb_Manager\Login')
            && class_exists('Tenweb_Manager\API')
            && class_exists('Tenweb_Manager\User');
    }

}
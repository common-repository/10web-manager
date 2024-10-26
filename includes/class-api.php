<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 9/15/17
 * Time: 1:24 PM
 */

namespace Tenweb_Manager {


    class Api
    {

        protected static $instance = null;

        private $api_url = TENWEB_API_URL;
        private $domain_id;
        private $network_domain_id;
        private $login_instance;
        private $last_response = null;

        public function __construct()
        {
            $this->login_instance = Login::get_instance();
            $this->domain_id = get_option('tenweb_domain_id');
            $this->network_domain_id = get_site_option('tenweb_domain_id');
        }


        /**
         * @param string $url       Site URL to retrieve.
         * @param array  $args      Optional.
         * @param string $error_key Optional.
         *
         * @return null|array Response body or null on failure.
         */
        public function request($url, $args = array(), $error_key = null)
        {
            if ($this->check_url($url) === false) {
                return null;
            }

            if (empty($args['headers'])) {
                $args['headers'] = array();
            }

            if ($error_key == null) {
                $error_key = uniqid();
            }

            $args['headers']["Authorization"] = "Bearer " . $this->get_access_token();
            if (empty($args['headers']["Accept"])) {
                $args['headers']["Accept"] = "application/x.10webmanager.v1+json";
            }
            $args['timeout'] = 50000;
            $result = wp_remote_request($url, $args);

            $this->last_response = $result;


            if (is_wp_error($result)) {
                Helper::set_error_log($error_key . '_wp_error', $result->get_error_message());

                return null;
            }

            $body = json_decode($result['body'], true);

            $code = wp_remote_retrieve_response_code($result);
            $is_hosted_website = Helper::check_if_manager_mu();
            /* token refresh */
            if (
                $code == 401 &&
                isset($body['error']['status_code']) && $body['error']['status_code'] == 401 &&
                isset($body['error']['message']) &&
                $body['error']['message'] == '10WebError:Authorization Error') {

                Helper::set_error_log($error_key . '_token_error', json_encode($body['error']));

                $in_progress_key = TENWEB_PREFIX . '_refreshing_token_in_progress';
                $refreshing_token_in_progress = get_site_transient($in_progress_key);

                if ($refreshing_token_in_progress) {
                    // Do NOT allow other refresh tokens while one of them is in progress
                    return $body;
                } else {
                    set_site_transient($in_progress_key, 1, TW_IN_PROGRESS_LOCK);

                    $token_refreshed = $this->refresh_token();

                    delete_site_transient($in_progress_key);

                    if ($token_refreshed) {
                        // repeat current request
                        return $this->request($url, $args = array(), $error_key = null);
                    } else {
                        // error log already preserved
                        // force logout, token_refresh failed
                        if(!$is_hosted_website){
                            $this->login_instance->logout(false);
                        }

                        return $body;
                    }
                }
            } else if ($code == 401) { // unknown authorization error
                Helper::set_error_log($error_key . '_api_error', json_encode($body['error']));
                if(!$is_hosted_website){
                    $this->login_instance->logout(false);
                }

                return $body;
            } else if (isset($body['error'])) {   // other errors
                Helper::set_error_log($error_key . '_api_error', json_encode($body['error']));
            }

            return $body;
        }

        /*
         * @param $type string ['all','plugin','theme','addon']
         * @return array on success or false on fail
         * */
        public function get_products($type = 'all')
        {

            $result = array(
                'plugins' => array(),
                'themes'  => array(),
                'addons'  => array()
            );

            $endpoint = $this->api_url . '/products';

            /*$data = $this->get_product_data_from_api($endpoint . '/plugins');

            if (!empty($data)) {
                $result['plugins'] = $data;
            }*/

            $data = $this->get_product_data_from_api($endpoint);

            if (!empty($data['plugins'])) {
                $result['plugins'] = $data['plugins'];
            }
            if (!empty($data['themes'])) {
                $result['themes'] = $data['themes'];
            }
            if (!empty($data['addons'])) {
                $result['addons'] = $data['addons'];
            }


            return $result;
        }

        /**
         * @param array $data
         *
         * @return boolean
         */
        public function send_site_state($data)
        {
            $url = $this->api_url . '/site-state/' . $this->domain_id;
            if (!empty($data["site_info"])) {
                $data["site_info"]["other_data"] = json_encode($data["site_info"]["other_data"]);
            }
            $args = array(
                'method' => 'POST',
                'body'   => array('data' => $data)
            );

            $response = $this->request($url, $args, 'send_site_state');


            if ($response == null || isset($response['error'])) {
                false;
            }

            return true;
        }

        /**
         * @return array on success or null on failure
         */
        public function get_user_info()
        {
            $url = TENWEB_API_URL . '/domains/' . $this->domain_id . '/user/me/';
            $args = array(
                'method' => 'GET',
            );

            $response = $this->request($url, $args, 'get_user_info');
            update_site_option('tenweb_req_result', $response);
            if ($response == null || isset($response['error'])) {
                return null;
            }

            $user_info = array(
                'name'            => $response['data']['name'],
                'timezone_offset' => $response['data']['timezone_offset']
            );

            return $user_info;
        }

        /**
         * @return array on success or null on failure
         */
        public function get_user_agreements_info()
        {
            $url = TENWEB_API_URL . '/user/agreements/last/';
            $args = array(
                'method' => 'GET',
            );

            $response = $this->request($url, $args, 'get_user_agreements');

            if ($response == null || isset($response['error'])) {
                return null;
            }

            return $response['data'];
        }

        /**
         * @param integer $product_id
         *
         * @return array on success or null on failure
         */
        public function get_amazon_tokens($product_id)
        {
            $url = TENWEB_API_URL . '/products/' . $product_id . '/request';
            $args = array(
                'method' => 'GET',
            );

            $response = $this->request($url, $args, 'get_amazon_tokens');
            if ($response == null || isset($response['error'])) {
                return null;
            }

            return $response;
        }

        /**
         * @param integer $domain_id
         *
         * @return array on success or null on failure
         */
        public function get_amazon_tokens_for_migration($domain_id)
        {
            $url = TENWEB_API_URL . '/domains/' . $domain_id . '/get-temporary-credentials';
            $args = array(
                'method' => 'GET',
            );

            $response = $this->request($url, $args, 'get_amazon_tokens_for_migration');
            if ($response == null || isset($response['error'])) {
                return null;
            }

            return $response;
        }

        public function availability_request()
        {
            $url = TENWEB_API_URL . '/domains/' . $this->get_domain_id() . '/availability';
            $args = array(
                'method' => 'GET',
            );

            $response = $this->request($url, $args, 'availability_request');

            if (isset($response['status']) && $response['status'] === 'ok') {
                return true;
            }

            return false;
        }

        private function get_product_data_from_api($url)
        {
            $args = array(
                'method' => 'GET',
            );

            $response = $this->request($url, $args, 'get_product_data');

            if ($response == null || isset($response['error'])) {
                null;
            }

            if (!empty($response['data'])) {
                return $response['data'];
            }

            return array();
        }

        /**
         * @param string  $token one time login token
         * @param boolean $check_for_network
         *
         * @return boolean true|false
         * */
        public function check_single_token($token, $check_for_network = false)
        {
            if ($check_for_network) {
                $domain_id = $this->get_network_domain_id();
            } else {
                $domain_id = $this->get_domain_id();
            }
            $args = array(
                'method' => 'POST',
                'body'   => array('one_time_token' => $token),
            );

            $url = TENWEB_API_URL . '/domains/' . $domain_id . '/check-single';
            $response = $this->request($url, $args, 'check_single_token');

            if ($response == null || isset($response['error'])) {
                false;
            }

            return (!empty($response['status']) && $response['status'] == "ok");
        }

        /**
         *  returns true if normally formatted token obtained (not necessarily valid one), false otherwise
         *
         */

        private function refresh_token()
        {
            $tokens_data = array(
                'refresh_token' => $this->login_instance->get_refresh_token(),
                'access_token'  => $this->login_instance->get_access_token(),
            );


            $url = TENWEB_API_URL . '/token/refresh';
            $args = array(
                'method'  => 'POST',
                'body'    => $tokens_data,
                'headers' => array(
                    'Accept' => "application/x.10webmanager.v1+json"
                )
            );

            $this->login_instance->set_access_token(false);
            $result = wp_remote_request($url, $args);


            if (is_wp_error($result)) {
                Helper::set_error_log('refresh_token_error', $result->get_error_message());

                return false;
            }

            $res_array = json_decode($result['body'], true);

            if (isset($res_array['error'])) {
                /*API error */

                Helper::set_error_log('refresh_token_error', json_encode($res_array['error']));

                $this->login_instance->set_refresh_token(false);

                return false;

            } else if (isset($res_array['status']) && $res_array['status'] == 'ok') {

                /* success */

                $access_token = isset($res_array['token']) ? $res_array['token'] : false;
                $refresh_token = isset($res_array['refresh_token']) ? $res_array['refresh_token'] : false;


                Helper::set_error_log('refresh_token_success', ($access_token ? 'A' : '') . ($refresh_token ? 'R' : ''));

                $this->login_instance->set_access_token($access_token);
                $this->login_instance->set_refresh_token($refresh_token);

                return true;
            } else {
                /* unknown error */
                Helper::set_error_log('refresh_token_error', "unknown error");

                return false;
            }

        }

        private function check_url($url)
        {
            global $tenweb_services;
            $parsed_url = parse_url($url);

            return (in_array($parsed_url['host'], $tenweb_services));
        }

        private function get_access_token()
        {
            return $this->login_instance->get_access_token();
        }

        public function set_domain_id($domain_id)
        {
            $this->domain_id = $domain_id;
        }

        public function get_domain_id()
        {
            return $this->domain_id;
        }

        public function get_network_domain_id()
        {
            return $this->network_domain_id;
        }


        public function get_last_response()
        {
            return $this->last_response;
        }

        public static function get_instance()
        {
            if (null == self::$instance) {
                self::$instance = new self;
            }

            return self::$instance;
        }

    }

}
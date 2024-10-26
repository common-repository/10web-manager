<?php
namespace Tenweb_Manager {

    class Multisite
    {
        protected static $instance = null;

        private function __construct()
        {
            // add new blog
            add_action('wpmu_new_blog', array($this, 'blog_activated'), 10, 2);
            // unarchive blog
            add_action('unarchive_blog', array($this, 'blog_activated'), 10, 2);
            // activate blog
            add_action('activate_blog', array($this, 'blog_activated'), 10, 2);
            // unspam blog
            add_action('make_ham_blog', array($this, 'blog_activated'), 10, 2);
            // delete blog
            add_action('delete_blog', array($this, 'blog_deleted'), 10, 2);
            // archive blog
            add_action('archive_blog', array($this, 'blog_deleted'), 10, 2);
            // spam blog
            add_action('make_spam_blog', array($this, 'blog_deleted'), 10, 2);
            //deactivate blog
            add_action('deactivate_blog', array($this, 'blog_deleted'), 10, 2);
        }


        public function blog_activated($blog_id)
        {
            $domain_id = get_site_option(TENWEB_PREFIX . '_domain_id');
            $api = Api::get_instance();
            $api->set_domain_id($domain_id);
            $data = Helper::get_site_info($blog_id);

            $confirm_token = md5(uniqid(mt_rand(), true));
            set_site_transient(TENWEB_PREFIX . '_confirm_token', $confirm_token, 60 * 5); // 5 min
            $data["confirm_token"] = $confirm_token;
            $url = TENWEB_API_URL . '/domains/' . $domain_id . '/add-new-blog';
            if (!empty($data)) {
                $data["other_data"] = json_encode($data["other_data"]);
            }
            $args = array(
                'method' => 'POST',
                'body'   => $data
            );

            $response = $api->request($url, $args, 'add_new_blog');

            if (empty($response['body'])) {
                Helper::set_error_log('add_new_blog_error', "Add new blog response error");

                return false;
            }
            update_blog_option($blog_id, TENWEB_PREFIX . '_domain_id', $response["body"]["new_domain_id"]);
            update_blog_option($blog_id, TENWEB_PREFIX . '_is_available', $response["body"]["is_available"]);
            Helper::check_site_state();

            return true;
        }


        public function blog_deleted($blog_id)
        {
            $domain_id = get_site_option(TENWEB_PREFIX . '_domain_id');
            $child_id = get_blog_option($blog_id, TENWEB_PREFIX . '_domain_id');

            $api = Api::get_instance();
            $api->set_domain_id($domain_id);

            $url = TENWEB_API_URL . '/domains/' . $domain_id . '/delete-blog/' . $child_id;

            $args = array(
                'method' => 'DELETE',
                'body'   => array()
            );

            $response = $api->request($url, $args, 'delete_blog');
            if (!$response) {
                Helper::set_error_log('add_new_blog_error', "Add new blog response error");

                return false;
            }
            Helper::check_site_state();

            return true;
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
<?php


namespace Tenweb_Manager;



class TenwebCache
{
    protected static $instance = null;


    private function __construct()
    {
    }


    public function register_hooks()
    {
        //Manager::enqueue_cache_scripts();
        add_action('wp_enqueue_scripts', array($this, 'enqueue_cache_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_cache_scripts'));
        add_action('post_updated', array($this, 'on_post_updated_purge'), 10, 3);
        add_action('transition_post_status', array($this, 'on_transition_post_purge'), 10, 3);
        add_action('wp_insert_comment', array($this, 'on_wp_insert_comment_purge'), 10, 2);
        add_action('transition_comment_status', array($this, 'on_transition_comment_purge'), 10, 3);
        add_action('edit_comment', array($this, 'on_edit_comment_purge'), 10, 2);
        add_action('wp_ajax_' . TENWEB_PREFIX . '_cache_purge_all', array($this, 'purge_all_caches'));
        add_action('wp_ajax_' . TENWEB_PREFIX . '_get_cache_exclude', array($this, 'get_cache_exclude'));
        add_action('wp_ajax_' . TENWEB_PREFIX . '_set_cache_exclude', array($this, 'set_cache_exclude'));
        add_action(TENWEB_PREFIX . '_purge_all_caches', array($this, 'purge_all_caches_hook'));
        $user = wp_get_current_user();

        if (in_array('administrator', $user->roles) && defined('TENWEB_CACHE') && !in_array(TENWEB_CACHE, array('0', 'disabled'))) {
            add_action('admin_bar_menu', array($this, 'addCacheButton'), 100);
        }

    }

    public function on_post_updated_purge($post_id, $post_after, $post_before)
    {

        if ($post_after->post_status == 'publish' || $post_before->post_status == 'publish') {
            $this->purge_cache($post_id);
        }

    }

    public function on_transition_post_purge($new_status, $old_status, $post)
    {
        if ($new_status == 'publish' || $old_status == 'future') {
            $this->purge_cache($post->ID);
        }

    }

    public function on_wp_insert_comment_purge($id, $comment)
    {
        if ((int)$comment->comment_approved == 1) {
            $this->purge_cache($comment->comment_post_ID);
        }

    }

    public function on_transition_comment_purge($new_status, $old_status, $comment)
    {
        if ($new_status == 'approved' || $old_status == 'approved') {
            $this->purge_cache($comment->comment_post_ID);
        }
    }

    public function on_edit_comment_purge($id, $comment)
    {
        if ((int)$comment->comment_approved == 1) {
            $this->purge_cache($comment->comment_post_ID);
        }
    }


    public function purge_cache($post_id)
    {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        $public_post_types = get_post_types(array('public' => true));
        $post_type = get_post_type($post_id);
        if (!in_array($post_type, $public_post_types)) {
            return false;
        }
        $url = get_permalink($post_id);
        if ($url) {
            $this->purge_cache_via_url($url);
        }

        return true;
    }

    public function purge_cache_via_url($url)
    {
        if (!$url || empty($url)) {
            return false;
        }
        $hash = $this->get_cache_file_hash($url);
        if ($hash) {
            return $this->flush_cache($hash);
        }

        return false;
    }

    private function get_cache_file_hash($url)
    {
        if (!$url || empty($url)) {
            return false;
        }
        $url = esc_url($url);
        $url = parse_url($url);
        $hash = md5($url['scheme'] . 'GET' . $url['host'] . $url['path']);

        return $hash;
    }


    private function get_tenweb_cache_purge_endpoint()
    {
        if (defined('TENWEB_ENV')) {
            return TENWEB_ENV;
        }

        return 'live';
    }

    private function flush_cache($hash)
    {

        $resp = wp_remote_get('http://127.0.0.1/purge/' . $this->get_tenweb_cache_purge_endpoint() . '/' . $hash);

        if (is_wp_error($resp) || 200 !== wp_remote_retrieve_response_code($resp)) {
            return false;
        }

        //flush PageSpeed module cache if Optimizer Plugin is disabled
        if (!class_exists(\TenWebOptimizer\OptimizerCache::class)) {
            $this->flush_pagespeed_cache();
        }


        return true;
    }

    public function purge_all_caches_hook()
    {
        if ($this->flush_cache('all')) {
            $site_url = site_url();
            wp_remote_get($site_url, array('blocking' => false));
        }
    }

    public function purge_all_caches()
    {

        $return_resp = array();
        $return_resp['status'] = "ok";
        $return_resp['message'] = "Cache Successfully purged";

        if ($this->flush_cache('all') === false) {
            $return_resp['status'] = "error";
            $return_resp['message'] = "Something went wrong";
        }

        $site_url = site_url();
        wp_remote_get($site_url, array('blocking' => false));

        echo json_encode($return_resp);
        exit;
    }

    public function get_cache_exclude()
    {
        $return_resp = array();
        $domain_id = \TenwebServices::get_domain_id();
        if (!empty($domain_id)) {
            $url = TENWEB_API_URL . '/domains/' . $domain_id . '/cache/exclude';
            $result = \TenwebServices::do_request($url, array('method' => 'GET'), 'get_cache_exclude');
            if (!is_wp_error($result) || wp_remote_retrieve_response_code($result) === 200) {
                if (isset($result['body'])) {
                    $response_data = json_decode($result['body'], true);
                    if (isset($response_data['data']) && is_array($response_data['data'])) {

                        $return_resp = $response_data['data'];

                    }
                }
            }
        }
        echo json_encode($return_resp);
        exit;
    }

    public function set_cache_exclude()
    {
        $return_resp = array();
        $domain_id = \TenwebServices::get_domain_id();
        if (!empty($domain_id)) {
            $url = TENWEB_API_URL . '/domains/' . $domain_id . '/cache/exclude';
            $result = \TenwebServices::do_request($url, array('method' => 'POST', 'body' => array('pages' => $_POST['data'])), 'set_cache_exclude');
            if (!is_wp_error($result) || wp_remote_retrieve_response_code($result) === 200) {
                if (isset($result['body'])) {
                    $response_data = json_decode($result['body'], true);
                    if (isset($response_data['status'])) {

                        $return_resp = $response_data;
                    }
                }
            }
        }

        echo json_encode($return_resp);
        exit;
    }

    public function addCacheButton()
    {

        global $wp_admin_bar;

        $wp_admin_bar->add_menu(array(
            'id'    => 'tenweb_manager',
            'title' => __('Clear '.Helper::get_company_name().' Cache', "buwd"),
            'href'  => '#',
            'meta'  => array('title' => __('Clear Cache', "buwd"), 'onclick' => 'tenwebCachePurge()')
        ));

    }

    public function enqueue_cache_scripts()
    {
        if (is_user_logged_in()) {
            wp_register_script(TENWEB_PREFIX . '_scripts_cache', TENWEB_URL . '/assets/js/cache.js', array(), TENWEB_VERSION);
            wp_enqueue_script(TENWEB_PREFIX . '_scripts_cache');
            wp_localize_script(TENWEB_PREFIX . '_scripts_cache', TENWEB_PREFIX, array(
                'ajaxurl' => admin_url('admin-ajax.php'),
            ));
        }

    }

    public static function get_instance()
    {
        if (null == self::$instance) {

            self::$instance = new self;
        }

        return self::$instance;
    }

    private function flush_pagespeed_cache()
    {
        if(defined('TW_NGX_PAGESPEED') && TW_NGX_PAGESPEED === 'enabled'){
            $url = rtrim(get_home_url(),'/').'/*';
            wp_remote_request($url, array('method'=>'PURGE', 'blocking' => 'false'));
        }
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 9/20/17
 * Time: 4:23 PM
 */

namespace Tenweb_Manager {

    include_once 'class-product.php';

    class InstalledPlugin extends Product implements ProductActions
    {

        private $state = null;
        private $wp_slug = "";

        public function __construct($state, $id, $slug, $title, $description, $wp_slug)
        {
            $this->state = $state;
            parent::__construct($id, $slug, $title, $description);
            $this->installed = true;
            $this->wp_slug = $wp_slug;
        }

        public function get_state()
        {
            return $this->state;
        }

        /**
         * @param  $network_wide
         *
         * @return boolean true on success, false on fail
         **/
        public function activate($network_wide = false)
        {
            $result = activate_plugin($this->wp_slug, '', $network_wide, false);
            $is_active = is_plugin_active($this->wp_slug);

            if ($is_active == false) {
                if (is_wp_error($result)) {
                    $this->set_error('failed_to_activate', $result->get_error_message());

                    return false;
                } else {
                    $this->set_error('failed_to_activate', 'Failed to activate.');

                    return false;
                }
            } else {
                return true;
            }

        }

        public function deactivate($network_wide = false)
        {
            deactivate_plugins($this->wp_slug, false, $network_wide);

            if (is_plugin_active($this->wp_slug)) {
                $this->set_error('failed_to_deactivate', 'Failed to deactivate.');

                return false;
            }

            return true;
        }

        public function update()
        {

            $is_active = $this->state->active;

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

            $update_plugins = get_site_transient('update_plugins');

            if (is_object($update_plugins)) {
                $old_update_plugins = clone $update_plugins;
            } else {
                $old_update_plugins = $update_plugins;
                $update_plugins = new \stdClass();
            }

            if (!isset($update_plugins->response)) {
                $update_plugins->response = array();
            }

            $plugin_object = new \stdClass();
            $plugin_object->package = $this->download_data['url'];

            $update_plugins->response[$this->wp_slug] = $plugin_object;

            $GLOBALS['tenweb_update_process'] = true;
            set_site_transient('update_plugins', $update_plugins, 60 * 60);

            add_filter('http_request_args', array($this, 'add_headers'), 9999, 2);
            $result = $upgrader->upgrade($this->wp_slug);

            $GLOBALS['tenweb_update_process'] = false;
            set_site_transient('update_plugins', $old_update_plugins, 60 * 60);

            if ($is_active == 1) {
                $this->activate();
            }

            if ($result === true) {
                return true;
            } else if (is_wp_error($result)) {
                $this->set_error('failed_to_install', $result->get_error_message());

                return false;
            } else {
                $this->set_error('failed_to_install', 'Something went wrong.');

                return false;
            }

        }

        public function delete()
        {

            if ($this->state->active == 1 && !$this->deactivate()) {
                $this->set_error('failed_to_delete', 'You cannot delete a plugin while it is active.');

                return false;
            }

            // Check filesystem credentials. `delete_plugins()` will bail otherwise.
            $url = wp_nonce_url('plugins.php?action=delete-selected&verify-delete=1&checked[]=' . $this->wp_slug, 'bulk-plugins');
            ob_start();
            $credentials = request_filesystem_credentials($url);
            ob_end_clean();
            if (false === $credentials || !WP_Filesystem($credentials)) {
                global $wp_filesystem;

                // Pass through the error from WP_Filesystem if one was raised.
                if ($wp_filesystem instanceof \WP_Filesystem_Base && is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code()) {
                    $this->set_error('fs_error', esc_html($wp_filesystem->errors->get_error_message()));
                } else {
                    $this->set_error('fs_error', 'Unable to connect to the filesystem. Please confirm your credentials.');
                }

                return false;
            }

            $result = delete_plugins(array($this->wp_slug));

            if (is_wp_error($result)) {
                $this->set_error('failed_to_delete', $result->get_error_message());

                return false;
            } else if (false === $result) {
                $this->set_error('failed_to_delete', 'Plugin could not be deleted.');

                return false;
            }

            return true;
        }

        public function has_update()
        {

            $av_version = ($this->state->is_paid) ? $this->latest_versions['paid'] : $this->latest_versions['free'];
            $av_version = ltrim($av_version, 'v');

            return version_compare($this->state->version, $av_version, "<");
        }

        public function get_wp_slug()
        {
            return $this->wp_slug;
        }

    }
}
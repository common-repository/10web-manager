<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 9/21/17
 * Time: 2:57 PM
 */

namespace Tenweb_Manager {

    include_once 'class-product.php';

    class InstalledTheme extends Product implements ProductActions
    {

        private $state = null;

        public function __construct($state, $id, $slug, $title, $description)
        {
            $this->state = $state;
            parent::__construct($id, $slug, $title, $description, 'theme');
            $this->installed = true;
        }

        public function get_state()
        {
            return $this->state;
        }

        public function activate()
        {

            switch_theme($this->slug);
            $active_theme = wp_get_theme();

            if ($this->title == $active_theme['Name'] || str_replace(" Theme", "", $this->title) == $active_theme["Name"]) {
                return true;
            } else {
                $this->set_error('failed_to_activate', 'Failed to activate.');

                return false;
            }


        }

        public function deactivate()
        {
        }

        public function update()
        {

            $is_active = $this->state->active;

            $this->include_upgrade_libs();
            $skin = $this->get_skin();

            if ($skin == null) {
                return false;
            }

            $upgrader = new \Theme_Upgrader($skin);

            $fs_options = apply_filters('upgrader_package_options', array('destination' => WP_PLUGIN_DIR));
            if ($upgrader->fs_connect(array(WP_CONTENT_DIR, $fs_options['destination'])) !== true) {
                $this->set_error('fs_error', "File system error. Invalid file permissions or FTP credentials.");

                return false;
            }

            if ($this->set_download_data() == false) {
                return false;
            }

            $update_themes = get_site_transient('update_themes');

            if (is_object($update_themes)) {
                $old_update_themes = clone $update_themes;
            } else {
                $old_update_themes = $update_themes;
                $update_themes = new \stdClass();
            }

            $theme_object = array(
                'package' => $this->download_data['url']
            );
            $update_themes->response[$this->slug] = $theme_object;

            $GLOBALS['tenweb_update_process'] = true;
            set_site_transient('update_themes', $update_themes, 60 * 60);

            add_filter('http_request_args', array($this, 'add_headers'), 9999, 2);

            $result = $upgrader->upgrade($this->slug);
            $GLOBALS['tenweb_update_process'] = false;

            set_site_transient('update_themes', $old_update_themes, 60 * 60);

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

        function delete()
        {
            // Check filesystem credentials. `delete_theme()` will bail otherwise.
            $url = wp_nonce_url('themes.php?action=delete&stylesheet=' . urlencode($this->slug), 'delete-theme_' . $this->slug);
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

            include_once(ABSPATH . 'wp-admin/includes/theme.php');

            $result = delete_theme($this->slug);

            if (is_wp_error($result)) {
                $this->set_error('failed_to_delete', $result->get_error_message());

                return false;
            } else if (false === $result) {
                $this->set_error('failed_to_delete', 'Theme could not be deleted.');

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

    }
}
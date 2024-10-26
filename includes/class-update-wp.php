<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 6/5/18
 * Time: 4:07 PM
 */

namespace Tenweb_Manager;

include_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';

class UpdateWP extends \Core_Upgrader
{

    public $update_obj;

    /**
     * @return \WP_Error|boolean false or \WP_Error on failure, true on success.
     * */
    public function update()
    {
        include_once ABSPATH . '/wp-admin/includes/update.php';
        include_once ABSPATH . '/wp-admin/includes/file.php';
        include_once ABSPATH . '/wp-admin/includes/template.php';
        include_once ABSPATH . '/wp-admin/includes/misc.php';


        $update = $this->get_core_update_object();
        if (is_wp_error($update)) {
            return $update;
        }

        $allow_relaxed_file_ownership = isset($update->new_files) && !$update->new_files;
        $fs = $this->check_file_system($allow_relaxed_file_ownership);
        if (is_wp_error($fs)) {
            return $fs;
        }

        //$upgrader = new \Core_Upgrader();
        $result = $this->upgrade($update, array(
            'allow_relaxed_file_ownership' => $allow_relaxed_file_ownership
        ));

        if (is_wp_error($result)) {
            Helper::set_error_log('core_update_install_failed', json_encode($result->get_error_messages()));

            return (new \WP_Error('installation_failed.', 'Installation Failed.'));
        }

        $this->update_obj = $update;

        return true;
    }

    public function upgrade_strings()
    {
        parent::upgrade_strings();
        foreach ($this->strings as $i => $string) {
            $this->strings[$i] = '';
        }
    }

    /**
     * @return \stdClass|\WP_Error
     * */
    private function get_core_update_object()
    {
        global $wp_version;

        //get update data if transient deleted
        wp_version_check(array(), true);

        $core_updates = get_core_updates();

        if (empty($core_updates)) {
            Helper::set_error_log('core_update_update_not_available', 'No core updates.');

            return (new \WP_Error('update_not_available', 'Update not available.'));
        }

        $cur_wp_version = preg_replace('/-.*$/', '', $wp_version);

        if (!isset($core_updates[0]->response) || 'latest' == $core_updates[0]->response || 'development' == $core_updates[0]->response || version_compare($core_updates[0]->current, $cur_wp_version, '=')) {
            Helper::set_error_log('core_update_update_not_available', 'No available version.');

            return (new \WP_Error('update_not_available', 'Update not available.'));
        }

        return $core_updates[0];
    }

    /**
     * @return boolean|\WP_Error \WP_Error on failure, true on success.
     * */
    private function check_file_system($allow_relaxed_file_ownership)
    {

        $url = wp_nonce_url('update-core.php?action=do-core-upgrade', 'upgrade-core');

        ob_start();
        $credentials = request_filesystem_credentials($url, '', false, ABSPATH, array('version', 'locale'), $allow_relaxed_file_ownership);
        ob_end_clean();

        global $wp_filesystem;

        if ($credentials === false || !WP_Filesystem($credentials, ABSPATH, $allow_relaxed_file_ownership)) {

            if ($wp_filesystem instanceof \WP_Filesystem_Base && is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code()) {
                $error_msg = esc_html($wp_filesystem->errors->get_error_message());
            } else {
                $error_msg = 'Unable to connect to the filesystem. Please confirm your credentials.';
            }

            Helper::set_error_log('core_update_fs_error', $error_msg);

            return (new \WP_Error('wrong_fs_credentials', 'Wrong filesystem credentials.'));
        }

        if ($wp_filesystem->errors->get_error_code()) {
            Helper::set_error_log('core_update_fs_issues', json_encode($wp_filesystem->errors->get_error_messages()));

            return (new \WP_Error('wrong_fs_credentials', 'Wrong filesystem credentials.'));
        }

        return true;
    }


    public function tenweb_get_version()
    {
        if (isset($this->update_obj->version)) {
            return $this->update_obj->version;
        } else {
            global $wp_version;

            return $wp_version;
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 9/15/17
 * Time: 3:45 PM
 */

namespace Tenweb_Manager;


class ProductState
{


    public $product_id;//@type int
    public $slug;//@type string
    public $title;//@type string
    public $description;//@type string
    public $type;//@type string ['plugin', 'theme','addon']
    public $version;//@type string
    public $installed;//@type int [0,1]
    public $active;//@type int [0,1]
    public $network_active;//@type int [0,1]
    public $is_paid;//@type int [0,1]
    public $screenshot = "";//@type string
    public $tenweb_product;
    public $author = "";//@type string
    public $repo_version;//@type string
    public $parent_theme_name = "";//@type string
    public $theme_errors = array();//@type array

    /**
     * ProductState constructor.
     *
     * @param $product_id
     * @param $slug
     * @param $title
     * @param $type
     * @param $version
     * @param $installed
     * @param $active
     * @param $is_paid
     * @param $tenweb_product
     * @param $network_active
     */
    public function __construct($product_id, $slug, $title, $description, $type, $version, $installed, $active = null, $is_paid = null, $tenweb_product = true, $network_active = null)
    {
        $this->product_id = intval($product_id);
        $this->slug = $slug;
        $this->title = $title;
        $this->description = $description;
        $this->type = $type;
        $this->version = substr($version, 0, 1) == 'v' ? substr($version, 1) : $version;
        $this->installed = intval($installed);
        $this->active = ($active !== null) ? intval($active) : null;
        $this->network_active = ($network_active !== null) ? intval($network_active) : null;
        $this->is_paid = ($is_paid !== null) ? intval($is_paid) : null;
        $this->tenweb_product = $tenweb_product;
    }

    public function set_active($wp_slug)
    {
        if ($this->type == "theme") {

            $current_theme = wp_get_theme();
            $this->active = ($current_theme['Name'] == $this->title);

        } else if ($this->type == "plugin") {
            $this->active = is_plugin_active($wp_slug);

            if (is_multisite()) {
                $this->network_active = is_plugin_active_for_network($wp_slug);
            }
            $this->active = is_plugin_active($wp_slug);
        }

    }

    public function set_is_paid($versions)
    {

        $version_ident = explode('.', $this->version);

        $free_version = substr($versions['free'], 0, 1) == 'v' ? substr($versions['free'], 1) : $versions['free'];
        $free_version_ident = explode('.', $free_version);

        $paid_version = substr($versions['paid'], 0, 1) == 'v' ? substr($versions['paid'], 1) : $versions['paid'];
        $paid_version_ident = explode('.', $paid_version);

        if ($version_ident[0] == $paid_version_ident[0]) { ///  important , keep order, if free and paid versions start with same number
            $this->is_paid = true;
        } else if ($version_ident[0] == $free_version_ident[0]) {
            $this->is_paid = false;
        } else {
            $this->is_paid = null;
        }

    }

    public function set_other_wp_info($wp_slug, $installed_product, $installed_products_wp_info)
    {

        if ($this->type === "theme") {

            $this->author = $installed_product->get("Author");
            if (!empty($installed_products_wp_info->response[$wp_slug])) {
                $this->repo_version = $installed_products_wp_info->response[$wp_slug]['new_version'];
            }

            $parent_theme = $installed_product->parent();
            if ($parent_theme === false) {
                $this->parent_theme_name = "";
            } else {
                $this->parent_theme_name = $installed_product->parent()->get('Name');
            }

            $errors = $installed_product->errors();
            if ($errors === false) {
                $this->theme_errors = array();
            } else {
                $this->theme_errors = $errors->errors;
            }

        } else {

            if (!empty($installed_products_wp_info->response[$wp_slug])) {
                $this->repo_version = $installed_products_wp_info->response[$wp_slug]->new_version;
            } else if (!empty($installed_products_wp_info->no_update[$wp_slug]->new_version)) {//only for plugins
                $this->repo_version = $installed_products_wp_info->no_update[$wp_slug]->new_version;
            }

            $this->author = (!empty($installed_product['AuthorName'])) ? $installed_product['AuthorName'] : "";
        }

        if (empty($this->repo_version)) {
            $this->repo_version = $this->version;
        }

    }

    public function set_tenweb_product($tenweb_product)
    {
        $this->tenweb_product = $tenweb_product;
    }

    public function set_screenshot($url)
    {
        $this->screenshot = $url;
    }

    public function get_info()
    {
        $state = array(
            'product_id'     => $this->product_id,
            'slug'           => $this->slug,
            'title'          => $this->title,
            'description'    => $this->description,
            'version'        => $this->version,
            'installed'      => $this->installed ? 1 : 0,
            'active'         => $this->active ? 1 : 0,
            'network_active' => $this->network_active ? 1 : 0,
            'is_paid'        => $this->is_paid ? 1 : 0,
            'screenshot'     => $this->screenshot,
            'tenweb_product' => $this->tenweb_product ? 1 : 0,
            'author'         => $this->author,
            'repo_version'   => $this->repo_version
        );

        if ($this->type === "theme") {
            $state['parent_theme_name'] = $this->parent_theme_name;
            $state['theme_errors'] = $this->theme_errors;
        } else {

        }

        return $state;
    }

    public function get_wp_info()
    {
        return array(
            'slug'    => $this->slug,
            'title'   => $this->title,
            'version' => $this->version,
            'active'  => $this->active ? 1 : 0
        );
    }

}
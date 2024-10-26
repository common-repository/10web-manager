<?php

namespace Tenweb_Manager {

    use KubAT\PhpSimple\HtmlDomParser;

    class WpAdminPagespeedFixes
    {


        private static $instance;

        /**
         * @var \simplehtmldom_1_5\simple_html_dom
         */
        private $htmlDom;

        /**
         * @var string
         */
        private $content;

        protected function __construct()
        {

            add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueue_wp_admin_pagespeed_fixes_scripts') );

            /**
             * We need this to workaround this issue
             * https://github.com/sunra/php-simple-html-dom-parser/issues/33
             */
            /**
             * @deprecated because of many errors with wordpress
             */
            /*
            define('MAX_FILE_SIZE', 12000000);

            include_once TENWEB_DIR.'/vendor/autoload.php';
            ob_start(array($this, 'postProcessHtml'));
            */
        }


        /**
         * @param $content
         *  return html content
         *
         * @return mixed|string|void
         */
        public function postProcessHtml($content)
        {
            $this->loadHtmlDom($content);

            $this->disablePagespeedModuleForIframes();
            // Add a custom category for panel widget

            return $this->getProcessedHtml();
        }

        private function disablePagespeedModuleForIframes()
        {

            if ($this->isDomLoaded()) {
                foreach ($this->htmlDom->find('iframe') as $iframe) {
                    if (!empty( $iframe->src)) {
                        $iframe->src .= (parse_url($iframe->src, PHP_URL_QUERY) ? '&' : '?') . 'PageSpeed=off';
                    }
                }
            }
        }

        public function enqueue_wp_admin_pagespeed_fixes_scripts()
        {
            wp_register_script(TENWEB_PREFIX . '_scripts_wp-admin-pagespeed-fixes', TENWEB_URL . '/assets/js/wp-admin-pagespeed-fixes.js', array(), TENWEB_VERSION);
            wp_enqueue_script(TENWEB_PREFIX . '_scripts_wp-admin-pagespeed-fixes');
        }

        public static function enable()
        {
            if (self::$instance === null) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        private function loadHtmlDom($content)
        {
            $this->content = $content ;
            $this->htmlDom = HtmlDomParser::str_get_html( $this->content );
        }

        private function getProcessedHtml()
        {
            return $this->isDomLoaded() ? $this->htmlDom->save() : $this->content;
        }

        private function isDomLoaded()
        {
            return !empty($this->htmlDom);
        }


    }
}
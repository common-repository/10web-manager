<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 9/23/17
 * Time: 10:31 PM
 */

namespace Tenweb_Manager {

    interface ProductActions
    {
        public function activate();

        public function deactivate();

        public function update();

        public function delete();

    }

}
<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 10/18/18
 * Time: 3:44 PM
 */

?>
<style>
    #adminmenumain, #wpfooter, #wpcontent *:not(.tenweb_cp_overlay *) {
        display: none !important;
    }
</style>
<div class="tenweb_cp_overlay">
    <div class="tenweb_cp_content">
        <p class="tenweb_cp_text1">Please wait,</p>
        <p class="tenweb_cp_text2">we’re connecting your site.</p>
        <div class="tenweb_cp_spinner_container">
            <img class="tenweb_cp_spinner" class="tenweb_cp_text2" src="<?php echo TENWEB_URL_IMG; ?>/spinner2.svg">
        </div>
    </div>
</div>

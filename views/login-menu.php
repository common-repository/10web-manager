<?php
if (isset($_GET['logged_in']) && $_GET['logged_in'] == '1') {
    echo '<div class="notice notice-error tenweb_manager_notice" style="padding: 10px;">'
        . __("You already have 10Web account, please log in.", TENWEB_LANG)
        . '</div>';
}
?>
<div class="twebman twebman-page twebman-login" id="twebman-login">
    <div class="twebman-messages"><?php //$this->display_messages(); ?></div>
    <div class="twebman-back"><a href="admin.php?page=tenweb_menu">Back</a></div>
    <div class="twebman-login-form loged_out">
        <div class="twebman-login-form-container">
            <div id="twebman-login-form" class="twebman-clear">
                <div class="twebman-login-form-logo">
                </div>
                <p class="sign_in">Log in to 10Web</p>
                <h2>Welcome!</h2>
                <div class="twebman-login-sub" style="">
                    <?php wp_nonce_field('tenweb_login_nonce', 'tenweb_login_nonce'); ?>
                    <div>
                        <div class="styled-input">
                            <label for="tenweb_email">Email Address<span class="required_star">*</span></label>
                            <input type="text" name="tenweb_email" id="tenweb_email" value=""
                                   x-autocompletetype="email">
                            <div class="error_label" id="valid_email">Please enter a valid email.</div>
                        </div>
                    </div>
                    <div>
                        <div class="styled-input">
                            <label for="password">Password<span class="required_star">*</span></label>
                            <input type="password" name="password" id="password" value=""
                                   x-autocompletetype="current-password">
                            <div class="error_label" id="enter_password">Please enter a password.</div>
                            <div class="error_label" id="error_response"></div>
                        </div>
                    </div>
                    <div class="clear">
                        <div class="forgot_password"><a href="<?php echo TENWEB_DASHBOARD; ?>/forgot-password"
                                                        target="_blank">Forgot Password?</a></div>
                        <div class="buttons">
                            <button onclick="tenwebLogin(); return false;" class="twebman-button button-login"
                                    id="button_login">LOG
                                IN<span
                                        class="spinner"></span></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="create_account">
            <p>Dont have an account yet? <a href="<?php echo $registration_link; ?>" target="_blank">Sign up and connect
                    your website now.</a></p>
        </div>
    </div>
</div>
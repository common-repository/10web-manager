<div class="tenweb_overlay"></div>
<div class="tenweb_popup_container">
    <div class="tenweb_header">
        Please let us know why you are deactivating <?php echo \Tenweb_Manager\Helper::get_company_name() ?> Manager. Your answer will help us to provide you support or
        sometimes offer discounts. (Optional):
        <span class="tenweb_popup_close"></span>
    </div>
    <form name="tenweb_deactivate" method="POST">
        <div class="tenweb_popup_content" data-adminemail="<?php echo $admin->data->user_email; ?>">
            <div>
                <input type="radio" value="reason_i_dont_understand_how_to_use" name="tenweb_manager_reasons"
                       id="tenweb_dont_understand"
                       class="tenweb_radio">
                <label for="tenweb_dont_understand">I don't understand how to use this plugin<label>
            </div>
            <div>
                <input type="radio" value="reason_plugin_is_hard_to_use_technical_problems"
                       name="tenweb_manager_reasons"
                       id="tenweb_technical" class="tenweb_radio">
                <label for="tenweb_technical">Technical problems/bugs<label>
            </div>
            <div>
                <input type="radio" value="reason_temporary_deactivation" name="tenweb_manager_reasons"
                       id="tenweb_temporary" class="tenweb_radio">
                <label for="tenweb_temporary">Temporary deactivation<label>
            </div>
            <div class="checkbox_container" style="margin-top: 5px;">
                <input type="checkbox" name="tenweb_checkbox">
                By submitting this form your email and website URL will be sent to 10Web. Click the checkbox if you
                consent to usage of mentioned data by 10Web in accordance with our
                <a target="_blank" href="https://10web.io/privacy-policy/">Privacy Policy</a>.
            </div>
            <div class="tenweb_i_dont_understand tenweb_content">
                <div>
                    <strong>Please describe your issue.</strong>
                </div>
                <br/>
                <textarea name="tenweb_dont_install_textarea" rows="4"></textarea>
                <br/>
                <div class="technical_email">
                    Our support will contact
                    <input type="text" name="tenweb_dont_install_email" class="tenweb_email_field"/>
                    shortly.
                </div>
                <br>
                <input type="button" value="Submit support ticket"
                       class="button button-primary button-primary-disabled">
            </div>
            <div class="tenweb_technical_active tenweb_content">
                <div>
                    <strong>Please describe your issue.</strong>
                </div>
                <br/>
                <textarea name="tenweb_technical_textarea" rows="4"></textarea>
                <br/>
                <div class="technical_email">
                    Our support will contact
                    <input type="text" name="tenweb_technical_email" class="tenweb_email_field"/>
                    shortly.
                </div>
                <br>
                <input type="button" value="Submit support ticket"
                       class="button button-primary button-primary-disabled">
            </div>
            <?php wp_nonce_field('tenweb_manager_deactivate'); ?>
            <input type="hidden" class="tenweb_submit_and_deactivate" name="tenweb_submit_and_deactivate" value="1"/>
        </div>
        <div class="tenweb_button">
            <a href="<?php echo $deactivate_url; ?>" class="button button-secondary">
                Skip and Deactivate
            </a>
            <a href="<?php echo $deactivate_url; ?>"
               class="button button-primary button-primary-disabled button-close tenweb_deactivate_btn"
               style="display: none;">
                Submit and Deactivate
            </a>
        </div>
    </form>
</div>
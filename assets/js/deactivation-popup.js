jQuery(document).ready(function () {

    var radio_checked = false;
    var pp_checkbox = jQuery('.checkbox_container input');

    var overlay = jQuery('.tenweb_overlay');
    var container = jQuery('.tenweb_popup_container');
    var default_email = jQuery('.tenweb_popup_content').data('adminemail');
    var hidden = jQuery('.tenweb_submit_and_deactivate');
    var deactivate_btn = jQuery('.tenweb_deactivate_btn');

    jQuery('#the-list .active[data-slug="10web-manager"] .deactivate a').on('click', function (e) {
        e.preventDefault();

        overlay.show();
        container.show();
        jQuery('.tenweb_email_field').val(default_email);

        return false;
    });

    overlay.on('click', close_popup);
    jQuery('.tenweb_popup_close').on('click', close_popup);

    jQuery('.tenweb_radio').on('change', function () {
        var self = jQuery(this);

        jQuery('.tenweb_content').removeClass('tenweb_active');
        container.removeClass('tenweb_popup_active1');
        container.removeClass('tenweb_popup_active2');

        if (self.attr('id') === "tenweb_technical") {
            jQuery('.tenweb_technical_active').addClass('tenweb_active');
            container.addClass('tenweb_popup_active1');
        } else if (self.attr('id') === "tenweb_dont_understand") {
            jQuery('.tenweb_i_dont_understand').addClass('tenweb_active');
            container.addClass('tenweb_popup_active1');
        } else {
            container.addClass('tenweb_popup_active2');
        }

        jQuery('.checkbox_container').show();
        if (radio_checked === false) {
            deactivate_btn.show();
            radio_checked = true;
        }
    });


    deactivate_btn.on('click', function (e) {
        e.preventDefault();

        if (radio_checked === false) {
            hidden.val(1);
            container.find('form').submit();
        } else if (pp_checkbox.prop("checked") === true) {
            hidden.val(2);
            container.find('form').submit();
        } else {

        }

        return false;
    });

    jQuery('.tenweb_content .button').on('click', function (e) {
        e.preventDefault();

        if (pp_checkbox.prop("checked") === true) {
            hidden.val(3);
            container.find('form').submit();
        }

        return false;
    });

    pp_checkbox.on('change', function () {

        if (pp_checkbox.prop("checked") === true) {
            jQuery('.tenweb_popup_container .button:not(.button-secondary)').removeClass('button-primary-disabled');
        } else {
            jQuery('.tenweb_popup_container .button:not(.button-secondary)').addClass('button-primary-disabled');
        }

    });

    function close_popup() {
        overlay.hide();
        container.hide();
        reset_popup();
    }

    function reset_popup() {
        radio_checked = false;

        jQuery('.tenweb_radio').prop('checked', false);
        pp_checkbox.prop('checked', false);
        jQuery('.checkbox_container').hide();
        jQuery('.tenweb_content').removeClass('tenweb_active');
        container.removeClass('tenweb_popup_active1');
        container.removeClass('tenweb_popup_active2');
        deactivate_btn.removeClass('button-primary-disabled');
        container.find('textarea').val("");
    }
});
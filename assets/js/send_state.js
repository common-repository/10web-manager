var tenweb_data = {
    action: 'check_site_state'
};
jQuery.ajax({
    type: "POST",
    url: tenweb_state.ajaxurl,
    data: tenweb_data,
    success: function (response) {

    },
    error: function (error) {
        console.log(error);
    }
});
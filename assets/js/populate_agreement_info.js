var tenweb_data = {
    action: 'populate_agreement_info'
};
jQuery.ajax({
    type: "POST",
    url: tenweb_populate_agreement_info.ajaxurl,
    data: tenweb_data,
    success: function (response) {
    },
    error: function (error) {
        console.log(error);
    }
});
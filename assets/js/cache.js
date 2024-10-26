function tenwebCachePurge() {

    var data = {};
    data.action = 'tenweb_cache_purge_all';

    jQuery("#error_response").hide();
    jQuery.ajax({
        type: "POST",
        //  dataType: 'json',
        url: tenweb.ajaxurl,
        data: data,
        success: function (response) {
            var response = JSON.parse(response);
            if (typeof response.error != "undefined") {

                jQuery('#tenweb_cache_message').removeClass('hidden').addClass('error').html('<p>' + response.error + '</p>')
            } else {
                jQuery('#tenweb_cache_message').removeClass('hidden').addClass('success').html('<p>' + response.message + '</p>')
            }
        },
        failure: function (errorMsg) {
            console.log('Failure' + errorMsg);
        },
        error: function (error) {
            console.log(error);
        }
    });
}

/**
 * Not supplying a value will remove the parameter, supplying one will add/update the parameter.
 * If no URL is supplied, it will be grabbed from window.location
 * @param key
 * @param value
 * @param url
 * @returns {string|*}
 * @constructor
 */

function UpdateQueryString(key, value, url) {
    if (!url) url = window.location.href;
    var re = new RegExp("([?&])" + key + "=.*?(&|#|$)(.*)", "gi"),
        hash;

    if (re.test(url)) {
        if (typeof value !== 'undefined' && value !== null) {
            return url.replace(re, '$1' + key + "=" + value + '$2$3');
        }
        else {
            hash = url.split('#');
            url = hash[0].replace(re, '$1$3').replace(/(&|\?)$/, '');
            if (typeof hash[1] !== 'undefined' && hash[1] !== null) {
                url += '#' + hash[1];
            }
            return url;
        }
    }
    else {
        if (typeof value !== 'undefined' && value !== null) {
            var separator = url.indexOf('?') !== -1 ? '&' : '?';
            hash = url.split('#');
            url = hash[0] + separator + key + '=' + value;
            if (typeof hash[1] !== 'undefined' && hash[1] !== null) {
                url += '#' + hash[1];
            }
            return url;
        }
        else {
            return url;
        }
    }
}

/**
 * Add &PageSpeed=off query parameter to Elementor editor iframe
 */
jQuery(window).on('elementor:init-components', function () {
    console.log('wp-admin-pagespeed-fixes.js'); // ok
    elementor.config.initial_document.urls.preview = UpdateQueryString('PageSpeed', 'off',elementor.config.initial_document.urls.preview )
});
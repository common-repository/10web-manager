<?php

$real_url_parsed = parse_url(site_url());

if (isset($_SERVER['HTTP_HOST'])
    && $_SERVER['HTTP_HOST'] !== $real_url_parsed['host']
    && strtolower($_SERVER['REQUEST_METHOD']) === 'get'
) {

    $redirect_url = $real_url_parsed['scheme'] . '://' . $real_url_parsed['host'] . $_SERVER['REQUEST_URI'];
    header('Location: ' . $redirect_url, true, 301);

}
<?php
if (isset($_GET['logged_in']) && $_GET['logged_in'] == '1') {
    echo '<div class="notice notice-error tenweb_manager_notice" style="padding: 10px;">'
        . __("You already have ".\Tenweb_Manager\Helper::get_company_name()." account, please log in.", TENWEB_LANG)
        . '</div>';
}
?>
<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800&display=swap" rel="stylesheet">

<div id="tenweb_cache_message" class="updated hidden is-dismissible"></div>
<div class="twebman twebman-page twebman-login" id="twebman-login">
    <div class="tenweb-cache-wrap">
        <div class="tenweb-cache-row">
            <div class="tenweb-cache-text">
                <div class="tenweb-wrap-text-title"><?php echo \Tenweb_Manager\Helper::get_company_name() ?> Cache</div>
                <div class="tenweb-wrap-text-desc">
                    <?php echo \Tenweb_Manager\Helper::get_cache_text(); ?>

                    <div class="tenweb-cache-buttons">
                        <a href="<?php echo $tenweb_hosting_tools_page ?>" target="_blank"
                           class="tenweb-cache-manage-button">manage
                            cache</a>
                        <a onclick="tenwebCachePurge(); " class="tenweb-cache-clear-button">clear cache</a>
                    </div>
                </div>
            </div>
        </div>
        <div id="tenweb-exclude-page-cache-row" class="tenweb-cache-row loading">
            <img class="tenweb_cp_spinner" class="tenweb_cp_text2" src="<?php echo TENWEB_URL_IMG; ?>/spinner2.svg">
            <label for="exclude_cache_input">Specify URLs you don't want to cache at all
                <span>
                    <div class="tenweb-exclude-page-cache-row-info">Supports regular expressions</div>
                </span>
            </label>
            <div class="tenweb_cache_exclude__input">
                <input name="tenweb_cache_exclude" placeholder="Enter the path" type="text" class="tenweb-input"
                       id="tenweb_cache_exclude_input">
                <button class="tenweb-cache-buttons" id="tenweb_cache_exclude_button">Save</button>
            </div>
        </div>
    </div>
</div>
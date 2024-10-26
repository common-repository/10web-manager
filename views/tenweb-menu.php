<?php
$services = array(
    "hosting"      => array(
        "title" => "Managed hosting on<br> Google Cloud",
        "link"  => "https://10web.io/cloud-hosting/",
    ),
    "builder"      => array(
        "title" => "Website builder based on Elementor",
        "link"  => "https://10web.io/wordpress-website-builder/",
    ),
    "templates"    => array(
        "title" => "Dozens of beautiful<br> templates",
        "link"  => "",
    ),
    "plugins"      => array(
        "title" => "50+ Premium<br> plugins",
        "link"  => "https://10web.io/plugins/",
    ),
    "backup"       => array(
        "title" => "Backup<br> Service",
        "link"  => "https://10web.io/services/backup/",
    ),
    "security"     => array(
        "title" => "Security<br> Service",
        "link"  => "https://10web.io/services/security/",
    ),
    "speed"        => array(
        "title" => "Speed<br> Optimization",
        "link"  => "https://10web.io/image-optimization-service",
    ),
    "seo"          => array(
        "title" => "SEO<br> Service",
        "link"  => "https://10web.io/services/seo/",
    ),
    "analytics"    => array(
        "title" => "Analytics<br> & Reports",
        "link"  => "https://10web.io/plugins/wordpress-google-analytics/",
    ),
    "support_comp" => array(
        "title" => "Technical<br> Support",
        "link"  => "https://10web.io/contact-us/",
    )
);
?>
<div id="tenweb_menu" class="about_tenweb">
    <!--Header-->
    <div id="manager_header" class="content_section">

        <div id="manager_header_bg">
            <!--<div id="manager_header_animation" class="clear animate-blocks">
                <div id="header_animation1" class="animate-block bottom floating-img"
                     data-parallax='["y": -160, "smoothness": 0]'></div>
                <div id="header_animation2" class="animate-block top floating-img"
                     data-parallax='["y": 160, "smoothness": 0]'></div>
                <div id="header_animation3" class="animate-block bottom floating-img"
                     data-parallax='["y": -160, "smoothness": 0]'></div>
                <div id="header_animation4" class="animate-block top floating-img"
                     data-parallax='["y": 160, "smoothness": 0]'></div>
            </div>-->
            <div class="container">
                <div id="tenweb_menu_header" class="clear">
                    <div id="tenweb_menu_logo"><a href="https://10web.io/" target="_blank"></a></div>
                </div>
                <h2 class="section-title animate-words">
                    <span class="word-container word-container-sub w3"><span>Connect your website to 10Web</span></span>
                </h2>
                <h1 class="section-title animate-words">
                    <span class="word-container w1"><span>Site management</span></span>
                    <span class="word-container w2"><span>from one dashboard</span></span>
                </h1>

                <div id="manager_header_buttons">
                    <a href="<?php echo $registration_link; ?>" class="button orange button_content animate_button">
                        CONNECT</a>
                    <!--<span class="manager_watch_video animate_button" id="watch_video"
                          data-id="ur3RMzLHhGA">Watch video</span>-->
                </div>

                <div id="manager_header_move">
                    <p>Move your website to 10Web Managed Hosting<br/> and automatically get 90+ PageSpeed Score</p>
                </div>
                <!--<div id="video_container">
                    <div class="close_embed mobile twtf twtf-close"></div>
                    <div>
                        <div class="close_embed screen twtf twtf-close"></div>
                        <div class="iframe-container">
                            <div id="iframe-container">
                            </div>
                        </div>
                    </div>
                </div>-->
            </div>
        </div>
    </div>
    <!--Section 3-->
    <!--<div id="manager_section_3" class="content_section">
        <div class="container">
            <h2 class="section-title">10Web Offers All Components For a Great Website</h2>
            <div class="clear components">
                <?php /*$i = 1;
                foreach ($services as $key => $service) : */?>
                    <div class="<?php /*echo $key; */?>">
                        <span class="icon"></span>
                        <div class="number"><?php /*echo ($i != 10 ? "0" : "") . $i; */?></div>
                        <h3><?php /*echo $service["title"]; */?>
                            <?php /*if ($key == "templates") : */?>
                                <br><span class="soon">SOON</span>
                            <?php /*endif; */?>
                        </h3>
                    </div>
                    <?php /*$i++; */?>
                <?php /*endforeach */?>
            </div>
        </div>
    </div>-->
    <!--Section 4-->
    <!--<div id="manager_section_4" class="content_section">
        <div class="container">
            <h2 class="section-title">Why 10Web?</h2>
            <div class="clear tenweb_comp">
                <div class="clear">
                    <div class="image_cont"><img class="vertical-middle "
                                                 src="<?php /*echo TENWEB_URL_IMG . '/platform.svg' */?>"></div>
                    <div class="content">
                        <h3>The Only All-in-One Platform</h3>
                        <p>You can build the website you have in mind using just our products: hosting, website builder and plugins.</p>
                    </div>
                </div>
                <div class="clear">
                    <div class="image_cont"><img class="vertical-middle "
                                                 src="<?php /*echo TENWEB_URL_IMG . '/building.svg' */?>"></div>
                    <div class="content">
                        <h3>Fast Website Building</h3>
                        <p>Choose from dozens of templates and build your website up to 10x faster. Customize it using drag & drop website builder with premium features.</p>
                    </div>
                </div>
                <div class="clear">
                    <div class="image_cont"><img class="vertical-middle "
                                                 src="<?php /*echo TENWEB_URL_IMG . '/hosting.svg' */?>"></div>
                    <div class="content">
                        <h3>Managed WordPress Hosting</h3>
                        <p>10Web hosting powered by Google Cloud is fast and secure. We take care of your website, letting you focus on your business.</p>
                    </div>
                </div>
                <div class="clear">
                    <div class="image_cont"><img class="vertical-middle "
                                                 src="<?php /*echo TENWEB_URL_IMG . '/migration.svg' */?>"></div>
                    <div class="content">
                        <h3>Free Automatic Migration</h3>
                        <p>Automatically move your website to 10Web’s managed hosting with a single click and boost its speed by up to 5x.</p>
                    </div>
                </div>
                <div class="clear">
                    <div class="image_cont"><img class="vertical-middle "
                                                 src="<?php /*echo TENWEB_URL_IMG . '/plugins.svg' */?>"></div>
                    <div class="content">
                        <h3>50+ Premium Plugins</h3>
                        <p>Get all essential premium plugins : Form Maker, Photo Gallery, Event Calendar, Slider, Google Maps and others.</p>
                    </div>
                </div>
                <div class="clear">
                    <div class="image_cont"><img class="vertical-middle "
                                                 src="<?php /*echo TENWEB_URL_IMG . '/services.svg' */?>"></div>
                    <div class="content">
                        <h3>All must-have services</h3>
                        <p>Use backup, security, seo,image optimization and performance services to make your website even better.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>-->
    <!--Section 5-->
    <!--<div id="manager_section_5" class="content_section customer-care">
        <div id="section_5_bg">
            <div class="container">
                <span class="customer-care-icon" data-src="<?php /*echo TENWEB_URL_IMG . '/w_care.svg'; */?>"></span>
                <h2 class="plugin-section-header section-title">10Web Care Means We Are Here for You</h2>
                <div class="customer-care-items clear">
                    <div class="customer-care-item">
                        <h3 class="sub-title">Fast response time</h3>
                        <p class="sub-description">You’ll never have to wait more than <b>5 minutes</b>.</p>
                    </div>
                    <div class="customer-care-item">
                        <h3 class="sub-title">Quick issue resolution</h3>
                        <p class="sub-description">Resolving an issue takes <b>24 hours max</b>.</p>
                    </div>
                    <div class="customer-care-item">
                        <h3 class="sub-title">Ask any question anytime</h3>
                        <p class="sub-description">We’re ready to take on <b>any WordPress question</b>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>-->

    <!--Section 9-->
    <!--<div id="manager_section_9" class="content_section ">
        <div class="container">
            <h2 class="plugin-section-header section-title">Platform For Building, Hosting, and Managing WordPress Websites</h2>
            <div>
                <a href="<?php /*echo $registration_link; */?>" class="button orange button_content animate_button">
                    CONNECT</a>
            </div>
        </div>
    </div>-->
</div>

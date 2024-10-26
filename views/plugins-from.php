<?php
if ($pugin_slug == 'contact-form-maker' || $pugin_slug == 'form-maker' || $pugin_slug == 'photo-gallery') {
    $pugin_view = "performance";
} else if ($pugin_slug == 'wd-instagram-feed' || $pugin_slug == 'wd-facebook-feed' || $pugin_slug == 'slider-wd' || $pugin_slug == 'post-slider-wd') {
    $pugin_view = "image-optimizer";
}
$plugins = array(
    "image-optimizer" => array(
        "title"        => "Optimize All Your Website Images in one click",
        "desc"         => "Accelerate Your Website 3 Times",
        "questions"    => array(
            "title"    => "Why Do You Need the Image Optimizer?",
            "question" => array(
                "Do your website images load too slowly?",
                "Does one by one image optimization take too long?",
                "Is your website’s Google ranking low?"
            )
        ),
        "features"     => array(
            array(
                "title" => "Media Library Optimization",
                "desc"  => "Optimize all images in your media library automatically.",
                "img"   => "/plugins_from/image-opt/f2.svg"
            ),
            array(
                "title" => "Photo Gallery Optimization",
                "desc"  => "Optimize all your gallery images in a click.",
                "img"   => "/plugins_from/image-opt/f1.svg"
            ),
            array(
                "title" => "Bulk Optimization",
                "desc"  => "Optimize all your images in a bulk.",
                "img"   => "/plugins_from/image-opt/f3.svg"
            ),
            array(
                "title" => "Compression Modes",
                "desc"  => "Choose between conservative and balanced compression modes.",
                "img"   => "/plugins_from/image-opt/f4.svg"
            ),
            array(
                "title" => "Media Type Conversion",
                "desc"  => "Perform jpg <-> png, gif <-> png, jpg <-> webP conversions.",
                "img"   => "/plugins_from/image-opt/f5.svg"
            ),
            array(
                "title" => "Reports",
                "desc"  => "Check your compression results and statistics.",
                "img"   => "/plugins_from/image-opt/f6.svg"
            )
        ),
        "how_it_works" => array(
            "title" => "How it Works?",
            "steps" => array(
                array(
                    "title" => "Sign Up Free",
                    "img"   => "/plugins_from/image-opt/step1.svg"
                ),
                array(
                    "title" => "Optimize all your images in a click",
                    "img"   => "/plugins_from/image-opt/step2.svg"
                )
            )
        ),
        "video"        => "-TXufIwlWjQ"
    ),
    "performance"     => array(
        "title"        => "Free WordPress speed test and Image optimizer for your website",
        "desc"         => "Analyze and Optimize Your Website Speed",
        "questions"    => array(
            "title"    => "Is your slow website hurting your business?",
            "question" => array(
                "Lacking a proper way of testing your website's speed?",
                "Having trouble of optimizing your website's speed?",
                "Are you looking for ways to automate image optimization?"
            )
        ),
        "features"     => array(
            array(
                "title" => "Performance Check",
                "desc"  => "Check your site performance with just one click.",
                "img"   => "/plugins_from/performance/f1.svg"
            ),
            array(
                "title" => "Performance Grade",
                "desc"  => "A letter(A) grading system that shows how website speed ranks.",
                "img"   => "/plugins_from/performance/f2.svg"
            ),
            array(
                "title" => "Load Time",
                "desc"  => "See how long it takes for each page to load.",
                "img"   => "/plugins_from/performance/f3.svg"
            ),
            array(
                "title" => "Recommendations",
                "desc"  => "Receive tips and suggestions to optimize your website’s speed.",
                "img"   => "/plugins_from/performance/f4.svg"
            ),
            array(
                "title" => "Bulk Optimization",
                "desc"  => "Optimize all your images in bulk.",
                "img"   => "/plugins_from/performance/f5.svg",
            ),
            array(
                "title" => "Automatic Image Optimization",
                "desc"  => "Automatically optimize images uploaded to your website.",
                "img"   => "/plugins_from/performance/f6.svg",
            ),
            array(
                "title" => "Scheduled Checks",
                "desc"  => "Schedule daily, weekly or monthly automatic performance checks.",
                "img"   => "/plugins_from/performance/f7.svg",
            )
        ),
        "how_it_works" => array(
            "title" => "Speed Up Your Website Absolutely For Free",
            "steps" => array(
                array(
                    "title" => "Sign Up On 10Web",
                    "img"   => "/plugins_from/performance/step1.svg"
                ),
                array(
                    "title" => "Check & Optimize Website Speed",
                    "img"   => "/plugins_from/performance/step2.svg"
                )
            )
        ),
        "video"        => "EKamqLhuYJs"
    )
);

?>
<div id="tenweb_menu" class="plugins-from <?php echo $pugin_view; ?>">
    <div id="tenweb_menu_content">
        <div id="tenweb_menu_header" class="clear">
            <div id="tenweb_menu_logo"><a href="https://10web.io/" target="_blank"></a></div>
        </div>
        <h2><?php echo $plugins[$pugin_view]["title"]; ?></h2>
        <p><?php echo $plugins[$pugin_view]["desc"]; ?></p>
        <a href="<?php echo $registration_link; ?>" id="tenweb_sign_up">SIGN UP FREE</a>
        <span class="manager_watch_video animate_button" data-id="<?php echo $plugins[$pugin_view]["video"]; ?>">How it Works?</span>
        <?php if ($pugin_view == "image-optimizer") : ?>
            <div id="image_optimizer">
                <div id="image_optimizer_content">
                    <div id="horizon-original" style="clip: rect(0px, 398px, 405px, 0px);">
                        <div id="label-original" class="image-label">Original</div>
                        <img src="<?php echo TENWEB_URL_IMG . "/plugins_from/image-opt/original.png"; ?>">
                    </div>
                    <div id="horizon-optimized">
                        <div id="label-optimized" class="image-label">Optimized</div>
                        <img src="<?php echo TENWEB_URL_IMG . "/plugins_from/image-opt/optimized.png"; ?>">
                    </div>
                    <div id="separator" style="left: 50%;"><span class="left-arr"></span><span class="right-arr"></span>
                    </div>
                </div>
            </div>
            <div id="image_optimizer_info" class="clear">
                <div class="left"><p>Original Size: <b>432KB</b></p></div>
                <div class="right"><p>Optimized Size: <b>135KB (68% Reduction)</b></p></div>
            </div>
            <div id="image_optimized_number">
                <div id="number">
                    <img src="<?php echo TENWEB_URL_IMG . "/plugins_from/image-opt/numbers.png"; ?>">
                </div>
                <p>Images Optimized</p>
            </div>
        <?php elseif ($pugin_view == "performance") : ?>
            <div class="header-tab-content">
                <img class="img_url_screen"
                     src="<?php echo TENWEB_URL_IMG . '/plugins_from/performance/performance_screen.png'; ?>">
                <img class="img_url_tablet"
                     src="<?php echo TENWEB_URL_IMG . '/plugins_from/performance/performance_screen.png'; ?>">
                <img class="img_url_mobile"
                     src="<?php echo TENWEB_URL_IMG . '/plugins_from/performance/performance_mobile.png'; ?>">
            </div>
        <?php endif; ?>
    </div>
    <div id="tenweb_manager_content">
        <div class="container">
            <div id="optimizer_info_content">
                <h3><?php echo $plugins[$pugin_view]["questions"]["title"]; ?></h3>
                <div class="questions_content">
                    <?php foreach ($plugins[$pugin_view]["questions"]["question"] as $question) : ?>
                        <div class="question"><p><?php echo $question; ?></p></div>
                    <?php endforeach; ?>
                </div>
                <a href="<?php echo $registration_link; ?>" id="tenweb_sign_up">SIGN UP FREE</a>
                <span class="cancel_anytime">It's free and always will be</span>
            </div>
        </div>
        <div id="optimizer_features">
            <div class="container">
                <div class="features-container clear">
                    <?php foreach ($plugins[$pugin_view]["features"] as $feature) : ?>
                        <div class="feature clear">
                            <div class="feature-image-cont"
                                 style="background-image: url(<?php echo TENWEB_URL_IMG . $feature["img"]; ?>);"></div>
                            <div class="feature-content">
                                <h3 class="sub-title"><?php echo $feature["title"]; ?><?php echo (isset($feature['soon'])) ? '<span class="coming_soon">SOON</span>' : ''; ?></h3>
                                <p class="sub-description"><?php echo $feature["desc"]; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div id="steps">
            <div class="container">
                <h3 class="section-title"><?php echo $plugins[$pugin_view]["how_it_works"]["title"]; ?></h3>
                <div class="steps_content clear  steps_3">
                    <?php foreach ($plugins[$pugin_view]["how_it_works"]["steps"] as $value) : ?>
                        <div class="step" style="background-image: url(<?php echo TENWEB_URL_IMG . $value["img"]; ?>);">
                            <h4 class="step_icon"><?php echo $value["title"]; ?></h4>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?php echo $registration_link; ?>" id="tenweb_sign_up">SIGN UP FREE</a><br>
                <span class="manager_watch_video animate_button"
                      data-id="<?php echo $plugins[$pugin_view]["video"]; ?>">How it Works?</span>
            </div>
        </div>
    </div>
</div>
<div id="video_container">
    <div class="close_embed mobile twtf twtf-close"></div>
    <div>
        <div class="close_embed screen twtf twtf-close"></div>
        <div class="iframe-container">
            <div id="iframe-container">
            </div>
        </div>
    </div>
</div>
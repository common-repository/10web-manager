<?php

require_once 'ip_banning.php';

class TWLoginProtection
{

    private $ip = null;
    private $banning = null;

    private static $IP_TYPE_SINGLE = 'Single';
    private static $IP_TYPE_WILDCARD = 'Wildcard';
    private static $IP_TYPE_MASK = 'Mask';
    private static $IP_TYPE_CIDR = 'CIDR';
    private static $IP_TYPE_SECTION = 'Section';


    public function __construct()
    {
        $this->ip = $this->getClientIp();
        $this->banning = new TWIPBanning($this->ip);

        //lockouts
        add_action("init", array($this, "checkBlocked"));
        add_action('wp_login_failed', array($this, "loginFailed"));

        add_filter('login_errors', array($this, 'checkAttemptedLogin'));

    }

    public function checkBlocked()
    {
        if (is_admin() || $GLOBALS['pagenow'] === 'wp-login.php') {
            if ($this->banning->checkIfIpIsBlocked()) {
                echo TW_LOCKOUT_MESSAGE;
                die();
            }
        }
    }

    public function loginFailed($username)
    {
        global $wp;
        $ip_temp_banning = $this->banning->addTemp();

        if ($ip_temp_banning["count"] >= TW_FAILED_LOGIN_ATTEMPTS_COUNT) {
            $this->banning->removeTemp();
            $this->banning->add();
            wp_redirect(wp_login_url(site_url($wp->request)));
        }

        return false;
    }

    public function checkAttemptedLogin($error)
    {
        $ip_temp_banning = $this->banning->getTempBlocked();
        if (isset($ip_temp_banning)) {
            $rest_failed_attempts_count = TW_FAILED_LOGIN_ATTEMPTS_COUNT - $ip_temp_banning["count"];
            if(is_wp_error($error)){
                return $error;
            }
            $error = $error . "<br><strong>Failed attempts count:</strong> " . $rest_failed_attempts_count;
        }

        return $error;
    }


    private function getClientIp()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ip = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = 'UNKNOWN';

        return $ip;
    }

    private function checkIp($ip, $allowed_ips)
    {
        foreach ($allowed_ips as $allowed_ip) {
            $type = $this->judgeIpType($allowed_ip);
            $method_name = 'subChecker' . $type;
            $sub_rst = $this->$method_name($allowed_ip, $ip);

            if ($sub_rst) {
                return true;
            }
        }

        return false;
    }

    private function judgeIpType($ip)
    {
        if (strpos($ip, '*')) {
            return self::$IP_TYPE_WILDCARD;
        }

        if (strpos($ip, '/')) {
            $tmp = explode('/', $ip);
            if (strpos($tmp[1], '.')) {
                return self::$IP_TYPE_MASK;
            } else {
                return self::$IP_TYPE_CIDR;
            }
        }

        if (strpos($ip, '-')) {
            return self::$IP_TYPE_SECTION;
        }

        return self::$IP_TYPE_SINGLE;
    }
}

$login_protection = new TWLoginProtection();




<?php

class TWIPBanning
{
    private $ip = null;
    private $blocked = array();
    private $temp_blocked = array();
    private $temp_option_name = null;
    private $option_name = null;
    public function __construct($ip = null)
    {

        $this->ip = $ip;
        $this->temp_option_name = "tw_temp_banned_ip_".$ip;
        $this->option_name = "tw_banned_ip_".$ip;
        $this->blocked = $this->getBannedIp();
        $this->temp_blocked = $this->getBannedIp(true);
    }

    public function getBlockedList()
    {
        return $this->blockedlist;
    }

    public function getTempBlocked()
    {
        return $this->temp_blocked;
    }

    public function checkIfIpIsBlocked()
    {
        if (is_array($this->blocked) && isset($this->blocked["created_at"])) {
            $diff = (strtotime(date("Y-m-d H:i:s")) - $this->blocked["created_at"]);
            if ($diff < TW_LOCKOUT_TIME) {
                return true;
            } else {
                $this->remove();
            }
        }

        return false;

    }

    public function checkIfIpIsTempBlocked()
    {
        if (is_array($this->temp_blocked) && isset($this->temp_blocked["created_at"]) ) {
            $diff = (strtotime(date("Y-m-d H:i:s")) - $this->temp_blocked["created_at"]);
            if ($diff < TW_FAILED_ATTEMPTS_TIME) {
                return true;
            } else {
                $this->removeTemp();
            }
        }

        return false;
    }


    public function add()
    {
        $data = array(
            "ip"         => $this->ip,
            "created_at" => time(),
        );
        $this->updateBannedIp($data);
    }

    public function remove()
    {
        delete_site_transient($this->option_name);
    }

    public function addTemp()
    {
        $count = 0;
        $created_at = time();
        if ($this->checkIfIpIsTempBlocked()) {
            $count = $this->temp_blocked["count"];
            $created_at = $this->temp_blocked["created_at"];
        }
        $this->temp_blocked = array(
            "ip"         => $this->ip,
            "count"      => ($count + 1),
            "created_at" => $created_at,
        );
        $this->updateBannedIp($this->temp_blocked, true);

        return $this->temp_blocked;
    }

    public function removeTemp()
    {
        delete_site_transient($this->temp_option_name);
    }

    public function setIp($ip)
    {
        $this->ip = $ip;
        $this->temp_option_name = "tw_temp_banned_ip_".$ip;
        $this->option_name = "tw_banned_ip_".$ip;
    }

    private function getBannedIp($temp = false)
    {
        $option_name = $this->option_name;
        if ($temp) {
            $option_name = $this->temp_option_name;
        }
        return get_site_transient($option_name);
    }

    private function updateBannedIp($data, $temp = false)
    {
        if ($temp) {
            set_site_transient($this->temp_option_name, $data, TW_FAILED_ATTEMPTS_TIME);
        }else{
            set_site_transient($this->option_name, $data, TW_LOCKOUT_TIME);
        }
    }
}

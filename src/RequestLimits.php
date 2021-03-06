<?php
require_once 'RedisConnection.php';

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RedisConnection
 *
 * @author lapdx
 */
class RequestLimits extends RedisConnection {

    private $ip = null;
    private $userAgent;
    private $refer;
    private $maxRequest = 25;
    private $limitTime = 300; // 5*60 second
    private $safeTime = 2; // second
    private $isCheckRefer = true;

    public function __construct() {
        
    }

    public function getIp() {
        $this->ip = $_SERVER["REMOTE_ADDR"];
        return $this->ip;
    }

    public function getUserAgent() {
        $this->userAgent = isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'';
        return $this->userAgent;
    }

    public function setLimitTime($time) {
        $this->limitTime = $time;
    }

    public function setSafeTime($time) {
        $this->safeTime = $time;
    }

    public function setMaxRequest($number) {
        $this->maxRequest = $number;
    }

    public function setCheckRefer($isRefer) {
        $this->isCheckRefer = $isRefer;
    }

    public function getRefer() {
        $this->refer = isset($_SERVER["HTTP_REFERER"])?$_SERVER["HTTP_REFERER"]:'';
        return $this->refer;
    }

    public function check() {
        $retVal = false;
        $oldTimes = $this->getClient()->lrange($this->getIp(),0,-1);
        if (!empty($oldTimes)) {
            foreach ($oldTimes as $time) {
                if (strtotime("now") - $time >= 5) {
                    $this->getClient()->lRemove($this->getIp(),$time,0);
                }
            }
        }
        $size = $this->getClient()->lLen($this->getIp());
        if ($this->isCheckRefer) {
            $this->getRefer();
            $this->getUserAgent();
            if (empty($this->refer) || empty($this->userAgent)) {
                $this->getClient()->lPush("block:" . $this->getIp(), "emptyRefer:" . strtotime("now") . ":" . date("Y-m-d h:i:s"));
                return false;
            }
        }
        $this->getClient()->lPush($this->getIp(), strtotime("now"));
        if ($size == 0) {
            $this->getClient()->setTimeout($this->getIp(), $this->limitTime);
            $retVal = true;
        } else if ($size > $this->maxRequest) {
            $this->getClient()->lPush("block:" . $this->getIp(), "exceedMaxRequest:" . strtotime("now") . ":" . date("Y-m-d h:i:s"));
            $retVal = false;
        } else if ($size > 0 && $size <= $this->maxRequest) {
            $values = $this->getClient()->lRange($this->getIp(), 0, 1);
            $lastTime = intval($values[1]);
            $now = intval($values[0]);
            if (($now - $lastTime) < $this->safeTime) {
                $this->getClient()->lPush("block:" . $this->getIp(), "exceedSafeTime:" . strtotime("now") . ":" . date("Y-m-d h:i:s"));
                $retVal = false;
            } else {
                $retVal = true;
            }
        }
        return $retVal;
    }

}

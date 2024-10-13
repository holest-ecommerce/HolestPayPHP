<?php
/**
* HolestPay PHP lib
* -------------------------------------------------
* File Version: 1.0.1
**/
namespace holestpay;

use Throwable;

if(!defined('HOLESTPAYLIB')){
    die("Direct access to this file is not allowed");
}

trait HolestPayI8N{

    public static function __($str){
        return $str;
    }

    public function translateKeys($data){
		if(!$data){
			$data = array("---" => HolestPayI8N::__("Failed: no valid payment info response"));
		}
		$tdata = array();
		if($data){
			foreach($data as $key => $val){
				if(is_object($val) || is_array($val)){
					$tdata[HolestPayI8N::__($key)] = $this->translateKeys($val);
				}else{
					$tdata[HolestPayI8N::__($key)] = $val;
				}
			}
		}
		return $tdata;
	}
}
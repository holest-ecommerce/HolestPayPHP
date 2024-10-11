<?php
/**
* HolestPay PHP lib
* -------------------------------------------------
* File Version: 1.0.1
* Date: October, 2024
**/
//IF no argument is passed to init method
if(!defined('HOLESTPAYLIB_DEFAULT_CONFIG_SOURCE')){
	define('HOLESTPAYLIB_DEFAULT_CONFIG_SOURCE',"holestpay.ini");
}

if(!defined('HPAY_PRODUCTION_URL'))
	define("HPAY_PRODUCTION_URL","https://pay.holest.com");

if(!defined('HPAY_SANDBOX_URL'))
	define("HPAY_SANDBOX_URL","https://sandbox.pay.holest.com");

if(!defined('HPAY_LIB_ROOT'))
	define("HPAY_LIB_ROOT",  rtrim(rtrim(__DIR__,"/"),"\\"));
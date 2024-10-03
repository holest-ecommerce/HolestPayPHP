<?php
/**
* HolestPay PHP lib
* -------------------------------------------------
* Version: 1.0.1
* File Version: 1.0.1
* Date: October, 2024
* Author: HOLEST E-COMMERCE DOO
* Author URL: https://ecommerce.holest.com/ 
* PHP 7,8,9
**/
namespace HolestPay;

if(!defined('HOLESTPAYLIB')){
	define('HOLESTPAYLIB',__FILE__);
    require_once(__DIR__ . "/class/HolestPayCore.php");

    public class HolestPay{
        private static $_instance;
        private static $_config;
        
        //TRAITS///////////////////////////////////////
        use HolestPayCore;
        ///////////////////////////////////////////////
        
        /**
         * Constructor / private - SINGLETON PATTERN
         * 
         */
        private function __construct(){
            //
        }

        /**
         * Initializes the library from confg. This is the library config, and not the SITE/POS HPay config
         * 
         * @param string $config if null|empty then define HOLESTPAYLIB_CONFIG_SOURCE be used which is by default holestpay.ini from this folder. You can pass file location (with ini, or json context), JSON string, ASOC Array or Object with config
         */
        public static function init($config = null){
            
        }
        
        /**
         * Gets the running SINGLETON instance
         */
        public static function instance(){
            if(HolestPay::$_instance){

            }

            return HolestPay::$_instance;
        }


    }
}


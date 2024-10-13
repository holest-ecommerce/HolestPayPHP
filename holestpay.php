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
namespace holestpay;

if(!defined('HOLESTPAYLIB')){
	define('HOLESTPAYLIB',__FILE__);
    
    require_once(__DIR__ . "/class/HolestPayCore.php");
    require_once(__DIR__ . "/class/HolestPayConversion.php");
    require_once(__DIR__ . "/class/HolestPayNet.php");
    require_once(__DIR__ . "/class/HolestPaySign.php");
    require_once(__DIR__ . "/class/HolestPayUI.php");
    require_once(__DIR__ . "/class/HolestPayI8N.php");
    require_once(__DIR__ . "/class/HolestPayMethods.php");
    
    class HolestPayLib{
        public static $PAY_STATUSES = array("SUCCESS","PAID", "AWAITING", "REFUNDED", "PARTIALLY-REFUNDED","VOID", "RESERVED", "EXPIRED", "OBLIGATED", "REFUSED");
        public static $SHIPPING_PACKET_STATUSES = array("PREPARING", "READY", "SUBMITTED", "DELIVERY", "DELIVERED", "ERROR", "RESOLVING", "FAILED", "CANCELED");

        private static $_instance = null;
        private static $_config = null;

        private static $_data_provider = null;
        private static $_log_provider = null;

        private $instance_uid = null;
        private static $active_instance_uid = null;
        
        //TRAITS///////////////////////////////////////
        use HolestPayCore;
        use HolestPayConversion;
        use HolestPayNet;
        use HolestPaySign;
        use HolestPayUI;
        use HolestPayI8N;
        use HolestPayMethods;
        ///////////////////////////////////////////////
        
        /**
         * Constructor / private - SINGLETON PATTERN
         * 
         */
        private function __construct(){
            $this->instance_uid = "hpaylib-" .rand(100000,999999);
        }

        /**
         * Checks if instance is active instance; in-active instances don't react to web requests
         * @return bool true or false
         */
        public function isActiveInstance(){
            return $this->instance_uid == HolestPayLib::$active_instance_uid;
        }

        /**
         * deactivates instance to not respond to events and web requests
         * @return string instance UID
         */
        public function deactivate(){
            if($this->instance_uid == HolestPayLib::$active_instance_uid){
                HolestPayLib::$active_instance_uid = null;
            }
            return $this->instance_uid;
        }

         /**
         * activates instance to start responding to events and web requests
         * @return string instance UID
         */
        public function activate(){
            HolestPayLib::$active_instance_uid = $this->instance_uid;
            return $this->instance_uid;
        }

        /**
         * returns current data provider instance
         * @return \holestpay\HolestPayAbstractDataProvider
         */
        public static function dataProvider(){
            return self::$_data_provider;
        }

        /**
         * returns current log provider instance
         * @return \holestpay\HolestPayAbstractLogProvider
         */
        public static function logProvider(){
            return self::$_log_provider;
        }

        /**
         * returns current lib configuration
         * @return \holestpay\HolestPayAbstractLogProvider
         */
        public static function libConfig(){
            return self::$_config;
        }


        /**
         * @param string $logscope - can be just "error"|"waring"|"log" or something like "order_4635764_result"
         * @param any $data - data to log 
         * @param int? $stack - level of call stack to also include
         * @return bool - true on successful write
         */
        public static function writeLog($logscope, $data, $stack = null ){
            if(HolestPayLib::logProvider()){
                if(intval($stack)){
                $s = debug_backtrace(2,intval($stack) + 1);
                $data =  array(
                    "@" => $data,
                    "backtrace" => array_shift($s)
                );
                }
                return HolestPayLib::logProvider()->writeLog($logscope, $data);
            }else{
                return false;
            }
        }

        /**
         * Initializes the library from confg. This is the library config, and not the SITE/POS HPay config
         * 
         * @param string $config - if null|empty then define HOLESTPAYLIB_CONFIG_SOURCE be used which is by default holestpay.ini from this folder. You can pass file location (with ini, or json context), JSON string, ASOC Array or Object with config. If ini file path is used then .ini file extension is required.
         * @param bool $reset - forces re-configuration
         * @return bool? true if configuration happened, null if it was already done and skiped, throws exception if unable to initaialize
         */
        public static function init($config = null, $reset = false){
            //ALREADY CONFIGURED AND NO RESET REQUESTED
            if(HolestPayLib::$_config && !$reset)
                return null;

            if($reset){
                HolestPayLib::$_config = null;
            }    

            $cfg = null;
            $cfg_source = "";

            if($config){
                if(is_string($config)){
                    $config = trim($config);
                    if(substr($config,0,1) == "{" && substr($config,-1) == "}"){
                        $cfg_source = '$config param - json string';
                        $cfg = json_decode($config, true);
                    }else if(file_exists($config)){
                        if(strtolower(substr($config,-4)) == ".ini"){
                            $cfg_source = '$config param - custom ini path';
                            $cfg = parse_ini_file($config);
                        }else{
                            $cfg_source = '$config param - custom json file';
                            //if .ini is not exenstion then JSON
                            $jcnt = @file_get_contents($config); 
                            if($jcnt){
                                $jcnt = trim($jcnt);
                                if(substr($jcnt,0,1) == "{" && substr($jcnt,-1) == "}"){
                                    $cfg = json_decode($jcnt, true);
                                }
                            }
                        } 
                    }
                }else if(is_array($config)){
                    $cfg_source = '$config param - assoc_array';
                    $cfg = $config;
                }else if(is_object($config)){
                    $cfg_source = '$config param - object';
                    $cfg = (array)$config;
                }
            }else{
                if(file_exists(__DIR__ ."/holestpay.ini")){
                    $cfg_source = "default lib's holestpay.ini";
                    $cfg = parse_ini_file(__DIR__ ."/holestpay.ini");
                }
            }

            if($cfg){
                $err = "";

                if(isset($cfg['data_provider_class'])){
                    if($cfg['data_provider_class']){
                        $cfg['data_provider_class'] = trim($cfg['data_provider_class']);
                        if(strtolower(substr($cfg['data_provider_class'],-4)) == ".php"){
                            $cfg['data_provider_class'] = substr($cfg['data_provider_class'],0, strlen($cfg['data_provider_class']) - 4);
                        }
                        $provider_class_file = "";
                        if(strpos($cfg['data_provider_class'],"/") !== false || strpos($cfg['data_provider_class'],"\\") !== false){
                            if(file_exists("{$cfg['data_provider_class']}.php")){
                                $provider_class_file = "{$cfg['data_provider_class']}.php";
                            }else if(file_exists(__DIR__ . "/implement/{$cfg['data_provider_class']}.php")){
                                $provider_class_file = __DIR__ . "/implement/{$cfg['data_provider_class']}.php";
                            }
                        }else if(file_exists(__DIR__ . "/implement/{$cfg['data_provider_class']}.php")){
                            $provider_class_file = __DIR__ . "/implement/{$cfg['data_provider_class']}.php";
                        }
                        if($provider_class_file){
                            require_once($provider_class_file);
                            $path_parts = pathinfo($provider_class_file);
                            $class_name = $cfg['data_provider_class_namespace'] ."\\". $path_parts['filename'];

                            if(class_exists($class_name)){
                                //////////////////////////////////////////////////////////////////
                                HolestPayLib::$_config   = $cfg;
                                HolestPayLib::$_data_provider = new $class_name(HolestPayLib::$_config);
                                //////////////////////////////////////////////////////////////////    
                            }else{
                                $err = "data_provider_class {$class_name} not found in " . $provider_class_file ;
                            }
                        }else{
                            $err = "data_provider_class php file not found: " . $provider_class_file;
                        }
                    }else{
                        $err = "data_provider_class configuration parameter not set";
                    }
                }else{
                    $err = "data_provider_class configuration parameter not set";
                }

                if(!$err){

                    if(isset($cfg['log_enabled']) && isset($cfg['log_provider_class'])){
                        if($cfg['log_enabled'] && $cfg['log_provider_class']){
                            $cfg['log_provider_class'] = trim($cfg['log_provider_class']);
                            if(strtolower(substr($cfg['log_provider_class'],-4)) == ".php"){
                                $cfg['log_provider_class'] = substr($cfg['log_provider_class'],0, strlen($cfg['log_provider_class']) - 4);
                            }
                            $log_class_file = "";
                            if(strpos($cfg['log_provider_class'],"/") !== false || strpos($cfg['log_provider_class'],"\\") !== false){
                                if(file_exists("{$cfg['log_provider_class']}.php")){
                                    $log_class_file = "{$cfg['log_provider_class']}.php";
                                }else if(file_exists(__DIR__ . "/implement/{$cfg['log_provider_class']}.php")){
                                    $log_class_file = __DIR__ . "/implement/{$cfg['log_provider_class']}.php";
                                }
                            }else if(file_exists(__DIR__ . "/implement/{$cfg['log_provider_class']}.php")){
                                $log_class_file = __DIR__ . "/implement/{$cfg['log_provider_class']}.php";
                            }
                            if($log_class_file){
                                require_once($log_class_file);
                                $path_parts = pathinfo($log_class_file);
                                $class_name = $cfg['log_provider_class_namespace'] ."\\". $path_parts['filename'];
    
                                if(class_exists($class_name)){
                                    //////////////////////////////////////////////////////////////////
                                    HolestPayLib::$_log_provider = new $class_name(HolestPayLib::$_config);
                                    //////////////////////////////////////////////////////////////////    
                                }
                            }
                        }
                    }

                    HolestPayLib::instance(true);//CREATE INSTANCE

                    return true;
                }

                throw new \Exception("HOLESTPAYLIB: {$err}!");
            }else{
                throw new \Exception('HOLESTPAYLIB: unable to load library configuration! Configuration source: ' . $cfg_source);
            }
        }
        
        /**
         * Gets the running SINGLETON instance
         * @param bool $force_recreate - force instance recreation
         */
        public static function instance($force_recreate = false){
            if(!HolestPayLib::$_instance || $force_recreate){
                HolestPayLib::$_instance = new HolestPayLib();
                HolestPayLib::$active_instance_uid = HolestPayLib::$_instance->instance_uid;
            }
            return HolestPayLib::$_instance;
        }


    }
}


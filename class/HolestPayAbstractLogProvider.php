<?php
/**
* HolestPay PHP lib
* -------------------------------------------------
* File Version: 1.0.1
**/
namespace holestpay;

if(!defined('HOLESTPAYLIB')){
  die("Direct access to this file is not allowed");
}

abstract class HolestPayAbstractLogProvider {
/**
 * Provider constructior. You should never call constructor of exteneded provider class yourself. HolestPayLib will call it internaly, and you only set log_provider_class lib configuration parameter to provider class (extended from this)  file name / class name (file name / class name must be same)
 * @param array (assoc) $lib_configuration - library configuration
 */
 public function __construct($lib_configuration){
  //
 }

/**
 * writes the data 
 * 
 * @param string $logscope - can be just "error"|"waring"|"log" or something like "order_4635764_result"
 * @param any $data - data to log
 * @return boolean - true on success , false on failure
 */
  abstract public function writeLog($logscope, $data);

/**
 * gets error logs
 * @return array - array of found error logs by date
 */
  abstract public function get_error_logs();

  
}
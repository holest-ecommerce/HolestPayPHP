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

abstract class HolestPayAbstractTranslationProvider {
/**
 * Provider constructior. You should never call constructor of exteneded provider class yourself. HolestPayLib will call it internaly, and you only set translation_provider_class lib configuration parameter to provider class (extended from this)  file name / class name (file name / class name must be same)
 * @param assoc_array $lib_configuration - library configuration
 */
 public function __construct($lib_configuration){
  //
 }


/**
 * Translates $phrase into $lng. $arg1-6 are value replacments 
 * @param string $phrase - phrase to translate
 * @param string $lng - target hpay language
 * @param any $arg1 - replacment value 1
 * @param any $arg2 - replacment value 2
 * @param any $arg3 - replacment value 3
 * @param any $arg4 - replacment value 4
 * @param any $arg5 - replacment value 5
 * @param any $arg6 - replacment value 6
 * @return string - translated $phrase. Original if translation is not found
 */
  abstract public function translate($phrase, $lng, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null, $arg5 = null, $arg6 = null);
}
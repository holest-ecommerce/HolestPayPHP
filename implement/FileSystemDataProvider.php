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

require_once(__DIR__ . "/../class/HolestPayAbstractDataProvider.php");

class FileSystemDataProvider extends HolestPayAbstractDataProvider{
 private $lib_configuration = null;
 
 /**
   * Provider constructior. You should never call this constructor yourself. HolestPayLib will call it internaly, and you only set data_provider_class lig configuration parameter to this file name / class name (file name and class name must be same)
   * @param assoc_array $lib_configuration - library configuration
   */
  public function __construct($lib_configuration){
    $this->lib_configuration = $lib_configuration;
  }

/**
 * writes current user displayable payment information for order as HTML 
 * 
 * @param string $order_uid - order unique identifikator
 * @param string $html - html (use at lest mediumtext for DB!)
 * @return bool - true on success , false on failure
 */
 public function writePaymentResponseHTML($order_uid, $html){}

/**
 * writes current user displayable fiscal or integration methods (for mutiple methods output is combined) information for order as HTML 
 * 
 * @param string $order_uid - order unique identifikator
 * @param string $html - html (use at lest mediumtext for DB!)
 * @return bool - true on success , false on failure
 */
  public function writeFiscalOrIntegrationResponseHTML($order_uid, $html){}

/**
 * writes current user displayable shipping method  (for mutiple methods output is combined)  information for order as HTML 
 * 
 * @param string $order_uid - order unique identifikator
 * @param string $html - html (use at lest mediumtext for DB!)
 * @return bool - true on success , false on failure
 */
  public function writeShippingResponseHTML($order_uid, $html){}

/**
 * gets current user displayable payment information for order as HTML. Empty if does not exists 
 * 
 * @param string $order_uid - order unique identifikator
 * @return string - current HTML for payment info. Empty if nothing exists 
 */
  public function getPaymentResponseHTML($order_uid){}

/**
 * gets current user displayable fiscal or integration methods information (for mutiple methods output is combined) for order as HTML. Empty if nothing exists 
 * 
 * @param string $order_uid - order unique identifikator
 * @return string - current HTML for fiscal or integration methods information info. Empty if nothing exists
 */
  public function getFiscalOrIntegrationResponseHTML($order_uid){}

/**
 * gets current user displayable shipping methods information (for mutiple methods output is combined) for order as HTML. Empty if nothing exists 
 * 
 * @param string $order_uid - order unique identifikator
 * @return string- current HTML for shipping methods information info. Empty if nothing exists
 */
  public function getShippingResponseHTML($order_uid){}

/**
 * writes excahnge rate and its timestamp to cache 
 * 
 * @param string[3] $form - uppercase 3 letter code of source currency like EUR, USD, RSD, BAM, MKD,...
 * @param string[3] $to - uppercase 3 letter code of destination currency like EUR, USD, RSD, BAM, MKD,...
 * @param float|array $rate - raw convestion rate, or array("rate" => 0.322234, "ts" => time())
 * @param int $ts - time of rate as php timestamp (time() function)
 * @return assoc_array array("rate" => 0.322234, "ts" => time())
 */
  public function cacheExchnageRate($form, $to, $rate, $ts = null){}

/**
 * reads excahnge rate and its timestamp from cache. Important: this function does not check ts. If you use you custom data provider you must check if fresh excahnge rate need to be read again and written to cache. Default data providers use excahnge_rate_cache_h parameter to set how long exchange rate is considered valid
 * 
 * @param string[3] $form - uppercase 3 letter code of source currency like EUR, USD, RSD, BAM, MKD,...
 * @param string[3] $to - uppercase 3 letter code of destination currency like EUR, USD, RSD, BAM, MKD,...
 * @return assoc_array array("rate" => 0.322234, "ts" => time())
 */
  public function readExchnageRate($form, $to){}

/**
 * reads array of all results received from HolestPay in the chronological order. Result may or may not contain payment transaction. You can have your custom storage for them but it is important to have one field that may accept full result JSON (so at least mediumtext if db is used) 
 * @param string $order_uid - order unique identifikator
 * @return array array( [{ ...result: ... },...] )
 */
  public function getResultsForOrder($order_uid){}

/**
 * writes all received results from HolestPay to data storage (existing & new). 
 * @param string $order_uid - order unique identifikator
 * @param array $results - all results received from HolestPay
 * @return bool- true on success , false on failure
 */
  public function writeResultsForOrder($order_uid, $results){}

/**
 * appends received result from HolestPay to data storage (existing & new). Function should check if result is already recived and skip writting in that case or better overwrite previous
 * @param string $order_uid - order unique identifikator
 * @param assoc_array $result - most recent result
 * @return bool - true if write happened , false on skip
 */
  public function appendResultForOrder($order_uid, $result){}

 /**
 * updates your site order based on current HPay result. Lib will call this method after all other methods it may call all executed. If you create this order in this moment you may keep order method write operations in memory untill this method is called
 * @param string $order_uid - order unique identifikator
 * @param assoc_array $result - most recent result  
 * @return - true on success , false on failure
 */
  public function updateOrder($order_uid, $result){}

/**
 * HPay tries to deliver result to your site in few ways. To prevent result processing at the same time at once use this method to only let first arrived request for same result to be accepted
 * @param string $order_uid - order unique identifikator
 * @return - true on successful locking otherwise false. If false abandon further execution!
 */
  public function lockOrderUpdate($order_uid){}

 /**
 * HPay tries to deliver result to your site in few ways. To prevent result processing at the same time at once use this method to unlock order updates after you successfully accepted result
 * @param string $order_uid - order unique identifikator
 * @return bool - true on successful unlocking otherwise false. 
 */
  public function unlockOrderUpdate($order_uid){}

 /**
 * HPay tries to deliver result to your site in few ways. If result has already been accepted you don't need to accept it again. You use md5(verificationhash) or md5(vhash) to get unique result identification. See hpay status specification ib readme.MD
 * @param string $result_md5_hash. Usualy calculated as md5(verificationhash) or md5(vhash)
 * @return bool - true if result was already accepted otherwise false.  
 */
  public function resultAlreadyReceived($result_md5_hash){}

/**
 * gets HPay status. HPay status is composed of payment status and statuses for all fiscal, integration and shipping metods. By default its string, but you may get it as array is you set second prameter as true. In that case you will get array like this array("PAYMENT" => "--PAYMENT_STATUS--", "FISCAL" => array("method1_uid" => array("status1" => "status1_val"), "SHIPPING" => array("method1_uid" => array("status1" => "status1_val"))  ). See hpay status specification ib readme.MD
 * @param string $order_uid - order unique identifikator
 * @param array $as_array - parse reurn value as array
 * @return string|assoc_array - HPAY status as string or prased if $as_array == true. If parsed reurned array will always have "PAYMENT","FISCAL" and "SHIPPING" keys. If there is nothing their value willl be null 
 */
  public function getOrderHPayStatus($order_uid, $as_array = false){}

/**
 * sets to HPay status for order. HPay status is composed of payment status and statuses for all fiscal, integration and shipping metods. By default all is placed in single string. You can pass assoc_array for value to indicate only update of "PAYMENT","FISCAL" and "SHIPPING" part like array("PAYMENT" => "PAID"). Function needs to preseve all previous and just add ou update statuses (once added status for anything can not just dissapear).  
 * @param string $order_uid - order unique identifikator
 * @param strinh|assoc_array $hpay_status - full hpay_status as string in its format. Partial status as string for payment or/and fiscal or/and integration or/and shipping metods. Once ste status for some method can not dissapear it can only change value.
 * @return string - full HPAY status in string form for order in HPay status format
 */
  public function setOrderHPayStatus($order_uid, $hpay_status){}

/**
 * gets HPay order in HolestPay format eather from $order_uid or full site order object 
 * @param string|Order $order_uid_or_site_order - $order_uid to read from data storage or full order object from site to convert to HPay Order
 * @return assoc_array - HPAY Order
 */
  public function getHOrder($order_uid_or_site_order){}

/**
 * gets HPay cart in HolestPay format eather from $order_uid or full site order or chart object 
 * @param string|Order|Cart $order_uid_or_site_order_or_site_cart - $order_uid to read from data storage or full order object from site to convert to HPay Cart or site Cart object to HPay Cart
 * @return assoc_array - HPAY Order
 */  
  public function getHCart($order_uid_or_site_order_or_site_cart){}

/**
* gets array of vault references for user to be used for charge or presented user to choose from. $user_uid is usually email. 
* @param string $user_uid - user identifier / usually email
* @return assoc_array - vault reference data. Basides value it ,may contain masked pan, last use time, method for which its valid for
*/ 
  public function getVaultReferences($user_uid){}

/**
* adds vault references for user to be used for future charges. $user_uid is usually email.
* @param string $user_uid - user identifier / usually email
* @param assoc_array - vault reference data. Basides value it ,may contain masked pan, last use time, method for which its valid for 
* @return bool - true on success , false on failure
*/  
  public function addVaultReference($user_uid, $vault_data){}
 
/**
* removes vault reference by its value 
* @param string $vault_ref - value of vault reference pointer itself
* @return bool - true on real delete happened, otherwise false
*/  
  public function removeVaultReference($vault_ref){}

/**
* updates vault reference by its value 
* @param string $vault_ref - value of vault reference pointer itself
* @param assoc_array $vault_data - vault reference data. Basides value it ,may contain masked pan, last use time, method for which its valid for
* @return bool - true on success, false on failure
*/  
  public function updateVaultReference($vault_ref, $vault_data){}

/**
 * PCI DSS 4.0.+ requires script integrity check to prevent CDN file modifiation and XSS attacks. This function will be used to store script integrity value for all scripts you load from HolestPay CDN. If integrity script tag attribute is wrong script will not be executed by browsers.
 * @param string $script_handle - may be 'hpay' OR 'hpay.clientadmin.ui' OR your custom script handle where you can use HolestPayUtils::CalculateScriptIntegrity(script_url) function for other external scripts in moment when you turst them and when you are sure they are not modified by attackers. 
 * @param string $integrity_hash - value for script tag integrity attribute obtained from HolestPay in secure way for 'hpay'|'hpay.clientadmin.ui' or calculated by you for other scripts
 * @return bool - true on success, false on failure
 */
  public function setScriptIntegrityHash($script_handle, $integrity_hash){}

/**
 * PCI DSS 4.0.+ requires script integrity check to prevent CDN file modifiation and XSS attacks. Use this to get value integrity attribute of script tag that points to script loaded from HolestPay servers
 * @param string $script_handle - may be 'hpay' OR 'hpay.clientadmin.ui'
 * @return string - value for script tag integrity attribute obtained from HolestPay in secure way
 */
  public function getScriptIntegrityHash($script_handle){}


 /**
 * returns site language
 * @return string - language, should be 2 lowercase letters language code like 'rs','en','de','mk','el'... 
 */
  public function getLanguage(){}


/**
 * loads site HPay configuration from permanent data storage
 * @return assoc_array - HPay site configuration
 */
public function loadSiteConfiguration(){

}

/**
 * writes site HPay configuration to permanent data storage
 * @param string|assoc_array $site_configuration - configuration to set including POS setup. If string it will be JSON deserialized. If you use single filed for it in DB make sure it can accept large amount of data. At least mediumtext
 * @return assoc_array - Site configuration that was set
 */
public function setSiteConfiguration($site_configuration){


}


}
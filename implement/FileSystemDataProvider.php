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

require_once(__DIR__ . "/../class/HolestPayAbstractDataProvider.php");

/**
 * Provider that writes all data to folder on disk as json files. All order data is a single file (responses, html responses, transactions...). Probably you won't use this data provider. It's good for testing purpohoses. If you decide to use it make sure ini/conf setting file_provider_folder is for a folder not accesible from public web!
 */
class FileSystemDataProvider extends HolestPayAbstractDataProvider{
  private $lib_configuration = null;
  private $site_data_folder  = null; 
  private $lock_timeout      = 22;
  private $use_cache         = true;
  private $CACHE             = array(); 
 
 /**
   * Provider constructior. You should never call this constructor yourself. HolestPayLib will call it internaly, and you only set data_provider_class lig configuration parameter to this file name / class name (file name and class name must be same)
   * @param assoc_array $lib_configuration - library configuration
   */
  public function __construct($lib_configuration){
    $this->lib_configuration = $lib_configuration;
    if(isset($this->lib_configuration["file_provider_folder"])){
        
        if(strpos($this->lib_configuration["file_provider_folder"],"./") === 0){
          $this->lib_configuration["file_provider_folder"] = substr($this->lib_configuration["file_provider_folder"],2);
        }

        if(file_exists($this->lib_configuration["file_provider_folder"])){
          $this->site_data_folder = rtrim($this->lib_configuration["file_provider_folder"],"/");
        }else{
          if(file_exists(HPAY_LIB_ROOT . "/" . $this->lib_configuration["file_provider_folder"])){
            $this->site_data_folder = rtrim(HPAY_LIB_ROOT . "/" . $this->lib_configuration["file_provider_folder"],"/");
          }else if(file_exists(__DIR__ . "/" . $this->lib_configuration["file_provider_folder"])){
            $this->site_data_folder = rtrim(__DIR__ . "/" . $this->lib_configuration["file_provider_folder"],"/");
          }else{ 
            //IT MUST EXIST!
            HolestPayLib::writeLog("error", __FILE__ . " file_provider_folder - path not found");
          }
        }

        if($this->site_data_folder){
          $this->site_data_folder = str_replace("/",DIRECTORY_SEPARATOR, $this->site_data_folder);
          $this->site_data_folder = explode(DIRECTORY_SEPARATOR,$this->site_data_folder);
          $n = array();
          foreach($this->site_data_folder as $index => $part){
            if(isset($this->site_data_folder[$index + 1])){
              if($this->site_data_folder[$index + 1] == ".."){
                continue;
              }
            }
            if($this->site_data_folder[$index] == "..")
              continue;
            $n[] = $this->site_data_folder[$index];
          }
          $this->site_data_folder = implode(DIRECTORY_SEPARATOR,$n);
        }
    }

    if(isset($this->lib_configuration["lock_timeout"])){
      $this->lock_timeout = intval($this->lib_configuration["lock_timeout"]);
      if(!$this->lock_timeout)
          $this->lock_timeout = 22;
      elseif($this->lock_timeout < 5){
        $this->lock_timeout = 5;
      }  
    }
  }

  private function getFullPath($path){
    if(!$this->site_data_folder)
      return null;
    return str_replace("/",DIRECTORY_SEPARATOR, $this->site_data_folder . "/" . ltrim($path,"/"));
  }

  private function loadJsonFromPath($path, $default_data = array(), $nocache = false){
    if(!$nocache && $this->use_cache && strpos($path,".lock") === false){
        if(isset($this->CACHE[$path]))
          return $this->CACHE[$path];  
    }

    $full_path = $this->getFullPath($path);
    if(file_exists($full_path)){
      try{
        $data = json_decode( file_get_contents($full_path), true);
        if(!$nocache && $this->use_cache && strpos($path,".lock") === false){
            $this->CACHE[$path] = $data;  
        }

        return $data;
      }catch(Throwable $ex){
        HolestPayLib::writeLog("error", "ERROR: Unable to load {$full_path}: " . $ex->getMessage(),5);
      }
    }
    return $default_data;
  }

  private function mergeData($dst, $src){
    $result = array();

    $dst = $dst ? ((array)$dst): array();
    $src = $src ? ((array)$src): array();

    $HPayResponses = null;

    if(isset($dst["HPayResponses"])){
      if(isset($src["HPayResponses"])){
         $tuids = array();
         $HPayResponses = array();
         foreach($dst["HPayResponses"] as $hresp){
          if(isset($hresp["transaction_uid"])){
            $tuids[] = $hresp["transaction_uid"];
            $HPayResponses[] = $hresp;
          }
         } 

         foreach($src["HPayResponses"] as $hresp){
          if(isset($hresp["transaction_uid"])){
            if(!in_array($hresp["transaction_uid"],$tuids)){
              $HPayResponses[] = $hresp;
            }
          }
         }
      }
    }  

    $result = array_merge($result, $dst , $src, $HPayResponses ? array("HPayResponses" => $HPayResponses) : array());
    return $result;
  }

  private function writeJsonToPath($path, $data, $nocache = false){
    $full_path = $this->getFullPath($path);
    try{
      
      $dir = dirname($full_path);

      if(!file_exists($dir)){
        mkdir( $dir, 0775, true );
      }
      
      if(!$nocache && $this->use_cache && strpos($path,".lock") === false && $data){
        $this->CACHE[$path] = $data;  
      }

      return file_put_contents($full_path, !empty($data) ? json_encode($data, JSON_PRETTY_PRINT): "{}");
    }catch(Throwable $ex){
      HolestPayLib::writeLog("error", "ERROR: Unable to write {$full_path}: " . $ex->getMessage(),5);
    }
  }

  private function mergeJsonToPath($path, $data, $nocache = false){
    try{
      $exising_data = $this->loadJsonFromPath($path, array());
      $ndata = $this->mergeData($exising_data, $data);
      $full_path = $this->site_data_folder . "/" . ltrim($path,"/");

      if(!$nocache && $this->use_cache && strpos($path,".lock") === false  && $ndata){
        $this->CACHE[$path] = $ndata;  
      }

      return file_put_contents($full_path, !empty($ndata) ? json_encode($ndata, JSON_PRETTY_PRINT): "{}");
    }catch(Throwable $ex){
      HolestPayLib::writeLog("error", "ERROR: Unable to merge-write {$path}: " . $ex->getMessage(),5);
    }
  }

/**
 * writes current user displayable payment information for order as HTML (may contain multiple responses)
 * 
 * @param string $order_uid - order unique identifikator
 * @param string $html - html (use at lest mediumtext for DB!)
 * @return bool - true on success , false on failure
 */
 public function writePaymentResponseHTML($order_uid, $html){
    return $this->mergeJsonToPath("/orders/{$order_uid}/data.json", array(
      "PaymentResponseHTML" => $html
    )) > 0;
 }

/**
 * writes current user displayable fiscal or integration methods (for mutiple methods output is combined) information for order as HTML 
 * 
 * @param string $order_uid - order unique identifikator
 * @param string $html - html (use at lest mediumtext for DB!)
 * @return bool - true on success , false on failure
 */
  public function writeFiscalOrIntegrationResponseHTML($order_uid, $html){
    return $this->mergeJsonToPath("/orders/{$order_uid}/data.json", array(
      "FiscalOrIntegrationResponseHTML" => $html
    )) > 0;
  }

/**
 * writes current user displayable shipping method  (for mutiple methods output is combined)  information for order as HTML 
 * 
 * @param string $order_uid - order unique identifikator
 * @param string $html - html (use at lest mediumtext for DB!)
 * @return bool - true on success , false on failure
 */
  public function writeShippingResponseHTML($order_uid, $html){
    return $this->mergeJsonToPath("/orders/{$order_uid}/data.json", array(
      "ShippingResponseHTML" => $html
    )) > 0;
  }

/**
 * gets current user displayable payment information for order as HTML. Empty if does not exists 
 * 
 * @param string $order_uid - order unique identifikator
 * @return string - current HTML for payment info. Empty if nothing exists 
 */
  public function getPaymentResponseHTML($order_uid){
    $order = $this->loadJsonFromPath("/orders/{$order_uid}/data.json");
    if(isset($order["PaymentResponseHTML"]))
      return $order["PaymentResponseHTML"];
    return "";
  }

/**
 * gets current user displayable fiscal or integration methods information (for mutiple methods output is combined) for order as HTML. Empty if nothing exists 
 * 
 * @param string $order_uid - order unique identifikator
 * @return string - current HTML for fiscal or integration methods information info. Empty if nothing exists
 */
  public function getFiscalOrIntegrationResponseHTML($order_uid){
    $order = $this->loadJsonFromPath("/orders/{$order_uid}/data.json");
    if(isset($order["FiscalOrIntegrationResponseHTML"]))
      return $order["FiscalOrIntegrationResponseHTML"];
    return "";
  }

/**
 * gets current user displayable shipping methods information (for mutiple methods output is combined) for order as HTML. Empty if nothing exists 
 * 
 * @param string $order_uid - order unique identifikator
 * @return string- current HTML for shipping methods information info. Empty if nothing exists
 */
  public function getShippingResponseHTML($order_uid){
    $order = $this->loadJsonFromPath("/orders/{$order_uid}/data.json");
    if(isset($order["ShippingResponseHTML"]))
      return $order["ShippingResponseHTML"];
    return "";
  }

/**
 * writes excahnge rate and its timestamp to cache 
 * 
 * @param string[3] $form - uppercase 3 letter code of source currency like EUR, USD, RSD, BAM, MKD,...
 * @param string[3] $to - uppercase 3 letter code of destination currency like EUR, USD, RSD, BAM, MKD,...
 * @param float|array $rate - raw convestion rate, or array("rate" => 0.322234, "ts" => time())
 * @param int $ts - time of rate as php timestamp (time() function)
 * @return assoc_array array("rate" => 0.322234, "ts" => time())
 */
  public function cacheExchnageRate($form, $to, $rate, $ts = null){

    $rate_data = is_array($rate) ? $rate : array(
      "rate" => floatval($rate),
      "ts" => $ts ? $ts : time()
    );

    if(!isset($rate_data["ts"]))
      $rate_data["ts"] = time();

    $this->writeJsonToPath("/exchange_rates/{$form}{$to}.json", $rate_data);
    return $rate_data;
  }

/**
 * reads excahnge rate and its timestamp from cache. Important: this function does not check ts. If you use you custom data provider you must check if fresh excahnge rate need to be read again and written to cache. Default data providers use excahnge_rate_cache_h parameter to set how long exchange rate is considered valid
 * 
 * @param string[3] $form - uppercase 3 letter code of source currency like EUR, USD, RSD, BAM, MKD,...
 * @param string[3] $to - uppercase 3 letter code of destination currency like EUR, USD, RSD, BAM, MKD,...
 * @return assoc_array array("rate" => 0.322234, "ts" => time())
 */
  public function readExchnageRate($form, $to){
    return $this->loadJsonFromPath("/exchange_rates/{$form}{$to}.json", null);
  }

/**
 * reads array of all results received from HolestPay in the chronological order. Result may or may not contain payment transaction. You can have your custom storage for them but it is important to have one field that may accept full result JSON (so at least mediumtext if db is used) 
 * @param string $order_uid - order unique identifikator
 * @return array array( [{ ...result: ... },...] )
 */
  public function getResultsForOrder($order_uid){
    $order = $this->loadJsonFromPath("/orders/{$order_uid}/data.json");
    if(isset($order["HPayResponses"]))
      return $order["HPayResponses"];
    return array();
  }

/**
 * writes all received results from HolestPay to data storage (existing & new). 
 * @param string $order_uid - order unique identifikator
 * @param array $results - all results received from HolestPay
 * @return bool- true on success , false on failure
 */
  public function writeResultsForOrder($order_uid, $results){
    $order = $this->loadJsonFromPath("/orders/{$order_uid}/data.json");
    $order["HPayResponses"] = $results;
    return $this->writeJsonToPath("/orders/{$order_uid}/data.json",$order);
  }

/**
 * appends received result from HolestPay to data storage (existing & new). Function should check if result is already recived and skip writting in that case or better overwrite previous
 * @param string $order_uid - order unique identifikator
 * @param assoc_array $result - most recent result
 * @return bool - true if write happened , false on skip
 */
  public function appendResultForOrder($order_uid, $result){
    $order = $this->loadJsonFromPath("/orders/{$order_uid}/data.json");
    $order = $this->mergeData($order, array(
      "HPayResponses" => array($result)
    ));
    return $this->writeJsonToPath("/orders/{$order_uid}/data.json",$order);
  }

 /**
 * updates your site order based on current HPay result. Lib will call this method after all other methods it may call all executed. If you create this order in this moment you may keep order method write operations in memory untill this method is called
 * @param string $order_uid - order unique identifikator
 * @param assoc_array $order - most recent result  
 * @return - true on success , false on failure
 */
  public function updateOrder($order_uid, $order){
    return $this->mergeJsonToPath("/orders/{$order_uid}/data.json",$order);
  }

/**
 * HPay tries to deliver result to your site in few ways. To prevent result processing at the same time at once use this method to only let first arrived request for same result to be accepted
 * @param string $order_uid - order unique identifikator
 * @return - true on successful locking otherwise false. If false abandon further execution!
 */
  public function lockOrderUpdate($order_uid){
     $lock = $this->loadJsonFromPath("/orders/{$order_uid}/order.lock", null, true);
     if($lock){
       if($lock["ts"] + $this->lock_timeout < time()){
          $lock = null;
       }
     }

     if($lock){
      return false;
     }

     return $this->writeJsonToPath("/orders/{$order_uid}/order.lock",array(
        "ts" => time()
     ), true);
  }

 /**
 * HPay tries to deliver result to your site in few ways. To prevent result processing at the same time at once use this method to unlock order updates after you successfully accepted result
 * @param string $order_uid - order unique identifikator
 * @return bool - true on successful unlocking otherwise false. 
 */
  public function unlockOrderUpdate($order_uid){
    return @unlink("/orders/{$order_uid}/order.lock");
  }

 /**
 * HPay tries to deliver result to your site in few ways. If result has already been accepted you don't need to accept it again. You use md5(verificationhash) or md5(vhash) to get unique result identification. See hpay status specification ib readme.MD
 * @param string $result_md5_hash. Usualy calculated as md5(verificationhash) or md5(vhash)
 * @return bool - true if result was already accepted otherwise false.  
 */
  public function resultAlreadyReceived($order_uid, $result_md5_hash){
    $exists = file_exists($this->getFullPath("/orders/{$order_uid}/results_hash/{$result_md5_hash}.rec", false));
    if(!$exists){
      //ONLY FIRST ONE WINS
      $this->writeJsonToPath("/orders/{$order_uid}/results_hash/{$result_md5_hash}.rec",array(
        "ts" => time()
      ));
    }
    return $exists;
  }
  
/**
 * gets HPay status. HPay status is composed of payment status and statuses for all fiscal, integration and shipping metods. By default its string, but you may get it as array is you set second prameter as true. In that case you will get array like this array("PAYMENT" => "--PAYMENT_STATUS--", "FISCAL" => array("method1_uid" => array("status1" => "status1_val"), "SHIPPING" => array("method1_uid" => array("status1" => "status1_val"))  ). See hpay status specification ib readme.MD
 * @param string $order_uid - order unique identifikator
 * @param array $as_array - parse reurn value as array
 * @return string|assoc_array - HPAY status as string or prased if $as_array == true. If parsed reurned array will always have "PAYMENT","FISCAL" and "SHIPPING" keys. If there is nothing their value willl be null 
 */
  public function getOrderHPayStatus($order_uid, $as_array = false){
    $order = $this->loadJsonFromPath("/orders/{$order_uid}/data.json");
    if(isset($order["HPayStatus"]))
      return $order["HPayStatus"];
    return "";
  }

/**
 * sets to HPay status for order. HPay status is composed of payment status and statuses for all fiscal, integration and shipping metods. By default all is placed in single string. You can pass assoc_array for value to indicate only update of "PAYMENT","FISCAL" and "SHIPPING" part like array("PAYMENT" => "PAID"). Function needs to preseve all previous and just add updated statuses (once added status for anything can not just dissapear).  
 * @param string $order_uid - order unique identifikator
 * @param strinh|assoc_array $hpay_status - full hpay_status as string in its format. Partial status as string for payment or/and fiscal or/and integration or/and shipping metods. Once ste status for some method can not dissapear it can only change value.
 * @return string - full HPAY status in string form for order in HPay status format
 */
  public function setOrderHPayStatus($order_uid, $hpay_status){
    $order = $this->loadJsonFromPath("/orders/{$order_uid}/data.json");
    $status = "";
    if(isset($order["HPayStatus"])){
      $status = $order["HPayStatus"];
    }

    $status = HolestPayLib::instance()->mergeHPayStatus($status,$hpay_status);

    return $this->mergeJsonToPath("/orders/{$order_uid}/data.json",array(
      "HPayStatus" => $status
    ));
    return $status;
  }

/**
 * FOR THIS PROVIDER ASSUMED SAME AS HPAY REQUEST(you probably need a different implementation that converts your order to HPay format). Gets HPay order in HolestPay format eather from $order_uid or full site order object 
 * @param string|Order $order_uid_or_site_order - $order_uid to read from data storage or full order object from site to convert to HPay Order
 * @return assoc_array - HPAY Order
 */
  public function getHOrder($order_uid_or_site_order){
    if(is_array($order_uid_or_site_order))
        return $order_uid_or_site_order; //just return becuse for this provider we assume same as HPay request native format. You probably need to convert your site order to hpay order
    else
        return $this->loadJsonFromPath("/orders/{$order_uid_or_site_order}/data.json",null);//for this provider we assume same as HPay request native format. You probably need to convert your site order to hpay order  
  }

/**
 * gets HPay cart in HolestPay format eather from $order_uid or full site order or chart object 
 * @param string|Order|Cart $order_uid_or_site_order_or_site_cart - $order_uid to read from data storage or full order object from site to convert to HPay Cart or site Cart object to HPay Cart
 * @return assoc_array - HPAY Order
 */  
  public function getHCart($site_cart){
    return $this->getHOrder($site_cart);//for this provider we assume same as HPay request native format. You probably need to convert your site order to hpay order 
  }

/**
* gets array of vault references for user to be used for charge or presented user to choose from. $user_uid is usually email. 
* @param string $user_uid - user identifier / usually email
* @return assoc_array - vault reference data. Basides value it ,may contain masked pan, last use time, method for which its valid for
*/ 
  public function getVaultReferences($user_uid){
    return $this->loadJsonFromPath("/users/{$user_uid}/vault.json");
  }

/**
* adds vault references for user to be used for future charges. $user_uid is usually email.
* @param string $user_uid - user identifier / usually email
* @param assoc_array - vault references data array. Basides value it ,may contain masked pan, last use time, method for which its valid for 
* @return bool - true on success , false on failure
*/   
  public function addVaultReference($user_uid, $vault_data){
    if(!$vault_data)
      return false;

    if(!isset($vault_data["vault_token_uid"]))
      return false;

    $vaults = $this->getVaultReferences($user_uid);
    $vaults[] = $vault_data;

    return $this->writeJsonToPath("/users/{$user_uid}/vault.json", $vaults);
  }
 
/**
* removes vault reference by its value 
* @param string $user_uid - user identifier / usually email
* @param string $vault_token_uid - value of vault reference pointer itself
* @return bool - true on real delete happened, otherwise false
*/  
  public function removeVaultReference($user_uid, $vault_token_uid){
    $user_vaults = $this->loadJsonFromPath("/users/{$user_uid}/vault.json");
    $n = array();
    $found = false;
    if(!empty($user_vaults)){
      foreach($user_vaults as $vault){
        if(isset($vault["vault_token_uid"])){
          if($vault["vault_token_uid"] == $vault_token_uid){
            $found = true;
            continue;
          }
          $n[] = $vault;
        }
      }
    }
    if($found)
      $this->writeJsonToPath("/users/{$user_uid}/vault.json", $n);
    return $found;
  }

/**
* updates vault reference by its value 
* @param string $user_uid - user identifier / usually email
* @param string $vault_token_uid - value of vault reference pointer itself
* @param assoc_array $vault_data - vault reference data. Basides value it ,may contain masked pan, last use time, method for which its valid for
* @return bool - true on success, false on failure
*/  
  public function updateVaultReference($user_uid, $vault_token_uid, $vault_data){
    if(!$vault_data)
      return false;

    if(!isset($vault_data["vault_token_uid"]))
      return false;

    $user_vaults = $this->loadJsonFromPath("/users/{$user_uid}/vault.json");
   
    $found = false;
    if(!empty($user_vaults)){
      foreach($user_vaults as $index => $vault){
        if(isset($vault["vault_token_uid"])){
          if($vault["vault_token_uid"] == $vault_token_uid){
            $found = true;
            $user_vaults[$index] = $vault_data;
            break;
          }
        }
      }
    }
    if($found)
      $this->writeJsonToPath("/users/{$user_uid}/vault.json", $user_vaults);
    return $found;
  }

/**
 * PCI DSS 4.0.+ requires script integrity check to prevent CDN file modifiation and XSS attacks. This function will be used to store script integrity value for all scripts you load from HolestPay CDN. If integrity script tag attribute is wrong script will not be executed by browsers.
 * @param string $script_handle - may be 'hpay' OR 'hpay.clientadmin.ui' OR your custom script handle where you can use HolestPayUtils::CalculateScriptIntegrity(script_url) function for other external scripts in moment when you turst them and when you are sure they are not modified by attackers. 
 * @param string $integrity_hash - value for script tag integrity attribute obtained from HolestPay in secure way for 'hpay'|'hpay.clientadmin.ui' or calculated by you for other scripts
 * @return bool - true on success, false on failure
 */
  public function setScriptIntegrityHash($script_handle, $integrity_hash){
    $scripts_subresource_integrity = $this->loadJsonFromPath("/subresource_integrity.json");
    $scripts_subresource_integrity[$script_handle] = $integrity_hash;
    $this->writeJsonToPath("/subresource_integrity.json", $scripts_subresource_integrity);
  }

/**
 * PCI DSS 4.0.+ requires script integrity check to prevent CDN file modifiation and XSS attacks. Use this to get value integrity attribute of script tag that points to script loaded from HolestPay servers
 * @param string $script_handle - may be 'hpay' OR 'hpay.clientadmin.ui'
 * @return string - value for script tag integrity attribute obtained from HolestPay in secure way
 */
  public function getScriptIntegrityHash($script_handle){
    $scripts_subresource_integrity = $this->loadJsonFromPath("/subresource_integrity.json");
    if(isset($scripts_subresource_integrity[$script_handle]))
      return $scripts_subresource_integrity[$script_handle];
    return false;
  }


 /**
 * returns site language
 * @return string - language, should be 2 lowercase letters language code like 'rs','en','de','mk','el'... 
 */
  public function getLanguage(){
      return HolestPayLib::libConfig()["default_language"];
  }

 /**
   * returns site currency
   * @return string - currency like RSD, EUR, MKD, BAM, USD, CHF, GBP... 
   */
  public function getCurrency(){
      return HolestPayLib::libConfig()["default_currency"];
  }

  /**
   * loads site HPay configuration from permanent data storage
   * @return assoc_array - HPay site configuration
   */
  public function loadSiteConfiguration(){
    return $this->loadJsonFromPath("/site_config.json", null);
  }

  /**
   * writes site HPay configuration to permanent data storage
   * @param string|assoc_array $site_configuration - configuration to set including POS setup. If string it will be JSON deserialized. If you use single filed for it in DB make sure it can accept large amount of data. At least mediumtext
   * @return assoc_array - Site configuration that was set
   */
  public function setSiteConfiguration($site_configuration){
    $this->writeJsonToPath("/site_config.json", $site_configuration);
  }


}
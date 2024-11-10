<?php
/**
* HolestPay PHP lib
* -------------------------------------------------
* File Version: 1.0.1
**/
namespace holestpay;

use Exception;
use Throwable;

if(!defined('HOLESTPAYLIB')){
    die("Direct access to this file is not allowed");
}

trait HolestPayCore{

    private $_webResultHandlerCalled = false;
    private $_webHooksHandlerCalled = false;
    private $_HSiteConfig = null;
    

    /**
     * This function is called automaticly in lib (lib configuration paremetar no_automatic_webresult_handling). If due you project structure you need to call it explicitly then it should be called on user order thank you page (page where user is redirected after payment = hpay_request->order_user_url) and on web-hook accept data endpoint. 
     * @return bool - true when processing happens , false on otherwise
     */
    public function webResultHandler(){
        if($this->_webResultHandlerCalled){
            try{
                if(isset($_REQUEST["hpay_forwarded_payment_response"])){
                    $str_resp = $_REQUEST["hpay_forwarded_payment_response"];
                    $result = json_decode($str_resp, true);
                    if(!$result){
                        $result = json_decode(stripslashes($str_resp), true);
                    }
                    $hmethod = null;
                    if(isset($result["order_uid"])){
                        $order = HolestPayLib::dataProvider()->getHOrder($result["order_uid"]);
                        if(isset($result["payment_method"])){
                            $hmethod = $this->getPaymentMethod($result["payment_method"]);
                        }

                        if(!$hmethod && $order && @$order["payment_method"]){
                            $hmethod = $this->getPaymentMethod($order["payment_method"]);
                        }

                        if(!$hmethod){
                            $hmths = HolestPayLib::instance()->getPaymentMethods(true);
                            if(!empty($hmths)){
                                $hmethod = $hmths[0];
                            }
                        }

                        if($hmethod){
                            if(isset($result["status"]) && isset($result["transaction_uid"])){
                                $res = $this->acceptResult($order, $result, $hmethod);
                                
                                if($res === true){

                                    $return_url = HolestPayLib::libConfig()["site_url"];
                                    if(isset($result["order_user_url"]) && $result["order_user_url"]){
                                        $return_url = $result["order_user_url"];
                                    }else if(@HolestPayLib::libConfig()["order_user_url"]){
                                        $return_url = @HolestPayLib::libConfig()["order_user_url"];
                                    } 
                                   
                                    if(isset($_REQUEST['hpay_local_request']) && $_REQUEST['hpay_local_request']){
                                        http_response_code(200);
                                        header("Content-Type:application/json");
                                        echo json_encode(array("received" => "OK", "accept_result" => "ACCEPTED", "order_user_url" => $return_url));
                                        die;

                                    }else{
                                        http_response_code(302);
                                        header("Location: {$return_url}");
                                        die();
                                    }
                                }
                                return;
                            }
                        }else{
                            HolestPayLib::writeLog("error",'HPAY aborted payment response processing:' . json_encode($result, JSON_PRETTY_PRINT),5);
                        }
                    }

                    //IF RESULT IS NOT ACCEPTED
                    if(isset($_REQUEST['hpay_local_request']) && $_REQUEST['hpay_local_request']){
                        http_response_code(200);
                        echo json_encode(array("received" => "NO", "accept_result" => "REFUSED"));
                        exit;
                    }else{
                        http_response_code(302);
                        header("Location: " . HolestPayLib::libConfig()["site_cart_url"]);
                        die();
                    }

                }
            }catch(Throwable $ex){
                HolestPayLib::writeLog("error",$ex->getMessage(),7);
            }
            $this->webHooksHandler();
        }
        $this->_webResultHandlerCalled = true;
    }
    
    public function webHooksHandler(){
        if(!$this->_webHooksHandlerCalled){
            try{
                header("Content-Type:application/json");
                $data = json_decode( file_get_contents('php://input'), true);
                $cfg = HolestPayLib::libConfig();
                $topic = "";
                $order_uid = "";
                $m_ts = intval(microtime(true));
                $time = date("YmdHis");

                global $__hpay_active_r_file;
                

                if(isset($_GET["topic"])){
                    if($_GET["topic"]){
                        $topic = $_GET["topic"];
                    }
                }

                if(isset($_GET["order_uid"])){
                    if($_GET["order_uid"]){
                        $order_uid = $_GET["order_uid"];
                    }
                }

                if($cfg['log_enabled'] && $cfg['log_debug']){
                    
                    if($order_uid){
                        $__hpay_active_r_file = "wh" . $time . "_{$order_id}_" . ($topic ?? "unknown") . "_" . $m_ts;
                    }else{
                        $__hpay_active_r_file = "wh" . $time . "_" . ($topic ?? "unknown") . "_" . $m_ts;
                    }
                    
                    HolestPayLib::writeLog($__hpay_active_r_file, array(
                        "request_url" => $_SERVER["REQUEST_URI"],
                        "data" => $data
                    ));
                }

                if($topic){
                    if($topic == "payresult"){

                    }else if($topic == "orderupdate"){

                        if($order_uid){
                            $res = $this->onOrderUpdate($data);

                            if($__hpay_active_r_file){
                                HolestPayLib::writeLog($__hpay_active_r_file,array(
                                    "site_result" => $order_uid,
                                    "result" => $res
                                ));
							}
							
							if($res["success"]){
                                http_response_code(200);
								echo json_encode(array("received" => "OK", "accept_result" => "ACCEPTED", "info" => $res));
							}else{
                                http_response_code(406);
								echo json_encode(array("rejected" => $res, "error" => "REJECTED", "error_code" => 406));
							}
							die;

                        }else{
                            http_response_code(406);
                            echo json_encode(array("error" => "order_uid not provided" , "error_code" => 406));
                            die;
                        }
                    }else if($topic == "posconfig-updated"){

                    }else if($topic == "pos-error-logs"){

                    }else{
                        http_response_code(200);
                        echo json_encode(array("received" => date("Y-m-d H:i:s"), "error" => "NOT_HANDLED" , "ts" => time() , "topic" => $_GET["topic"]));
                        die;
                    }
                }
                http_response_code(406);
                echo json_encode(array("error" => "BAD DATA" , "error_code" => 406));
                die;
            }catch(Throwable $ex){
                HolestPayLib::writeLog("error",$ex->getMessage(),7);
                http_response_code(500);
                echo json_encode(array("error" => "ERROR" , "error_code" => 500));
                die;
            }
        }
        $this->_webHooksHandlerCalled = true;
    } 

    /**
     * returns current HPay site configuration from local data provider storage. Security parameters & POS configuration is obtained from HPay panel on connect, POS updates are received by site via web-hook when you update POS on HPay panel. Local copy is stored with (data provider)->setSiteConfiguration($hsite_configuration)
     * @param bool $reload - forces re-reading from local data provider storage
     * @return assoc_array - current HPay site configuration
     */
    public function getHSiteConfig($reload = false){
        if(!$reload && $this->_HSiteConfig){
            return $this->_HSiteConfig;
        }

        if(!isset(self::$_data_provider))
            return false;

        $this->_HSiteConfig = HolestPayLib::dataProvider()->loadSiteConfiguration();    

        return $this->_HSiteConfig;
    }

    /**
     * returns current HPay site parameter by name
     * @param string $name - name of parametar
     * @param mixed $default - default value
     * @return mixed - parameter value. If parameter is not set returns $default
     */
    public function getHSiteConfigParam($name, $default = null){
        $hsite_cfg = $this->getHSiteConfig();
        if($hsite_cfg){
            if(isset($hsite_cfg[$name])){
                return $hsite_cfg[$name];
            }else{
                return $default;
            }
        }
        return $default;
    }

    /**
     * returns current POS connection parameters
     * @return mixed - current connected POS connection paraneters
     */
    public function getPOSConnection(){
        $environment = $this->getHSiteConfigParam("environment");
        if(!$environment)
            return null;

        $pos_connection = $this->getHSiteConfigParam("{$environment}");  
        return $pos_connection;
    }

    /**
     * returns current POS connected to site configuration 
     * @return mixed - current connected POS config
     */
    public function getPOS(){
        $environment = $this->getHSiteConfigParam("environment");
        if(!$environment)
            return null;

        if($this->getHSiteConfigParam("{$environment}")){    
            $pos = $this->getHSiteConfigParam("{$environment}POS");  
        }

        return $pos;
    }

    /**
     * returns current POS connection parameter by name
     * @param string $name - name of parametar
     * @param mixed $default - default value
     * @return mixed - parameter value. If parameter is not set returns $default
     */
    public function getPOSConnectionParam($name, $default = null){
        $pos_connection = $this->getPOSConnection();
        if($pos_connection){
            if(isset($pos_connection[$name])){
                return $pos_connection[$name];
            }
        }
        return $default;
    }

    /**
     * returns current POS parameter by name 
     * @param string $name - name of parametar
     * @param mixed $default - default value
     * @return mixed - parameter value. If parameter is not set returns $default
     */
    public function getPOSParam($name, $default = null){
        $pos = $this->getPOS();
        if($pos){
            if(isset($pos[$name])){
                return $pos[$name];
            }
        }
        return $default;
    }

    /**
     * Gets HPay url for path 
     * @param string $path
     * @return string - URL for path
     */
    public function getHPayURL($path = ""){
		$url = $this->getHSiteConfigParam("environment",null) == "production" ? HPAY_PRODUCTION_URL : HPAY_SANDBOX_URL;
		if($path){
			return rtrim($url,"/") . "/" . ltrim($path,"/");	
		}
		return $url;
	}

    /**
     * Destroys connection data
     * @return assoc_array - current full HPay site configuration with false for connection property (that property is named as environment)
     */
    public function disconnectPOS(){
        return $this->setHSiteConfig(null, false, null);
    }

    /**
     * sets current HPay site environment and/or pos connection and/or pos configuration from data received on connect or when POS parameters are updated on HPay panel. If you pass null to any of arguments current value will be keept
     * @param string $environment - environment
     * @param assoc_array $pos_connection_params - contains parameters for connection. Crucial one is secret_token
     * @param assoc_array $pos - POS configuration as recived from HPay
     * @return assoc_array|false - current full HPay site configuration or false in provided value is invalid
     */
    private function setHSiteConfig($environment, $pos_connection_params, $pos){
        if(!$this->_HSiteConfig)
            $this->_HSiteConfig = $this->getHSiteConfig(true);

        if(!$this->_HSiteConfig){
            $this->_HSiteConfig = array();
        }

        if($environment){
            if(!in_array($environment,array("sandbox","production"))){
                HolestPayLib::writeLog("error","Invalid environment value!",7);
                return false;
            }
            $this->_HSiteConfig["environment"] = $environment;
        }

        if($pos_connection_params !== null){
            if(!$pos_connection_params){
                $this->_HSiteConfig[$this->_HSiteConfig["environment"]] = false;
            }else{
                if(is_object($pos_connection_params)){
                    $pos_connection_params = json_decode(json_encode($pos_connection_params),true);
                }else if(is_string($pos_connection_params)){
                    $pos_connection_params = json_decode($pos_connection_params,true);
                }

                if(!isset($pos_connection_params["secret_token"])){
                    HolestPayLib::writeLog("error","Invalid POS connection data. Missing secret_token!",7);
                    return false;
                }else if(!$pos_connection_params["secret_token"]){
                    HolestPayLib::writeLog("error","Invalid POS connection data. Empty secret_token!",7);
                    return false;
                }else{
                    $this->_HSiteConfig[$this->_HSiteConfig["environment"]] = $pos_connection_params;
                }

            }
        }

        if($pos){
            if(is_object($pos)){
                $pos = json_decode(json_encode($pos),true);
            }else if(is_string($pos)){
                $pos = json_decode($pos,true);
            }

            if(!isset($pos["MerchantsiteUid"])){
                HolestPayLib::writeLog("error","Invalid POS data. Missing MerchantsiteUid!",7);
                return false;
            }else if(!$pos["MerchantsiteUid"]){
                HolestPayLib::writeLog("error","Invalid POS data. Empty MerchantsiteUid!",7);
                return false;
            }else{
                $this->_HSiteConfig[$this->_HSiteConfig["environment"]."POS"] = $pos;
            }
        }

        if(!isset(self::$_data_provider)){
            HolestPayLib::writeLog("error","Data provider not set!",7);
            return false;
        }

        $this->_HSiteConfig = HolestPayLib::dataProvider()->setSiteConfiguration($this->_HSiteConfig);    

        return $this->_HSiteConfig;
    }

    /**
     * Gets current exchange rate $from -> $to
     * @param string $from - form currency EUR, USD, GBP...
     * @param string $to - to currentcy RSD, BAM, MKD...
     * @return float - rate
     */
    public static function getExchnageRate($from, $to){
        $from   = strtoupper(trim($from)); 
		$to     = strtoupper(trim($to)); 
		
		if($from == $to){
			return 1.00;	
		}

        global $__hpay_in_proccess_exc_rates;
        if(!isset($__hpay_in_proccess_exc_rates))
            $__hpay_in_proccess_exc_rates = array();

        if(isset($__hpay_in_proccess_exc_rates["{$from}{$to}"]))    
            return $__hpay_in_proccess_exc_rates["{$from}{$to}"];
		
		$cached = null;

        try{
            $cfg = HolestPayLib::libConfig();
            if(!$cfg){
                return null;
            }

            $cached = HolestPayLib::dataProvider()->readExchnageRate($from, $to);
           
            if($cached){
                if(is_array($cached)){
                    if(isset($cached["ts"]) && isset($cfg["exchange_rate_cache_h"])){
                        $h = intval($cfg["exchange_rate_cache_h"]);
                        if(!$h){
                            $h = 1;
                        }
                        if($cached["ts"] + $h * 3600 < time()){
                            $cached = null;
                        }
                    }
                }
            }

            if(!$cached){
                
                if(!isset($cfg["exchange_rate_source"])){
                    HolestPayLib::writeLog("error","exchange_rate_source - not configured");
                    return null;
                }

                $eurl = str_ireplace('{FROM}',$from,$cfg["exchange_rate_source"]);
                $eurl = str_ireplace('{TO}',$to,$eurl);

                $res = HolestPayLib::fetch($eurl, array(
                    "timeout" => 15
                ));

                if($res){
                    if( substr("{$res->status}",0,1) == "2"){
                        if(floatval($res->raw)){
                            $cached = array(
                                "rate" => floatval($res->raw),
                                "ts" => time()
                            );
                        }else
                            $cached = $res->json();
                            
                        if($cached){
                            if(!isset($cached["rate"])){
                                $cached = null;
                            }else if(!$cached["rate"]){
                                $cached = null;
                            }
                        }
                        if($cached){
                            HolestPayLib::dataProvider()->cacheExchnageRate($from, $to, $cached["rate"],$cached["ts"]);
                        }
                    }
                }
            }
            
        }catch(Throwable $ex){
            HolestPayLib::writeLog("error",$ex->getMessage(),5);
        }

        if($cached){
            $__hpay_in_proccess_exc_rates["{$from}{$to}"] = $cached["rate"];
            return $cached["rate"];
        }
         
        return null;
    }

    /**
     * Gets current exchange rate $from -> $to but woth respect to exchange rate correction set for POS
     * @param string $from - form currency EUR, USD, GBP...
     * @param string $to - to currentcy RSD, BAM, MKD...
     * @return float - rate
     */
    public static function getMerchantExchnageRate($from, $to){
		$rate = HolestPayLib::getExchnageRate($from, $to);
		if($rate === null){
			return $rate;
		}
		
		$ExchanageCorrection = floatval(HolestPayLib::instance()->getPOSParam("ExchanageCorrection",0));
		if($ExchanageCorrection){
			$rate = $rate * (1.00 + ($ExchanageCorrection/100));
		}
		return $rate;
	}

    /**
     * reads array of all results received from HolestPay in the chronological order. Result may or may not contain payment transaction. You can have your custom storage for them but it is important to have one field that may accept full result JSON (so at least mediumtext if db is used) 
     * @param string $order_uid - order unique identifikator
     * @return array array( [{ ...result: ... },...] )
     */
    public function getResultsForOrder($order_uid){
        return HolestPayLib::dataProvider()->getResultsForOrder($order_uid);
    }

    /**
     * gets current user displayable payment information for order as HTML. Empty if does not exists 
     * 
     * @param string $order_uid - order unique identifikator
     * @return string - current HTML for payment info. Empty if nothing exists 
     */
    public function getPaymentResponseHTML($order_uid){
        return HolestPayLib::dataProvider()->getPaymentResponseHTML($order_uid);
    }

    /**
     * gets current user displayable fiscal or integration methods information (for mutiple methods output is combined) for order as HTML. Empty if nothing exists 
     * 
     * @param string $order_uid - order unique identifikator
     * @return string - current HTML for fiscal or integration methods information info. Empty if nothing exists
     */
    public function getFiscalOrIntegrationResponseHTML($order_uid){
        return HolestPayLib::dataProvider()->getFiscalOrIntegrationResponseHTML($order_uid);
    }

    /**
     * gets current user displayable shipping methods information (for mutiple methods output is combined) for order as HTML. Empty if nothing exists 
     * 
     * @param string $order_uid - order unique identifikator
     * @return string- current HTML for shipping methods information info. Empty if nothing exists
     */
     public function getShippingResponseHTML($order_uid){
        return HolestPayLib::dataProvider()->getShippingResponseHTML($order_uid);
     }

    /**
     * gets HPay status. HPay status is composed of payment status and statuses for all fiscal, integration and shipping metods. By default its string, but you may get it as array is you set second prameter as true. In that case you will get array like this array("PAYMENT" => "--PAYMENT_STATUS--", "FISCAL" => array("method1_uid" => array("status1" => "status1_val"), "SHIPPING" => array("method1_uid" => array("status1" => "status1_val"))  ). See hpay status specification ib readme.MD
     * @param string $order_uid - order unique identifikator
     * @param array $as_array - parse reurn value as array
     * @return string|assoc_array - HPAY status as string or prased if $as_array == true. If parsed reurned array will always have "PAYMENT","FISCAL" and "SHIPPING" keys. If there is nothing their value willl be null 
     */
    public function getOrderHPayStatus($order_uid, $as_array = false){
        return HolestPayLib::dataProvider()->getOrderHPayStatus($order_uid, $as_array);
    }

    /**
     * returns site language
     * @return string - language, should be 2 lowercase letters language code like 'rs','en','de','mk','el'... 
     */
    public function getLanguage(){
        return HolestPayLib::dataProvider()->getLanguage();
    }

    /**
     * returns site currency
     * @return string - currency like RSD, EUR, MKD, BAM, USD, CHF, GBP... 
     */
    public function getCurrency(){
        return HolestPayLib::dataProvider()->getCurrency();
    }

    /**
     * extracts only HPay PAY status form full HPay status
     * @param string $order_uid - order unique identifikator
     * @param string $full_status_string - pass full HPay status directly
     * @return string HPAY PAY status
     */
    public function getOrderHPayPayStatus($order_uid, $full_status_string = null){
        $hstatus = $full_status_string !== null ? $full_status_string : $this->getOrderHPayStatus($order_uid, false);
        if(stripos($hstatus,"PAYMENT:") !== false){
            $hstatus = trim($hstatus);
            $hstatus = str_replace("  "," ",$hstatus);
            $hstatus = str_replace("  "," ",$hstatus);
            $hstatus = explode("PAYMENT:",$hstatus);
            $hstatus = $hstatus[1];
            $hstatus = explode(" ",$hstatus);
            $hstatus = $hstatus[0];
            return trim($hstatus);
        }
        return "";
    }

    /**
     * extracts only HPay FISCAL&INTEGRATIOS status form full HPay status
     * @param string $order_uid - order unique identifikator
     * @param string $full_status_string - pass full HPay status directly
     * @return assoc_array - array("method1_uid" => method1_status ...)
     */
    public function getOrderHPayFiscalAndIntegrationStatus($order_uid, $full_status_string = null){
        $hstatus = $full_status_string !== null ? $full_status_string : $this->getOrderHPayStatus($order_uid, false);
        if(stripos($hstatus,"_FISCAL:") !== false){
            $hstatus = trim($hstatus);
            $hstatus = str_replace("  "," ",$hstatus);
            $hstatus = str_replace("  "," ",$hstatus);
            $hstatus = explode(" ",$hstatus);
            $fi_stat = array();
            foreach($hstatus as $tstat){
                if(stripos($tstat,"_FISCAL:") !== false){
                    $tstat = explode("_FISCAL:",$tstat);
                    $fi_stat[trim($tstat[0])] = trim($tstat[1]);
                }
            }
            return $fi_stat;
        }
        return array();
    }

    /**
     * extracts only HPay SHIPPING status form full HPay status
     * @param string $order_uid - order unique identifikator
     * @param string $full_status_string - pass full HPay status directly
     * @return assoc_array - array("method1_uid" => array( "packet1_code" => packet1_status) ...)
     */
    public function getOrderHPayShippingStatus($order_uid, $full_status_string = null){
       $hstatus = $full_status_string !== null ? $full_status_string : $this->getOrderHPayStatus($order_uid, false);
        if(stripos($hstatus,"_SHIPPING:") !== false){
            $hstatus = trim($hstatus);
            $hstatus = str_replace("  "," ",$hstatus);
            $hstatus = str_replace("  "," ",$hstatus);
            $hstatus = explode(" ",$hstatus);
            $s_stat = array();
            foreach($hstatus as $tstat){
                if(stripos($tstat,"_SHIPPING:") !== false){
                    $tstat = explode("_SHIPPING:",$tstat);
                    $s_stat[trim($tstat[0])] = array();
                    $tstat[1] = trim($tstat[1]);
                    $tstat[1] = explode(",",$tstat[1]);
                    foreach($tstat[1] as $packet){
                        if(strpos($packet,"@") !== false){
                            $packet = explode("@",$packet);
                            $s_stat[trim($tstat[0])][trim($packet[0])] = trim($packet[1]);
                        }
                    }
                }
            }
            return $s_stat;
        }
        return array();
    }

    /**
     * Parses HPay status string to assoc array
     * @param string $hpay_status - full hpay status 
     * @return array - assoc array
     */
    public function parseHStatus($hpay_status){
        return array(
            "PAYMENT"  => $this->getOrderHPayPayStatus(null, $hpay_status),
            "FISCAL"   => $this->getOrderHPayFiscalAndIntegrationStatus(null, $hpay_status),
            "SHIPPING" => $this->getOrderHPayShippingStatus(null, $hpay_status)
        );
    }

    /**
     * Serialized HPay status in assoc array data structure to full HPay status string
     * @param assoc_array - HPay status in assoc array form
     * @return string HPay status in string form 
     */
    public function serializeHStatus($status_data){
        $status_full = "";
        if($status_data){
            if(isset($status_data["PAYMENT"])){
                $status_full = "PAYMENT:" . $status_data["PAYMENT"];
            }

            if(isset($status_data["FISCAL"])){
                foreach($status_data["FISCAL"] as $method_uid => $f_status){
                    if($f_status){
                        if($status_full)
                            $status_full .= " ";
                        $status_full .= "{$method_uid}_FISCAL:{$f_status}";
                    }
                }
            }

            if(isset($status_data["SHIPPING"])){
                foreach($status_data["SHIPPING"] as $method_uid => $packets){
                    if(!empty($packets)){
                        if($status_full)
                            $status_full .= " ";
                        $status_full .= "{$method_uid}_SHIPPING:";
                        $p_and_s = array();
                        foreach($packets as $packet_code => $packet_status){
                            $p_and_s[] = "{$packet_code}@{$packet_status}";
                        }
                        $status_full .= implode(",",$p_and_s);
                    }
                }
            }
        }
        return $status_full;
    }

    /**
     * Conbines new status updates with existing ones and calculates new HPay status string making sure no information is lost. Normaly you don't combine statuses yourself.
     * @param string $status - existing status
     * @param string $new_status - new status
     * @return HPay status in string form 
     */
    public function mergeHPayStatus($status, $new_status){
        if(!$new_status){
            if(!$status)
                return "";
            return is_string($status) ? $status : $this->serializeHStatus($status);
        }

        if(!$status)
            $status = array();
        else if(is_string($status)){
            $status = $this->parseHStatus($status);
        }else{
            $status = json_decode(json_encode($status),true);//MAKE A COPY!
        }

        if(is_string($new_status)){
            $new_status = $this->parseHStatus($new_status);
        }

        foreach($new_status as $what => $sval){
            if($what == "PAYMENT"){
                if($sval)
                    $status["PAYMENT"] = $sval;
            }else{
                if($new_status[$what] && !empty($new_status[$what]))
                    $status[$what] = array_merge($status[$what],$new_status[$what]);
            }
        }
        return $status;
    }

    /**
     * sets to HPay status for order. HPay status is composed of payment status and statuses for all fiscal, integration and shipping metods. By default all is placed in single string. You can pass assoc_array for value to indicate only update of "PAYMENT","FISCAL" and "SHIPPING" part like array("PAYMENT" => "PAID"). Function needs to preseve all previous and just add ou update statuses (once added status for anything can not just dissapear).  
     * @param string $order_uid - order unique identifikator
     * @param strinh|assoc_array $hpay_status - full hpay_status as string in its format. Partial status as string for payment or/and fiscal or/and integration or/and shipping metods. Once ste status for some method can not dissapear it can only change value.
     * @return string - full HPAY status in string form for order in HPay status format
     */
    public function setOrderHPayStatus($order_uid, $hpay_status){
        return HolestPayLib::dataProvider()->setOrderHPayStatus($order_uid, $hpay_status);
    }
    
    /**
     * gets HPay order in HolestPay format eather from $order_uid or full site order object 
     * @param string|Order $order_uid_or_site_order - $order_uid to read from data storage or full order object from site to convert to HPay Order
     * @return assoc_array - HPAY Order
     */
    public function getHOrder($order_uid_or_site_order){
        return HolestPayLib::dataProvider()->getHOrder($order_uid_or_site_order);
    }
    
    /**
     * gets HPay cart in HolestPay format eather from $order_uid or full site order or chart object 
     * @param string|Order|Cart $order_uid_or_site_order_or_site_cart - $order_uid to read from data storage or full order object from site to convert to HPay Cart or site Cart object to HPay Cart
     * @return assoc_array - HPAY Order
     */  
    public function getHCart($order_uid_or_site_order_or_site_cart){
        return HolestPayLib::dataProvider()->getHCart($order_uid_or_site_order_or_site_cart);
    }

    /**
    * gets array of vault references for user to be used for charge or presented user to choose from. $user_uid is usually email. 
    * @param string $user_uid - user identifier / usually email
    * @return assoc_array - vault reference data. Basides value it ,may contain masked pan, last use time, method for which its valid for
    */ 
    public function getVaultReferences($user_uid){
        return HolestPayLib::dataProvider()->getVaultReferences($user_uid);
    }

    /**
     * adds vault references for user to be used for future charges. $user_uid is usually email.
    * @param string $vault_token_uid - user identifier / usually email
    * @param assoc_array - vault reference data. Basides value it ,may contain masked pan, last use time, method for which its valid for 
    * @return bool - true on success , false on failure
    */  
    public function addVaultReference($user_uid, $vault_data){
        return HolestPayLib::dataProvider()->addVaultReference($user_uid, $vault_data);
    }
    
    /**
    * removes vault reference by its value 
    * @param string $user_uid - user identifier / usually email
    * @param string $vault_token_uid - value of vault reference pointer itself
    * @return bool - true on real delete happened, otherwise false
    */  
    public function removeVaultReference($user_uid, $vault_token_uid){
        return HolestPayLib::dataProvider()->removeVaultReference($user_uid, $vault_token_uid);
    }
    
    /**
     * updates vault reference by its value 
    * @param string $user_uid - user identifier / usually email
    * @param string $vault_token_uid - value of vault reference pointer itself
    * @param assoc_array $vault_data - vault reference data. Basides value it ,may contain masked pan, last use time, method for which its valid for
    * @return bool - true on success, false on failure
    */  
     public function updateVaultReference($user_uid,$vault_token_uid, $vault_data){
        return HolestPayLib::dataProvider()->updateVaultReference($user_uid,$vault_token_uid, $vault_data);
     }
 
     /**
      * Merges newly arrived HTML response (may be payment, shipping and fiscal&integration output) to existing one 
      * @param string $new_output - new HTML result for all payment or new HTML result for all shipping and new HTML result for all fiscal&integration 
      * @param string $existing_output - previous HTML result output from data storage
      * @return string merged output HTML 
      */
     private function mergeMethodsHTMLOutputs($new_output, $existing_output){
		if(!trim($existing_output))
			return $new_output;
		
		$new_arr      = explode("<!-- METHOD_HTML_START:",$new_output);
		$existing_arr = explode("<!-- METHOD_HTML_START:",$existing_output);
		
		$new_dict      = array();
		$existing_dict = array(); 
		
		foreach($new_arr as $msection){
			if(stripos($msection,'<!-- METHOD_HTML_END') === false){
				continue;
			}
			
			$muid = explode(" -->",substr($msection,0,128));
			$muid = trim($muid[0]);
			$new_dict[$muid] = "<!-- METHOD_HTML_START:" . $msection;
		}
		
		foreach($existing_arr as $msection){
			if(stripos($msection,'<!-- METHOD_HTML_END') === false){
				continue;
			}
			
			$muid = explode(" -->",substr($msection,0,128));
			$muid = trim($muid[0]);
			$existing_dict[$muid] = "<!-- METHOD_HTML_START:" . $msection;
		}
		
		foreach($new_dict as $muid => $msection){
			$existing_dict[$muid] = $msection;
		}
		
		$html = "";
		
		foreach($existing_dict as $muid => $msection){
			$html .= ("\n" . $msection);
		}
		
		return $html;
	}

    private function onOrderUpdate($resp, $order = null){

    }

    private function acceptResult($order, $result, $pmethod_id = null, $is_webhook = false){
        
    }

    

}
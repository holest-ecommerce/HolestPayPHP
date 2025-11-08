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
                        $order = $this->getHOrder($result["order_uid"]);
                        if(isset($result["payment_method"])){
                            $hmethod = $this->getPaymentMethod($result["payment_method"]);
                        }

                        if(!$hmethod && $order && @$order["payment_method"]){
                            $hmethod = $this->getPaymentMethod($order["payment_method"]);
                        }

                        if(!$hmethod){
                            $hmths = $this->getPaymentMethods(true);
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

                                    $return_url = HolestPayLib::urlAddQSparam($return_url, "user_order_uid", $result["order_uid"]);
                                   
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
                        $__hpay_active_r_file = "wh" . $time . "_{$order_uid}_" . ($topic ?? "unknown") . "_" . $m_ts;
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
                        if($order_uid){
                            $pmethod_id = null;
							$hmethod    = null;
							
							if(isset($_GET["pos_pm_id"])){
								$pmethod_id = $_GET["pos_pm_id"];
								$hmethod    = $this->getPaymentMethod($pmethod_id);
                                if($hmethod){
                                    $pmethod_id = $hmethod["HPaySiteMethodId"];
                                }
							}
							
							
							$order = $this->getHOrder($order_uid);
							if($order){
								
								$res = null;
								$res = $this->acceptResult($order, $data, $pmethod_id, true);
								
								if($__hpay_active_r_file){
                                    HolestPayLib::writeLog($__hpay_active_r_file,array(
                                        "site_result" => "payresult",
                                        "result" => $res
                                    ));
								}

								$return_url = HolestPayLib::libConfig()["site_url"];
								if(isset($order["order_user_url"]) && $order["order_user_url"]){
									$return_url = $order["order_user_url"];
								}else if(@HolestPayLib::libConfig()["order_user_url"]){
									$return_url = @HolestPayLib::libConfig()["order_user_url"];
								}
								$return_url = HolestPayLib::urlAddQSparam($return_url, "user_order_uid", $order_uid);

    							if($res === true){
								    http_response_code(200);
									echo json_encode(array("received" => "OK", "accept_result" => "ACCEPTED", "order_user_url" => $return_url));
								}else{
									http_response_code(406);
									echo json_encode(array("rejected" => $res, "error" => "REJECTED", "error_code" => 406, "order_user_url" => $return_url));
								}
								die;
							}

                            http_response_code(404);
							echo json_encode(array("received" => "NO", "accept_result" => "NOT RECOGNISED", "error_code" => 404, "rdiff" => rand(100000,999999)));
							die;

                        }else{
                            http_response_code(406);
                            echo json_encode(array("error" => "order_uid not provided" , "error_code" => 406));
                            die;
                        }
                    }else if($topic == "orderupdate"){

                        if($order_uid){
                            $res = $this->onOrderUpdate($data);

                            if($__hpay_active_r_file){
                                HolestPayLib::writeLog($__hpay_active_r_file,array(
                                    "site_result" => "orderupdate",
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

                        if(isset($data["environment"]) && isset($data["merchant_site_uid"]) && isset($data["POS"]) && isset($data["checkstr"])){
							
							if($data["environment"] == $this->getHSiteConfigParam("environment")){
								if($data["merchant_site_uid"] == $this->getPOSConnectionParam("merchant_site_uid")){
									if($data["checkstr"] == md5($this->getPOSConnectionParam("merchant_site_uid") . $this->getPOSConnectionParam("secret_token"))){
										$this->setHSiteConfig(null, null, $data["POS"]);
                                        http_response_code(200);
										echo json_encode(array("received" => "OK", "accept_result" => "POS_CONFIG_UPDATED"));
										die;
									}	
								}
							}
						}
                        http_response_code(406);
						echo json_encode(array("rejected" => 1, "error" => "REJECTED", "error_code" => 406));
						die;

                    }else if($topic == "pos-error-logs"){
                        if(isset($data["environment"]) && isset($data["merchant_site_uid"]) && isset($data["POS"]) && isset($data["checkstr"])){
							
							if($data["environment"] == $this->getHSiteConfigParam("environment")){
								if($data["merchant_site_uid"] == $this->getPOSConnectionParam("merchant_site_uid")){
									if($data["checkstr"] == md5($this->getPOSConnectionParam("merchant_site_uid") . $this->getPOSConnectionParam("secret_token"))){
										
                                        http_response_code(200);
										echo json_encode(array("received" => "OK", "logs" => HolestPayLib::logProvider()->get_error_logs() ));
										die;

									}	
								}
							}
						}
                        http_response_code(406);
						echo json_encode(array("rejected" => 1, "error" => "REJECTED", "error_code" => 406));
						die;
                    }else{
                        http_response_code(200);
                        echo json_encode(array("received" => date("Y-m-d H:i:s"), "error" => "NOT_HANDLED" , "ts" => time() , "topic" => $topic));
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
     * @return array (assoc) - current HPay site configuration
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
     * @return array (assoc) - current full HPay site configuration with false for connection property (that property is named as environment)
     */
    public function disconnectPOS(){
        return $this->setHSiteConfig(null, false, null);
    }

    /**
     * sets current HPay site environment and/or pos connection and/or pos configuration from data received on connect or when POS parameters are updated on HPay panel. If you pass null to any of arguments current value will be keept
     * @param string $environment - environment
     * @param array (assoc) $pos_connection_params - contains parameters for connection. Crucial one is secret_token
     * @param array (assoc) $pos - POS configuration as recived from HPay
     * @return array (assoc)|false - current full HPay site configuration or false in provided value is invalid
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
     * gets HPay status. HPay status is composed of payment status and statuses for all fiscal, integration and shipping metods. By default its string, but you may get it as array is you set second prameter as true. In that case you will get array like this array("PAYMENT" => "--PAYMENT_STATUS--", "INTEGR" => array("method1_uid" => array("status1" => "status1_val"), "FISCAL" => array("method1_uid" => array("status1" => "status1_val"), "SHIPPING" => array("method1_uid" => array("status1" => "status1_val"))  ). See hpay status specification ib readme.MD
     * @param string $order_uid - order unique identifikator
     * @param array $as_array - parse reurn value as array
     * @return string|array (assoc) - HPAY status as string or prased if $as_array == true. If parsed reurned array will always have "PAYMENT","FISCAL","INTEGR" and "SHIPPING" keys. If there is nothing their value willl be null 
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
     * extracts only HPay FISCAL status form full HPay status
     * @param string $order_uid - order unique identifikator
     * @param string $full_status_string - pass full HPay status directly
     * @return array (assoc) - array("method1_uid" => method1_status ...)
     */
    public function getOrderHPayFiscalStatus($order_uid, $full_status_string = null){
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
     * extracts only HPay INTEGRATIOS status form full HPay status
     * @param string $order_uid - order unique identifikator
     * @param string $full_status_string - pass full HPay status directly
     * @return array (assoc) - array("method1_uid" => method1_status ...)
     */
    public function getOrderHPayIntegrationsStatus($order_uid, $full_status_string = null){
        $hstatus = $full_status_string !== null ? $full_status_string : $this->getOrderHPayStatus($order_uid, false);
        if(stripos($hstatus,"_INTEGR:") !== false){
            $hstatus = trim($hstatus);
            $hstatus = str_replace("  "," ",$hstatus);
            $hstatus = str_replace("  "," ",$hstatus);
            $hstatus = explode(" ",$hstatus);
            $fi_stat = array();
            foreach($hstatus as $tstat){
                if(stripos($tstat,"_INTEGR:") !== false){
                    $tstat = explode("_INTEGR:",$tstat);
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
     * @return array (assoc) - array("method1_uid" => array( "packet1_code" => packet1_status) ...)
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
            "FISCAL"   => $this->getOrderHPayFiscalStatus(null, $hpay_status),
			"INTEGR"   => $this->getOrderHPayIntegrationsStatus(null, $hpay_status),
            "SHIPPING" => $this->getOrderHPayShippingStatus(null, $hpay_status)
        );
    }

    /**
     * Serialized HPay status in assoc array data structure to full HPay status string
     * @param array (assoc) - HPay status in assoc array form
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
			
			if(isset($status_data["INTEGR"])){
                foreach($status_data["INTEGR"] as $method_uid => $f_status){
                    if($f_status){
                        if($status_full)
                            $status_full .= " ";
                        $status_full .= "{$method_uid}_INTEGR:{$f_status}";
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
     * sets to HPay status for order. HPay status is composed of payment status and statuses for all fiscal, integration and shipping metods. By default all is placed in single string. You can pass array (assoc) for value to indicate only update of "PAYMENT","FISCAL","INTEGR" and "SHIPPING" part like array("PAYMENT" => "PAID"). Function needs to preseve all previous and just add ou update statuses (once added status for anything can not just dissapear).  
     * @param string $order_uid - order unique identifikator
     * @param strinh|array (assoc) $hpay_status - full hpay_status as string in its format. Partial status as string for payment or/and fiscal or/and integration or/and shipping metods. Once ste status for some method can not dissapear it can only change value.
     * @return string - full HPAY status in string form for order in HPay status format
     */
    public function setOrderHPayStatus($order_uid, $hpay_status){
        return HolestPayLib::dataProvider()->setOrderHPayStatus($order_uid, $hpay_status);
    }
    
    /**
     * gets HPay order in HolestPay format eather from $order_uid or full site order object 
     * @param string|Order $order_uid_or_site_order - $order_uid to read from data storage or full order object from site to convert to HPay Order
     * @return array (assoc) - HPAY Order
     */
    public function getHOrder($order_uid_or_site_order){
        return HolestPayLib::dataProvider()->getHOrder($order_uid_or_site_order);
    }
    
    /**
     * gets HPay cart in HolestPay format eather from $order_uid or full site order or chart object 
     * @param string|Order|Cart $order_uid_or_site_order_or_site_cart - $order_uid to read from data storage or full order object from site to convert to HPay Cart or site Cart object to HPay Cart
     * @return array (assoc) - HPAY Order
     */  
    public function getHCart($order_uid_or_site_order_or_site_cart){
        return HolestPayLib::dataProvider()->getHCart($order_uid_or_site_order_or_site_cart);
    }

    /**
    * gets array of vault references for user to be used for charge or presented user to choose from. $user_uid is usually email. 
    * @param string $user_uid - user identifier / usually email
    * @return array (assoc) - vault reference data. Basides value it ,may contain masked pan, last use time, method for which its valid for
    */ 
    public function getVaultReferences($user_uid){
        return HolestPayLib::dataProvider()->getVaultReferences($user_uid);
    }

    /**
     * adds vault references for user to be used for future charges. $user_uid is usually email.
    * @param string $vault_token_uid - user identifier / usually email
    * @param array (assoc) - vault reference data. Basides value it ,may contain masked pan, last use time, method for which its valid for 
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
    * @param array (assoc) $vault_data - vault reference data. Basides value it ,may contain masked pan, last use time, method for which its valid for
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

	/**
	 * HPay tries to deliver result to your site in few ways. To prevent result processing at the same time at once use this method to only let first arrived request for same result to be accepted
	 * @param string $order_uid - order unique identifikator
	 * @return - true on successful locking otherwise false. If false abandon further execution!
	 */
	public function lockOrderUpdate($order_uid){
		return HolestPayLib::dataProvider()->lockOrderUpdate($order_uid);
	}

	/**
	* HPay tries to deliver result to your site in few ways. To prevent result processing at the same time at once use this method to unlock order updates after you successfully accepted result
	* @param string $order_uid - order unique identifikator
	* @return bool - true on successful unlocking otherwise false. 
	*/
	public function unlockOrderUpdate($order_uid){
		return HolestPayLib::dataProvider()->unlockOrderUpdate($order_uid);
	}

	/**
	* HPay tries to deliver result to your site in few ways. If result has already been accepted you don't need to accept it again. You use md5(verificationhash) or md5(vhash) to get unique result identification. See hpay status specification ib readme.MD
	* @param string $result_md5_hash. Usualy calculated as md5(verificationhash) or md5(vhash)
	* @return bool - true if result was already accepted otherwise false.  
	*/
	public function resultAlreadyReceived($order_uid, $result_md5_hash){
		return HolestPayLib::dataProvider()->resultAlreadyReceived($order_uid, $result_md5_hash);
	}

	/**
	 * Accepts and writes fisacal&integration and shipping data for order
	 * @param string $order_uid - order unique identifikator
	 * @param array $resp (assoc) - response data that is beeing accepted 
	 * @return boolean - true is something is updated, otherwise false
	 */
	private function acceptResponseFiscalAndShipping($order_uid, & $resp){
		
		if(!$resp)
			return false;

		$something_updated = false;	
		
		if(isset($resp["fiscal_user_info"])){
			//MAY BE SINGLE OR ARRAY!
			
			if(!array_is_list($resp["fiscal_user_info"])){
				$resp["fiscal_user_info"] = array($resp["fiscal_user_info"]);
			}
			
			$fiscal_user_info = HolestPayLib::dataProvider()->getFiscalOrIntegrationData($order_uid);

			if(empty($fiscal_user_info)){
				HolestPayLib::dataProvider()->writeFiscalOrIntegrationData($order_uid,$resp["fiscal_user_info"]);
				$something_updated = true;
			}else{
				
				if(!array_is_list($fiscal_user_info)){
					$fiscal_user_info = array($fiscal_user_info);
				}

				$fmethods_existing = array();
				foreach($fiscal_user_info as $index => $fi){
					if(isset($fi["method_uid"])){
						$fmethods_existing[$fi["method_uid"]] = $index;
					}else{
						$fmethods_existing[""] = $index;
					}
				}
				
				foreach($resp["fiscal_user_info"] as $fi){
					$method_uid = "";
					if(isset($fi["method_uid"])){
						$method_uid = $fi["method_uid"];
					}
					if(isset($fmethods_existing[$method_uid])){
						$fiscal_user_info[$fmethods_existing[$method_uid]] = $fi;
					}else{
						$fiscal_user_info[] = $fi;
					}
				}
				
				HolestPayLib::dataProvider()->writeFiscalOrIntegrationData($order_uid,$fiscal_user_info);
				$something_updated = true;
			}
		}
		
		if(isset($resp["shipping_user_info"])){
			//MAY BE SINGLE OR ARRAY!
			if(!array_is_list($resp["shipping_user_info"])){
				$resp["shipping_user_info"] = array($resp["shipping_user_info"]);
			}
			
			$shipping_user_info = HolestPayLib::dataProvider()->getShippingData($order_uid);

			if(empty($shipping_user_info)){
				HolestPayLib::dataProvider()->writeShippingData($order_uid,$resp["shipping_user_info"]);
				$something_updated = true;
			}else{
				
				if(!array_is_list($shipping_user_info)){
					$shipping_user_info = array($shipping_user_info);
				}
				
				$smethods_existing = array();
				foreach($shipping_user_info as $index => $fi){
					if(isset($fi["method_uid"])){
						$smethods_existing[$fi["method_uid"]] = $index;
					}else{
						$smethods_existing[""] = $index;
					}
				}
				
				foreach($resp["shipping_user_info"] as $fi){
					$method_uid = "";
					if(isset($fi["method_uid"])){
						$method_uid = $fi["method_uid"];
					}
					if(isset($smethods_existing[$method_uid])){
						$shipping_user_info[$smethods_existing[$method_uid]] = $fi;
					}else{
						$shipping_user_info[] = $fi;
					}
				}
				
				HolestPayLib::dataProvider()->writeShippingData($order_uid,$shipping_user_info);
				$something_updated = true;
			}
		}
		
		$existing_fhtml = HolestPayLib::dataProvider()->getFiscalOrIntegrationResponseHTML($order_uid);
		$existing_shtml = HolestPayLib::dataProvider()->getShippingResponseHTML($order_uid);
		
		if(!$existing_fhtml){
			$existing_fhtml = "";
		}
		
		if(!$existing_shtml){
			$existing_shtml = "";
		}
		
		if(isset($resp["fiscal_user_info"])){
			unset($resp["fiscal_user_info"]);
		}
		
		if(isset($resp["shipping_user_info"])){
			unset($resp["shipping_user_info"]);
		}
		
		
		if(isset($resp["fiscal_html"])){
			$merged = $this->mergeMethodsHTMLOutputs($resp["fiscal_html"], $existing_fhtml);
			HolestPayLib::dataProvider()->writeFiscalOrIntegrationResponseHTML($order_uid, $merged);
			unset($resp["fiscal_html"]);
			$something_updated = true;
		}
		
		if(isset($resp["shipping_html"])){
			$merged = $this->mergeMethodsHTMLOutputs($resp["shipping_html"], $existing_shtml);
			HolestPayLib::dataProvider()->writeFiscalOrIntegrationResponseHTML($order_uid, $merged);
			unset($resp["shipping_html"]);
			$something_updated = true;
		}
		
		return $something_updated;
	}

    private function onOrderUpdate($resp, $order = null){

		global $hpay_doing_order_update;
		
		$hpay_doing_order_update = true;
		
		try{
		
			if(!$resp){
				$hpay_doing_order_update = false;
				return array(
					'success' => false,
					'message' => HolestPayLib::__('EMPTY_OUTCOME_DATA')
				);
			}
			
			if(isset($resp["result"])){
				if(is_string($resp["result"])){
					unset($resp["result"]);
				}
			}
			
			if(!isset($resp["status"]) || !isset($resp["order_uid"])){
				$hpay_doing_order_update = false;
				return array(
					'success' => false,
					'message' => HolestPayLib::__('BAD_ORDER_DATA')
				);
			}
			
			$order_uid = null;
			if(isset($resp["order_uid"])){
				$order_uid = $resp["order_uid"];
			}

			if($this->verifyResponse($resp)){
				$reshash = null;
				if(isset($resp["vhash"])){
					if($resp["vhash"])
						$reshash = md5($resp["vhash"]);
				}
				
				

				if(!$order && $order_uid){
					$order = $this->getHOrder($order_uid);
				}
				
				if(!$order){
					//OVDE PREDVIDETI KREIRANJE
					$hpay_doing_order_update = false;
					return array(
						'success' => false,
						'message' => HolestPayLib::__('ORDER_ID_NOT_FOUND')
					);
				}else{
					
					if(!$this->lockHOrderUpdate($order_uid)){
						if($order){
							return array(
								'success'           => false,
								'message'           => HolestPayLib::__('CANNOT_GET_ORDER_LOCK'),
								"order_id"          => $order_uid,
								"order_site_status" => $this->getOrderHPayStatus($order_uid)
							);
						}else{
							return array(
								'success' => false,
								'message' => HolestPayLib::__('CANNOT_GET_ORDER_LOCK')
							);
						}
					}
					
					$already_received = $this->resultAlreadyReceived($order_uid, $reshash);
					if($already_received === true){
						//other process that first started to handle result is still execting
						sleep(2);
						$already_received = $this->resultAlreadyReceived($order_uid, $reshash);
					}
					
					$hpay_responses          = $this->getResultsForOrder($order_uid);
					$hpay_responses_dirty = false;
					
					$hpay_order = $resp["order"];
					$hpay_operation = "";
					
					$restock = null;
					
					if(isset($resp["result"])){
						if(isset($resp["result"]["restock"])){
							if($resp["result"]["restock"]){
								$restock = true;
							}
						}

						if(isset($resp["result"]["hpay_operation"])){
							$hpay_operation = $resp["result"]["hpay_operation"];
						}						
					}
					
					unset($resp["order"]);
					
					if(isset($resp["result"])){
						if(is_string($resp["result"])){
							unset($resp["result"]);
						}
					}
					
					if(isset($resp["result"])){
						if(is_array($resp["result"])){
							$resp = array_merge($resp, $resp["result"]);
						}
					}
					
					$transaction = null; 
					$hpay_responses_tranuids = array();
					
					$is_duplicate_response    = false;
										
					$has_integ_or_ship_update = $this->acceptResponseFiscalAndShipping($order_uid,$resp); 
					
					if($already_received){
						if($reshash){
							try{

								if($already_received === true){
									//other process that first started to handle result is still execting
									sleep(2);
									$already_received = $this->resultAlreadyReceived($order_uid, $reshash);
								}

								if($already_received === true){
									//other process that first started to handle result is still execting
									sleep(2);
									$already_received = $this->resultAlreadyReceived($order_uid, $reshash);
								}

								if($already_received !== true){
									$result_existing["processed_already"] = true;
									$this->unlockHOrderUpdate($resp["order_uid"]);
									return $already_received;
								}else{
									//6 seconds ??? - try again then
									$already_received = false;
								}

							}catch(Throwable $tex){
								HolestPayLib::writeLog("error", $tex,5);
							}
						}
						
					}
					
					if(isset($resp)){
						if(isset($resp["transaction_uid"])){
							$is_set = false;
							foreach($hpay_responses as $index => $prev_result){
								if(isset($prev_result["transaction_uid"])){
									if($prev_result["transaction_uid"] == $resp["transaction_uid"]){
										$hpay_responses[$index] = $resp;
										$is_set = true;
										$hpay_responses_dirty = true;
										break;
									}
								}
							}
							
							if(!$is_set){
								if(isset($resp["result"]) && is_array($resp["result"])){
									$hpay_responses[] = $resp["result"];
									$hpay_responses_dirty = true;
								}
							}
						}else{
							if($has_integ_or_ship_update){
								$resp["transaction_uid"] = "";
								foreach($hpay_responses as $ind => $prev_resp){
									if(isset($prev_resp["transaction_uid"])){
										if($prev_resp["transaction_uid"]){
											continue;
										}
									}
									unset($hpay_responses[$ind]);
								}
								
								if(isset($resp["result"])){
									if(is_array($resp["result"]))
										$hpay_responses[] = $resp["result"];	
									else 
										$hpay_responses[] = $resp;	
								}else{
									$hpay_responses[] = $resp;	
								}
								$hpay_responses_dirty = true;
							}
						}
					}
					
					foreach($hpay_responses as $index => $prev_result){
						if(isset($prev_result["transaction_uid"])){
							$hpay_responses_tranuids[] = $prev_result["transaction_uid"];
						}			
					}
					
					if(isset($hpay_order["Transactions"])){
						usort($hpay_order["Transactions"], function($a, $b){
							return intval($a["id"]) - intval($b["id"]);
						});
						
						foreach($hpay_order["Transactions"] as $trans){
							if($trans){
								if(isset($trans["Data"])){
									if(is_string($trans["Data"])){
										$trans["Data"] = json_decode($trans["Data"], true);
									}
									
									if(!in_array($trans["Uid"],$hpay_responses_tranuids)){
										if(isset($trans["Data"]["result"])){
											$hpay_responses[] = $trans["Data"]["result"];
											$hpay_responses_dirty = true;
										}
									}
									
									if(!$transaction){
										$transaction = $trans;
									}else if($transaction["id"] < $trans["id"]){
										$transaction = $trans;
									}
								}
							}
						}
					}
					
					if($hpay_responses_dirty){
						HolestPayLib::dataProvider()->writeResultsForOrder($order_uid, $hpay_responses);
					}
					
					HolestPayLib::dataProvider()->setOrderHPayStatus($order_uid, $resp["status"]);

					if(strpos($resp["status"],"PAYMENT:PAID") !== false || strpos($resp["status"],"PAYMENT:SUCCESS") !== false){
						if(isset($order["payment_method"]) && intval($order["payment_method"])){
							if($hpay_operation == "capture"){
								try{
									if($hpay_order){
										if(isset($hpay_order["Data"])){
											if(isset($hpay_order["Data"]["items"])){

												HolestPayLib::dataProvider()->updateOrder($order_uid,array(
													"order_items" => $hpay_order["Data"]["items"]
												));

											}
										}	
									}
								}catch(Throwable $crex){
									HolestPayLib::writeLog("error",$crex,6);
								}
							}
						}
					}else if(strpos($resp["status"],"PAYMENT:PARTIALLY-REFUNDED") !== false){
						if(isset($order["payment_method"]) && intval($order["payment_method"])){
							try{
								if($hpay_order){
									if(isset($hpay_order["Data"])){
										if(isset($hpay_order["Data"]["items"])){

											HolestPayLib::dataProvider()->updateOrder($order_uid,array(
												"order_items" => $hpay_order["Data"]["items"]
											));

										}
									}	
								}
							}catch(Throwable $crex){
								HolestPayLib::writeLog("error",$crex,6);
							}
						}
					}else if(strpos($resp["status"],"PAYMENT:VOID") !== false || strpos($resp["status"],"PAYMENT:REFUND") !== false){
						if(isset($order["payment_method"]) && intval($order["payment_method"])){
							try{
								if($hpay_order){
									if(isset($hpay_order["Data"])){
										if(isset($hpay_order["Data"]["items"])){

											HolestPayLib::dataProvider()->updateOrder($order_uid,array(
												"order_items" => $hpay_order["Data"]["items"]
											));

										}
									}	
								}
							}catch(Throwable $crex){
								HolestPayLib::writeLog("error",$crex,6);
							}
						}
					}else if(strpos($resp["status"],"PAYMENT:RESERVED") !== false || strpos($resp["status"],"PAYMENT:AWAITING") !== false){
						
						if(isset($order["payment_method"]) && intval($order["payment_method"])){
							try{
								if($hpay_order){
									if(isset($hpay_order["Data"])){
										if(isset($hpay_order["Data"]["items"])){

											HolestPayLib::dataProvider()->updateOrder($order_uid,array(
												"order_items" => $hpay_order["Data"]["items"]
											));

										}
									}	
								}
							}catch(Throwable $crex){
								HolestPayLib::writeLog("error",$crex,6);
							}
						}
					}
					
					$this->unlockHOrderUpdate($resp["order_uid"]);
				}
			}else{
				$hpay_doing_order_update = false;
				return array(
						'success' => false,
						'message' => HolestPayLib::__('UNVERIFIED_RESULT'),
						"order_id"          => $order_uid,
						"order_site_status" => $this->getOrderHPayStatus($order_uid)
					);
			}
			
			$hpay_doing_order_update = false;	
			$result = array(
				'success'           => true,
				"order_id"          => $order_uid,
				'order_site_status' => $this->getOrderHPayStatus($order_uid)
			);
			
			if($reshash){
				try{
					HolestPayLib::dataProvider()->resultReceivedSave($order_uid,$reshash, $result);
				}catch(Throwable $tex){
					HolestPayLib::writeLog("error", $tex, 6);
				}
			}
			
			return $result;
		}catch(Throwable $ex){

			HolestPayLib::writeLog("error", $ex, 6);
			$hpay_doing_order_update = false;
			$data = array(
				'success'   => false,
				'message'   => HolestPayLib::__('ERROR_EXCEPTION'),
				'exception' => $ex->getMessage()
			);
			
			if($order){
				$data["order_id"]          = $order_uid;
				$data["order_site_status"] = $this->getOrderHPayStatus($order_uid);
			}
			
			return $data;
		}
    }

    private function acceptResult($order, $result, $pmethod_id = null, $is_webhook = false){

		$res = $this->onOrderUpdate($result,$order);

		if($res && isset($res["success"]) && $res["success"]){
			return true;
		}else if($res && isset($res["message"])){
			return $res["message"];
		}else{
			return false;
		}
    }
}
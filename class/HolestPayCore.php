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
    private $_HSiteConfig = null;
    

    /**
     * This function is called automaticly in lib (lib configuration paremetar no_automatic_webresult_handling). If due you project structure you need to call it explicitly then it should be called on user order thank you page (page where user is redirected after payment = hpay_request->order_user_url) and on web-hook accept data endpoint. 
     * @return bool - true when processing happens , false on otherwise
     */
    public function webResultHandler(){
        if($this->_webResultHandlerCalled){
            return;//RUN ONLY ONCE
        }
        $this->_webResultHandlerCalled = true;

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

    public static function getExchnageRate($from, $to){
        $from   = strtoupper(trim($from)); 
		$to     = strtoupper(trim($to)); 
		
		if($from == $to){
			return 1.00;	
		}
		
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

                if(!$cached){
                    if(!isset($cfg["exchange_rate_source"])){
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
            }
        }catch(Throwable $ex){
            HolestPayLib::writeLog("error",$ex->getMessage(),5);
        }

        if($cached){
            return $cached["rate"];
        }
         
        return null;
    }

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

    public function onOrderUpdate(){

    }

    public function acceptResult(){
        
    }

    

}
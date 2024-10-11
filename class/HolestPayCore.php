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

trait HolestPayCore{

    private $_webResultHandlerCalled = false;
    private $_POS = null;

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
     * returns current POS configuration from local data provider storage. POS configuration is obtained from HPay panel on connect or sent to site via web-hook when you update POS on HPay panel. Local copy is stored with (data provider)->setPOSConfiguration($pos_configuration)
     * @param bool $reload - forces re-reading from local data provider storage
     * @return assoc_array - current POS configuration
     */
    public function getPOSConfig($reload = false){
        if(!$reload && $this->_POS){
            return $this->_POS;
        }

        if(!isset(self::$_data_provider))
            return false;

        $this->_POS = HolestPayLib::dataProvider()->loadPOSConfiguration();    

        return $this->_POS;
    }

    
    /**
     * sets current POS configuration from data received on connect or via web-hook when you update POS on HPay panel. 
     * @param assoc_array $pos_config - current config to set
     * @return assoc_array - current POS configuration (just set)
     */
    private function setPOSConfig($pos_config){
        if(is_object($pos_config)){
            $pos_config = json_decode(json_encode($pos_config),true);
        }else if(is_string($pos_config)){
            $pos_config = json_decode($pos_config,true);
        }

        $this->_POS = $pos_config;

        if(!isset(self::$_data_provider))
            return false;

        $this->_POS = HolestPayLib::dataProvider()->setPOSConfiguration($pos_config);    

        return $this->_POS;
    }

    public function onOrderUpdate(){

    }

    public function acceptResult(){
        
    }

    

}
<?php
namespace holestpay;

if(!defined('HOLESTPAYLIB')){
    die("Direct access to this file is not allowed");
}

public trait HolestPayCore{
    private $_webResultHandlerCalled = false;

    /**
     * This function is called automaticly in lib (lib configuration paremetar no_automatic_webresult_handling). If due you project structure you need to call it explicitly then it should be called on user order thank you page (page where user is redirected after payment = hpay_request->order_user_url) and on web-hook accept data endpoint. 
     * @return bool - true when processing happens , false on otherwise
     */
    public function webResultHandler(){
        if($this->$_webResultHandlerCalled){
            return;//RUN ONLY ONCE
        }
        $this->$_webResultHandlerCalled = true;




    }

    public function onOrderUpdate(){

    }

    public function acceptResult(){
        
    }

}
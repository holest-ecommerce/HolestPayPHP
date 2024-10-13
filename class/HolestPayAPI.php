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

trait HolestPayAPI{


    public function hpayClientApiCall($endpoint_path, $data, $blocking = true){

    }

    public function storeOrder($order_id, $with_status = null, $noresultawait = false){

    }

    public function callMethodAction($hmethod_uid, $action, $data){

    }
    
    public function callMethodDefaultAction($hmethod_uid, $action, $data){
        return $this->callMethodAction($hmethod_uid, "defaultAction", $data);
    }

    public function charge($pay_request, $hpay_method_id, $vault_token_uid){

    }

    
}
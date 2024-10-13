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

trait HolestPayMethods{
    
    public function getPaymentMethods($only_enabled = true, $cof_capable = null, $for_country = null, $for_amount = null, $for_amount_currency = null){

    }

    public function getShippingMethods($only_enabled = true, $for_country = null, $for_amount = null, $for_amount_currency = null){

    }

    public function getFiscalAndIntegrationMethods($only_enabled = true, $for_country = null){

    }

    public function getPaymentMethod($hpayment_method_uid){

    }

    public function getShippingMethod($hshipping_method_uid){

    }

    public function getFiscalAndIntegrationMethod($hfi_method_uid){

    }

    public function calculateShipping($hshipping_method_uid, $order_uid_or_horder){

    }

}
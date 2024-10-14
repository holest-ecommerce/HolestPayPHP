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

    private function filteredMethodsOfType($method_type, $only_enabled = true, $cof_capable = null, $for_country = null, $for_amount = null, $for_amount_currency = null){
        $filtered_list = array();
        $mms = $this->getPOSParam($method_type, array());
        foreach($mms as $mm){

            if($only_enabled && !$mm["Enabled"])
                continue;

            if($cof_capable !== null){
                if($cof_capable && isset($mm["POps"])){
                    if(strpos($mm["POps"],"charge") === false)
                        continue;
                }else{
                    if(strpos($mm["POps"],"charge") !== false)
                        continue;
                }
            }   
            
            if($for_country){
                if(isset($mm["Excluded Countries"])){
                    if(in_array(strtoupper($for_country),$mm["Excluded Countries"])){
                        continue;
                    }
                }
                if(isset($mm["Only For Countries"])){
                    if(!in_array(strtoupper($for_country),$mm["Only For Countries"])){
                        continue;
                    }
                }
            }

            if($for_amount !== null){
                if(!$for_amount_currency){
                    $curr = null;
                    preg_match('/[a-zA-Z]{3}/', "{$for_amount}", $curr);
                }

                if(!$for_amount_currency)
                    $for_amount_currency = HolestPayLib::instance()->getCurrency();
                

                if(isset($mm["Minimal Order Amount"])){
                    if($mm["Minimal Order Amount"]){
                        $amt = HolestPayLib::convertMoney($mm["Minimal Order Amount"], $for_amount_currency);     
                        if($amt < $for_amount){
                            continue;
                        }
                    }
                }

                if(isset($mm["Maximal Order Amount"])){
                    if($mm["Maximal Order Amount"]){
                        $amt = HolestPayLib::convertMoney($mm["Maximal Order Amount"], $for_amount_currency);     
                        if($amt > $for_amount){
                            continue;
                        }
                    }
                }
            }
            $filtered_list[] = $mm;
        }
        return $filtered_list;
    }

    public function getPaymentMethods($only_enabled = true, $cof_capable = null, $for_country = null, $for_amount = null, $for_amount_currency = null){
        return $this->filteredMethodsOfType("payment", $only_enabled, $cof_capable, $for_country, $for_amount, $for_amount_currency);
    }

    public function getShippingMethods($only_enabled = true, $for_country = null, $for_amount = null, $for_amount_currency = null){
        return $this->filteredMethodsOfType("shipping", $only_enabled, null, $for_country, $for_amount, $for_amount_currency);
    }

    public function getFiscalAndIntegrationMethods($only_enabled = true, $for_country = null){
        return $this->filteredMethodsOfType("fiscal", $only_enabled, null, $for_country, null, null);
    }

    public function getPaymentMethod($hpayment_method_uid_or_id){
        $pms = $this->getPOSParam("payment", array());
        foreach($pms as $pm){
            if($pm["Uid"] == $hpayment_method_uid_or_id || $pm["HPaySiteMethodId"] == $hpayment_method_uid_or_id)
                return $pm;
        }
        return null;
    }

    public function getShippingMethod($hshipping_method_uid_or_id){
        $sms = $this->getPOSParam("shipping", array());
        foreach($sms as $sm){
            if($sm["Uid"] == $hshipping_method_uid_or_id || $sm["HPaySiteMethodId"] == $hshipping_method_uid_or_id)
                return $sm;
        }
        return null;
    }

    public function getFiscalAndIntegrationMethod($hfi_method_uid_or_id){
        $fms = $this->getPOSParam("fiscal", array());
        foreach($fms as $fm){
            if($fm["Uid"] == $hfi_method_uid_or_id || $fm["HPaySiteMethodId"] == $hfi_method_uid_or_id)
                return $fm;
        }
        return null;
    }

    public function calculateShipping($hshipping_method_uid, $order_uid_or_horder){

    }

}
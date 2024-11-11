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

trait HolestPaySign{

    /**
    * Computes signature hash for site -> HPay request
    * @param string $transaction_uid - empty for pay request
    * @param string $status: - empty for pay request, set only when updated
    * @param string $order_uid: order uniqe identifier as in request - maybe be order ID but note that AUTO-INCREMENT ID may change on DB migration
    * @param string $amount: amount - total amount in order currency max 8 decimals, 
    * @param string $currency: 3 letter order currency code like EUR, USD, CHF ... 
    * @param string $vault_token_uid: If new token it will be 'new'. On subsequent changes it will have value assigned back from HolestPay.
    * @param string $subscription_uid: if subscripion then subscripion_uid is used. Value used should be as in current request.  
    * @return string value to set as verificationhash
    */
    public function payRequestSignatureHash($transaction_uid, $status, $order_uid, $amount, $currency, $vault_token_uid = "", $subscription_uid = "", $rand = ""){
        if(!trim($order_uid))
            $order_uid = "";
        else
            $order_uid = trim($order_uid);
        
        if($amount === null || trim($amount) === ""){
            $amount = 0;
        }
        
        $amount = number_format($amount,8,".","");//8 decimals , . is decimal separator , no thousand separator
        
        if($currency && strlen($currency) !== 3)
            return null;
        
        if(!$currency){
            $currency = "";
        }else{
            $currency = trim($currency);
        }
        
        if(!$subscription_uid)
            $subscription_uid = "";
        else 
            $subscription_uid = trim($subscription_uid);
        
        if(!$vault_token_uid)
            $vault_token_uid = "";
        else 
            $vault_token_uid = trim($vault_token_uid);
        
        if(!$transaction_uid)
            $transaction_uid = "";
        
        if(!$rand)
            $rand = "";
        
        if(!$status)
            $status          = "";
        
        $merchant_site_uid = $this->getPOSConnectionParam("merchant_site_uid","undefined");
        $secret_token = $this->getPOSConnectionParam("secret_token","undefined");
        
        $srcstr = "{$transaction_uid}|{$status}|{$order_uid}|{$amount}|{$currency}|{$vault_token_uid}|{$subscription_uid}{$rand}";
        $srcstrmd5 = md5($srcstr . $merchant_site_uid);
        
        return strtolower(hash("sha512", $srcstrmd5 . $secret_token));
    }

    /**
     * Adds verificationhash signature to alraedy prepared request data to send
     * @param array (assoc) $data - prepared data for request
     */
    public function signRequestData(& $data){
        if(!$data)
            $data = array();

        $status = null;
        $order_uid = null;
        $amount = null;
        $currency = null;
        $vault_token_uid = null;
        $subscription_uid = null;
        $rand = null;

        if(isset($data["status"])) $status = $data["status"];
        if(isset($data["order_uid"])) $order_uid = $data["order_uid"];
        if(isset($data["order_amount"])) $amount = $data["order_amount"];
        if(isset($data["order_currency"])) $currency = $data["order_currency"];
        if(isset($data["vault_token_uid"])) $vault_token_uid = $data["vault_token_uid"];
        if(isset($data["subscription_uid"])) $subscription_uid = $data["subscription_uid"];
        if(isset($data["rand"])) $rand = $data["rand"];

        if(!$rand){
            $rand = uniqid("rnd");
            $data["rand"] = $rand;
        }
        

        $data["verificationhash"] = $this->payRequestSignatureHash(null, $status, $order_uid, $amount, $currency, $vault_token_uid, $subscription_uid, $rand);
    }
        
    /**
     * Checks the signature value of HPay -> Site 'vhash' against returned returned parameters specified as arguments
     * @param string $returned_vhash: hash to validate
     * @param string $transaction_uid as in response or ""
     * @param string $status  as in response or ""
     * @param string $order_uid: order uniqe identifier as in response
     * @param string $amount: amount - total amount as in response
     * @param string $currency: 3 letter order currency code like EUR, USD, CHF ... as in response
     * @param string $vault_token_uid: as in response or ""
     * @param string $subscription_uid:  as in response or ""
     * @param string $rand: extra security string
     * @return bool - true if matches, otherwise false
    */
    public function payResponseVerifyHash($returned_vhash, $transaction_uid, $status, $order_uid, $amount, $currency, $vault_token_uid = "", $subscription_uid = "", $rand = ""){
        
        if(!trim($order_uid)){
            return null;
        }else{
            $order_uid = trim($order_uid);
        }
        
        if($amount === null){
            $amount = 0;
        }
        
        $amount = number_format($amount,8,".","");//8 decimals , . is decimal separator , no thousand separator
        
        if($currency && strlen($currency) !== 3)
            return null;
        
        if(!$rand)
            $rand = "";
        
        if(!$subscription_uid)
            $subscription_uid = "";
        else 
            $subscription_uid = trim($subscription_uid);
        
        if(!$vault_token_uid)
            $vault_token_uid = "";
        else 
            $vault_token_uid = trim($vault_token_uid);
        
        if(!$transaction_uid)
            $transaction_uid = "";
        else 
            $transaction_uid = trim($transaction_uid);
        
        if(!$status)
            $status = "";
        else 
            $status = trim($status);
        
        
        $merchant_site_uid = $this->getPOSConnectionParam("merchant_site_uid","undefined");
        $secret_token = $this->getPOSConnectionParam("secret_token","undefined");
        
        $srcstr    = "{$transaction_uid}|{$status}|{$order_uid}|{$amount}|{$currency}|{$vault_token_uid}|{$subscription_uid}{$rand}";
        $srcstrmd5 = md5($srcstr . $merchant_site_uid);
        $computed  = strtolower(hash("sha512", $srcstrmd5 . $secret_token));
        
        return $computed == strtolower($returned_vhash);
    }
    /**
     * Checks the signature of HPay -> Site response
     */
    public function verifyResponse($response){
        if(!$response)
            return false;
        
        if(!isset($response["vhash"])){
            return false;
        }
        
        if(!isset($response["transaction_uid"])){
            $response["transaction_uid"] = "";
        }
        
        if(!isset($response["status"])){
            $response["status"] = "";
        }
        
        if(!isset($response["order_uid"])){
            $response["order_uid"] = "";
        }
        
        if(!isset($response["order_amount"])){
            $response["order_amount"] = "0";
        }
        
        if(!isset($response["order_currency"])){
            $response["order_currency"] = "";
        }
        
        if(!isset($response["vault_token_uid"])){
            $response["vault_token_uid"] = "";
        }
        
        if(!isset($response["subscription_uid"])){
            $response["subscription_uid"] = "";
        }
        
        if(!isset($response["rand"])){
            $response["rand"] = "";
        }
        
        if(!isset($response["vhash"])){
            return false;
        }
        
        try{
            return $this->payResponseVerifyHash(
                $response["vhash"],
                $response["transaction_uid"],
                $response["status"],
                $response["order_uid"],
                $response["order_amount"],
                $response["order_currency"],
                $response["vault_token_uid"],
                $response["subscription_uid"],
                $response["rand"]
            );
        }catch(Throwable $ex){
            HolestPayLib::writeLog("error",$ex->getMessage(),7);
            return false;
        }
    }

}
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

/**
 * Result for lib's built-in fetch function
 */
class NetResponse{
    /** HTTP Status */
    public $status = null;

    /** HTTP RAW Response */
    public $raw = null;

    private $_json = null;

    public function __construct($raw, $status){
        $this->raw = $raw; 
        $this->status = $status; 
    }

    /**
     * Desodes response JSON to assoc array
     */
    public function json(){
        if($this->_json){
            return $this->_json;
        }

        if($this->raw){
            $this->_json = json_decode($this->raw, true);
        }
        return $this->_json;
    }

    /**
     * Desodes response as string, same as ->raw
     */
    public function text(){
        return $this->raw;
    }
}


trait HolestPayNet{

/**
 * Performs HTTP request
 * @param string $url - source url
 * @param assoc_array? $http_request - array("method" => "POST|PUT|...", "headers" => array("Content-Type" => "application/json",...), "blocking" => true|fallse, "body" => ..., "timeout" => 25 )
 * @returns \holestpay\NetResponse
 */
    public static function fetch($url, $http_request = false)
    {
        $method = "GET";
        $blocking = true;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$timeout = 25;

        if($http_request){

            if(isset($http_request["method"])){
                if($http_request["method"])
                    $method = $http_request["method"];
            }

            if($method == "GET" && isset($http_request["body"])){
                if($http_request["body"]){
                    $method = "POST";
                }
            }

            if(isset($http_request["headers"])){
                $headers = array();
                foreach($http_request["headers"] as $key => $val){
                    if(ctype_digit("{$key}")){
                        $headers[] = $val;
                    }else{
                        $headers[] = "{$key}: {$val}";
                    }
                }

                if(!empty($headers)){
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                }
            }

            if(isset($http_request["timeout"])){
                $timeout = intval($http_request["timeout"]);
            }

            if(isset($http_request["blocking"])){
                $blocking = $http_request["blocking"];
            }
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);//WE HAVE REQ SIGING, and having this 'on' creates problems very commonly
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);//WE HAVE REQ SIGING, and having this 'on' creates problems very commonly

        if(!$blocking){
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $response = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
        curl_close($ch);

        return new \holestpay\NetResponse($response, $httpcode);
    }

}
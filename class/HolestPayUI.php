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

trait HolestPayUI{

    /**
     * Returns (or also outputs) scripts and data for site fontend. Optionaly pass $checkout_order_uid to immedatly initiate payment for that order
     * @param bool $echo - set to true to immedatly output orherwise it will return HTML in bod cases 
     * @param string $csp_nonce - if you use nonce for Content Security Policy set nonce so it gets added as scripts attribute
     * @return string - HTML containing script tags and needed data for site frontent
     */
    public function frontendScripts($echo = false, $csp_nonce = ""){


    }

    /**
     * Returns raw script (without script tags) that will initiate payment for order. Requires scripts from ->frontendScripts.  Use $invoker_fn_name if you don't want to start payment immedatly to specify name of javascript function that you will call explicitly wo start the process  
     * @param string $checkout_order_uid - order uid to prepare request for
     * @param string $invoker_fn_name - name of function that will invoke the process. Is null auto-invoke will happen
     * @return string - javascipt for order payment invocation 
     */
    public function orderPayScript($checkout_order_uid, $invoker_fn_name = null){

    }

    /**
     * Returns raw script (without script tags) that will initiate payment for order. Requires scripts from ->frontendScripts.  Use $invoker_fn_name if you don't want to start payment immedatly to specify name of javascript function that you will call explicitly wo start the process  
     * @param string $checkout_form_selector - selector for HMLL form element (document.querySelector) to create payment request from
     * @param string $invoker_fn_name - name of function that will invoke the process. Is null auto-invoke will happen
     * @return string - javascipt for order payment invocation 
     */
    public function orderFormPayScript($checkout_form_selector, $invoker_fn_name = null){

    }

    /**
     * Returns (or also outputs) scripts and data for site backend. 
     * @param bool $echo - set to true to immedatly output orherwise it will return HTML in bod cases 
     * @param string $csp_nonce - if you use nonce for Content Security Policy set nonce so it gets added as scripts attribute
     * @return string - HTML containing script tags and needed data for site backend
     */
    public function backendendScripts($echo = false, $csp_nonce = ""){

        
    }

}
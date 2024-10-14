<?php


try{
    require_once(__DIR__ . "/../holestpay.php");
    \holestpay\HolestPayLib::init();
 
    echo \holestpay\HolestPayLib::convertMoney("EUR 30.5","RSD");

    


}catch(Throwable $ex){
    echo $ex->getMessage();
}
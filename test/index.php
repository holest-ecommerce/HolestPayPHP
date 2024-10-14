<?php


try{
    require_once(__DIR__ . "/../holestpay.php");
    \holestpay\HolestPayLib::init();
 
    \holestpay\HolestPayLib::writeLog("error","MUDA");

    echo \holestpay\HolestPayLib::convertMoney("30 EUR","RSD");

    


}catch(Throwable $ex){
    echo $ex->getMessage();
}
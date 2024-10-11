<?php


try{
    
    require_once(__DIR__ . "/../holestpay.php");
    \holestpay\HolestPayLib::init();


    echo \holestpay\HolestPayLib::convertToCM(50,"in");

    


}catch(Throwable $ex){
    echo $ex->getMessage();
}
<?php


try{
    
    require_once(__DIR__ . "/../holestpay.php");
    HolestPayLib::init();
}catch(Throwable $ex){
    echo $ex->getMessage();
}
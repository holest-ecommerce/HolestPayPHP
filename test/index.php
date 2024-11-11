<?php

require_once(__DIR__ . "/../holestpay.php");
use \holestpay\HolestPayLib;
 



try{
    HolestPayLib::init();
    require_once(__DIR__ ."/libmenu.php");

    echo "<hr/>";
    echo "CURRENT LIB CONF<br/>";

    echo "<pre style='background: #feffde;padding: 15px;'>";

    echo stripcslashes(str_replace(array("{","}",'"',','),"",json_encode(HolestPayLib::libConfig(), JSON_PRETTY_PRINT)));

    echo "</pre>";
   
    echo "<hr/>";
    echo "EXCHANGE RATES";
    echo "<br/>EUR-RSD:".HolestPayLib::convertMoney("EUR 1","RSD");
    echo "<br/>USD-RSD:".HolestPayLib::convertMoney("1USD","RSD");
    echo "<br/>CHF-RSD:".HolestPayLib::convertMoney("1 CHF","RSD");
    echo "<br/>MKD-RSD:".HolestPayLib::convertMoney("1 MKD","RSD");
    echo "<br/>BAM-RSD:".HolestPayLib::convertMoney("1 BAM","RSD");



}catch(Throwable $ex){
    echo $ex->getMessage();
}
<?php


try{
    require_once(__DIR__ . "/../holestpay.php");
    \holestpay\HolestPayLib::init();

    echo "<hr/>";

    echo "<a href='index.php'>TEST START - LIB STATUS</a> | <a href='frontend.php'>FRONTEND</a> | <a href='admin.php'>BACKEND</a>";

    echo "<hr/>";

    echo "<hr/>";
    echo "CURRENT LIB CONF<br/>";

    echo "<pre style='background: #feffde;padding: 15px;'>";

    echo stripcslashes(str_replace(array("{","}",'"',','),"",json_encode(\holestpay\HolestPayLib::libConfig(), JSON_PRETTY_PRINT)));

    echo "</pre>";
   
    echo "<hr/>";
    echo "EXCHANGE RATES";
    echo "<br/>EUR-RSD:".\holestpay\HolestPayLib::convertMoney("EUR 1","RSD");
    echo "<br/>USD-RSD:".\holestpay\HolestPayLib::convertMoney("1USD","RSD");
    echo "<br/>CHF-RSD:".\holestpay\HolestPayLib::convertMoney("1 CHF","RSD");
    echo "<br/>MKD-RSD:".\holestpay\HolestPayLib::convertMoney("1 MKD","RSD");
    echo "<br/>BAM-RSD:".\holestpay\HolestPayLib::convertMoney("1 BAM","RSD");



}catch(Throwable $ex){
    echo $ex->getMessage();
}
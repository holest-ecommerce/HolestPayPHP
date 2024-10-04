<?php
/**
* HolestPay PHP lib
* -------------------------------------------------
* File Version: 1.0.1
**/
namespace holestpay;

require_once(__DIR__ . "/../class/HolestPayAbstractLogProvider.php");
public class FileSystemLogProvider extends HolestPayAbstractLogProvider{
    private $lib_configuration = null;
 
    /**
     * Provider constructior. You should never call this constructor yourself. HolestPayLib will call it internaly, and you only set log_provider_class lig configuration parameter to this file name / class name (file name and class name must be same)
     * @param assoc_array $lib_configuration - library configuration
     */
     public function __construct($lib_configuration){
        $this->lib_configuration = $lib_configuration;
     }

    /**
     * writes the data to appropriate log file. You should not call this methord here. Instead configure log_provider_... in lib config and then use HolestPayLib::writeLog($logscope, $data, $stack = false);  
     * 
     * @param string $logscope - can be just "error"|"waring"|"log" or something like "order_4635764_result"
     * @param any $data - data to log
     * @param bool $stack - true to also add call stack
     * @return - true on success , false on failure
     */
    public function writeLog($logscope, $data, $stack = false){

        
    }


}
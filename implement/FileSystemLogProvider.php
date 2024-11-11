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
require_once(__DIR__ . "/../class/HolestPayAbstractLogProvider.php");
class FileSystemLogProvider extends HolestPayAbstractLogProvider{
    private $lib_configuration = null;
 
    /**
     * Provider constructior. You should never call this constructor yourself. HolestPayLib will call it internaly, and you only set log_provider_class lig configuration parameter to this file name / class name (file name and class name must be same)
     * @param array (assoc) $lib_configuration - library configuration
     */
     public function __construct($lib_configuration){
        $this->lib_configuration = $lib_configuration;
        if($this->lib_configuration){
            if(isset($this->lib_configuration["log_provider_folder"])){
                $this->lib_configuration["log_provider_folder"] = rtrim($this->lib_configuration["log_provider_folder"],"/");
            }else{
                $this->lib_configuration["log_enabled"] = false;
            }
        }
     }

    /**
     * writes the data to appropriate log file. You should not call this methord here. Instead configure log_provider_... in lib config and then use HolestPayLib::writeLog($logscope, $data, $stack = false);  
     * 
     * @param string $logscope - must be applacable to be part of file/folder name can be just "error"|"waring"|"log" or something like "order_4635764_result"
     * @param any $data - data to log
     * @return - true on success , false on failure
     */
    public function writeLog($logscope, $data){
        global $__hpay_log_clean_done;
        if(!isset($__hpay_log_clean_done)){
            $__hpay_log_clean_done = true;
            $this->cleanOutdated();
        }

        if($this->lib_configuration){
            if($this->lib_configuration["log_enabled"]){
                $dest = null;
                if(substr($this->lib_configuration["log_provider_folder"],0,1) == "."){
                    $dest = realpath(HPAY_LIB_ROOT . "/" . $this->lib_configuration["log_provider_folder"] . "/" . date("Y_m_d_") . $logscope . ".log");
                }else{
                    $dest = realpath($this->lib_configuration["log_provider_folder"] . "/" . date("Y_m_d_") . $logscope . ".log");
                }
                @file_put_contents($dest, date("Y-m-d H:i:s: ") . ( is_string($data) ? $data : json_encode($data, JSON_PRETTY_PRINT)) ."\r\n",FILE_APPEND);      
            }
        }
    }

    /**
     * removes log files older than ini.log_expiration_days
     */
    public function cleanOutdated(){
        try{
            if($this->lib_configuration){
                if($this->lib_configuration["log_enabled"]){
                    if(isset($this->lib_configuration["log_expiration_days"])){
                        if(intval($this->lib_configuration["log_expiration_days"])){
                            $files = array_unique(glob($this->lib_configuration["log_provider_folder"] ."/*.log"));
                            $threshold = strtotime("-{$this->lib_configuration["log_provider_folder"]} day");
                            foreach ($files as $file) {
                                if (is_file($file)) {
                                    if ($threshold >= filemtime($file)) {
                                        @unlink($file);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }catch(Throwable $ex){
            //:( where ???
        }
    }

    /**
     * gets error logs
     * @return array - array of found error logs by date
     */
    public function get_error_logs(){
        $elogs = array();
        try{
            if($this->lib_configuration){

                $this->cleanOutdated();

                $files = array_unique(glob($this->lib_configuration["log_provider_folder"] ."/*error.log"));
               
                foreach ($files as $file) {
                    if (is_file($file)) {
                         $elogs[basename($file)] = @file_get_contents($file);
                    }
                }
            }
        }catch(Throwable $ex){
            //:( where ???
        }
        return $elogs;
    }


}
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

require_once(__DIR__ . "/../class/HolestPayAbstractTranslationProvider.php");

class HolestPayDefaultTranslation extends HolestPayAbstractTranslationProvider{
    private $lib_configuration = null;
    private $_translations_loaded = array();

    /**
     * Provider constructior. You should never call constructor of exteneded provider class yourself. HolestPayLib will call it internaly, and you only set translation_provider_class lib configuration parameter to provider class (extended from this)  file name / class name (file name / class name must be same)
     * @param assoc_array $lib_configuration - library configuration
     */
    public function __construct($lib_configuration){
        $this->lib_configuration = $lib_configuration;
    }

    /**
     * Translates $phrase into $lng. $arg1-6 are value replacments 
     * @param string $phrase - phrase to translate
     * @param string $lng - target hpay language
     * @param any $arg1 - replacment value 1
     * @param any $arg2 - replacment value 2
     * @param any $arg3 - replacment value 3
     * @param any $arg4 - replacment value 4
     * @param any $arg5 - replacment value 5
     * @param any $arg6 - replacment value 6
     * @return string - translated $phrase. Original if translation is not found
     */
    public function translate($phrase, $lng, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null, $arg5 = null, $arg6 = null){
        if(!isset($this->_translations_loaded[$lng])){
            if(file_exists(HPAY_LIB_ROOT . "/i8n/{$lng}.json")){
            $context = @file_get_contents(HPAY_LIB_ROOT . "/i8n/{$lng}.json");
            if($context){
                $this->_translations_loaded[$lng] = json_decode(HPAY_LIB_ROOT . "/i8n/{$lng}.json",true);
                if(!$this->_translations_loaded[$lng]){
                    $this->_translations_loaded[$lng] = array();
                    HolestPayLib::writeLog("error","Translation file " . HPAY_LIB_ROOT . "/i8n/{$lng}.json" . " un-parsable");
                }
            }else{
                    HolestPayLib::writeLog("error","Translation file " . HPAY_LIB_ROOT . "/i8n/{$lng}.json" . " empty");
            }
            }else{
                HolestPayLib::writeLog("error","Translation file " . HPAY_LIB_ROOT . "/i8n/{$lng}.json" . " not found");
            }
        }

        $str = "{$phrase}";

        if(isset($this->_translations_loaded[$lng][$str])){
            $str = $this->_translations_loaded[$lng][$str];
        }

        if($arg1 !== null){
            $replacments = array($arg1, $arg2, $arg3, $arg4, $arg5, $arg6);
            $t = array();
            $r = array();
            foreach($replacments as $ind => $val){
                $t[] = '$' . ($ind + 1);
                $r[] = $val;     
            }
            $str = str_replace($t,$r,$str);
        }
        return $str;
    }
}


trait HolestPayI8N{
    
    public static function __($str, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null, $arg5 = null, $arg6 = null ){
        $lng = HolestPayLib::dataProvider()->getLanguage();
        if(!$lng)
            $lng = "en";
        return HolestPayLib::translationProvider()->translate($str,$lng,$arg1, $arg2, $arg3, $arg4, $arg5, $arg6);
    }

    public function translateKeys($data){
		if(!$data){
			$data = array("---" => HolestPayI8N::__("Failed: no valid payment info response"));
		}
		$tdata = array();
		if($data){
			foreach($data as $key => $val){
				if(is_object($val) || is_array($val)){
					$tdata[HolestPayI8N::__($key)] = $this->translateKeys($val);
				}else{
					$tdata[HolestPayI8N::__($key)] = $val;
				}
			}
		}
		return $tdata;
	}
}
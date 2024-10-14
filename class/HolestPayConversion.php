<?php
/**
* HolestPay PHP lib
* -------------------------------------------------
* File Version: 1.0.1
**/
namespace holestpay;

if(!defined('HOLESTPAYLIB')){
    die("Direct access to this file is not allowed");
}

trait HolestPayConversion{
    private static $TO_GRAM_UNIT_CONVERT = array(
		"g" =>         1,
		"grams" =>     1,
		"gram" =>      1,
		"mg" =>        0.001,
		"mgs" =>       0.001,
		"miligrams" => 0.001,
		"miligram" =>  0.001,
		"kg" =>        1000,
		"kgs" =>       1000,
		"kilograms" => 1000,
		"kilogram" =>  1000,
		"oz" =>        28.3495231,
		"ounces" =>    28.3495231,
		"ounce" =>     28.3495231,
		"lb" =>        453.59237,
		"lbs" =>       453.59237,
		"pounds" =>    453.59237,
		"pound" =>     453.59237
	);
	
	private static $TO_CM_UNIT_CONVERT = array(
		"mm" =>       0.1,
		"m"  =>       100,
		"km" =>       100000,
		"cm" =>       1,
		"dm" =>       10,
		"in" =>       2.54,
		"mi" =>       160934.4,
		"ft" =>       30.48,
		"yd" =>       91.44,
		"yds"     =>  91.44,
		"chain"   =>  2011.68,
		"furlong" =>  20116.80
	);
	
	public static function convertToGrams($value, $unit){
		if(!$value)
			return 0;
		
		$unit = strtolower(trim($unit));
		if(isset(self::$TO_GRAM_UNIT_CONVERT[$unit])){
			return self::$TO_GRAM_UNIT_CONVERT[$unit] * $value;
		}
		return $value;
	}
	
	public static function convertToCM($value, $unit){
		if(!$value)
			return 0;
		
		$unit = strtolower(trim($unit));
		if(isset(self::$TO_CM_UNIT_CONVERT[$unit])){
			return self::$TO_CM_UNIT_CONVERT[$unit] * $value;
		}
		return $value;
	}

	/**
	 * Converts money string like 2222 RSD to amount in specified currency
	 * @param string $amt_with_currency - like '1100.00 RSD'
	 * @param string $to_currency - target currency like EUR, BAM, MKD...
	 */
	public static function convertMoney($amt_with_currency, $to_currency = null){
		if($amt_with_currency){

			if($to_currency === null){
				$to_currency = HolestPayLib::instance()->getCurrency();
			}

			$curr = null;
			$price = floatval($amt_with_currency);

			preg_match('/[a-zA-Z]{3}/', $amt_with_currency, $curr);
			if(!empty($curr)){
				$curr = strtoupper(trim($curr[0]));
			}

			if(!$curr)
				$curr = HolestPayLib::instance()->getCurrency();

			if($to_currency != $curr){
				$rate = HolestPayLib::instance()->getMerchantExchnageRate($curr, $to_currency);
				return $price * $rate;
			}
			
			return $price;

		}else
			return 0.00;
	}

}
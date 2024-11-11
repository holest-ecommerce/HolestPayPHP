<?php
/**
* HolestPay PHP lib
* -------------------------------------------------
* File Version: 1.0.1
* Date: October, 2024
**/
namespace holestpay;

if (!function_exists('array_is_list')) {
    function array_is_list(array $arr)
    {
        if ($arr === []) {
            return true;
        }
        return array_keys($arr) === range(0, count($arr) - 1);
    }
}
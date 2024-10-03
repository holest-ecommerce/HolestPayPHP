<?php
/**
* HolestPay PHP lib
* -------------------------------------------------
* File Version: 1.0.1
**/
namespace HolestPay;

abstract class HolestPayAbstractLogProvider {
  /**
   * writes the data 
   * 
   * @param string $logscope - can be just "error"|"waring"|"log" or something like "order_4635764_result"
   * @param any $data - data to log
   * @param bool $stack - true to also add call stack
   * @return - true on success , false on failure
   */
  abstract public function writelog($logscope, $data, $stack = false);
}
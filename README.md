# HolestPayPHP
 HolestPayPHP library

# HolestPay Order Satatus Format

[PAYMENT:payment_status][ (fmethod1_uid)_FISCAL:(fmethod1_status) [(fmethod2_uid)_FISCAL:(fmethod2_status)]...][ (smethod1_uid)_SHIPPING:packet_no@shipping_status [(smethod2_uid)_SHIPPING:packet_no@shipping_status]...]

* ORDER PAYMENT/FISCAL&INTEGRATION/SHIPPING IS IMPORTANT! 
* ONE AND ONLY ONE EMPTY CHARACTER AS SUB-STATUSES SEPARATOR IS IMPORTANT 

Possible payment status:
    SUCCESS
    PAID
    AWAITING (waiting bank trasfer for example)
    REFUNDED
    PARTIALLY-REFUNDED
    VOID
    RESERVED (amount is reserved but still not captured from the buyer card)
    EXPIRED (used with methods that have expiration)
    OBLIGATED (same as AWAITING but when services delivery for customer has started or there is legal maen to garantee payment will happen)
    REFUSED

    PAYMENT:payment_status - may not exist if HolestPay payment module is not used and you don't set it explicitly     

Possible fisal module status:
  - varies depending on module
    
    Fiscal statuses will exists only if fiscal/integration module add status at all and if it is executed

Possible packet shipping status: 
    PREPARING - initial status if shipping address is ok. Instructions can be submitted to courier directly from this status
    READY - used by some companies that indicate to colleagues that order is checked, goods ready and that they can submit request to courier
    SUBMITTED - request submitted to currier
    DELIVERY - under delivery
    DELIVERED - delivered
    ERROR - error in currier api request
    RESOLVING - shipping address (or something else) needs attention from backend
    FAILED - delivery permanently failed, or currier API refused the request
    CANCELED - explicitly canceled by buyer or company
    
    Shipping statuses will exists only there are packets handled by HPay shipping modules

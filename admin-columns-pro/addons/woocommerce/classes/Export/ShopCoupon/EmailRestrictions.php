<?php

namespace ACA\WC\Export\ShopCoupon;

use ACP;
use WC_Coupon;

class EmailRestrictions implements ACP\Export\Service
{

    public function get_value($id): string
    {
        $restrictions = (new WC_Coupon($id))->get_email_restrictions();

        return implode(', ', $restrictions);
    }

}
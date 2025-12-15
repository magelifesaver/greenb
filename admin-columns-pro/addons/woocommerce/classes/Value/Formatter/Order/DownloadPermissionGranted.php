<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;

class DownloadPermissionGranted extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $granted = $order->get_download_permissions_granted();

        if ( ! $granted) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            ac_helper()->icon->yes(null, __('Download Permission Granted', 'codepress-admin-columns'))
        );
    }

}
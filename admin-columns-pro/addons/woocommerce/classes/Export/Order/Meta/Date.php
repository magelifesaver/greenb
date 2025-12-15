<?php

namespace ACA\WC\Export\Order\Meta;

use ACP;
use DateTime;

class Date implements ACP\Export\Service
{

    private $key;

    private $format;

    public function __construct(string $key, string $format = 'Y-m-d H:i:s')
    {
        $this->key = $key;
        $this->format = $format;
    }

    public function get_value($id): string
    {
        $order = wc_get_order($id);

        $metadata = $order->get_meta($this->key);

        $time = ac_helper()->date->strtotime($metadata);

        if ( ! $time) {
            return '';
        }

        $date_time = DateTime::createFromFormat('U', $time);

        return $date_time
            ? $date_time->format($this->format)
            : $date_time;
    }

}
<?php

namespace ACA\WC\Export\Order\Meta;

use ACP;

class Image implements ACP\Export\Service
{

    private $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function get_value($id): string
    {
        $order = wc_get_order($id);

        $metadata = $order->get_meta($this->key);

        if (is_numeric($metadata)) {
            $url = wp_get_attachment_url($metadata);

            return $url ?: '';
        }

        if (filter_var($metadata, FILTER_VALIDATE_URL) && preg_match('/[^\w.-]/', $metadata)) {
            return $metadata;
        }

        return '';
    }

}
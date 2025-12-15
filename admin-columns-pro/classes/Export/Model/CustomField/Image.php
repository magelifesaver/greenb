<?php

namespace ACP\Export\Model\CustomField;

use AC\Storage\MetaData;
use ACP\Export\Service;

class Image implements Service
{

    private $storage;

    private $meta_key;

    public function __construct(MetaData $storage, string $meta_key)
    {
        $this->storage = $storage;
        $this->meta_key = $meta_key;
    }

    public function get_value($id): string
    {
        $urls = [];

        foreach ((array)$this->storage->get($id, $this->meta_key) as $url) {
            if (is_numeric($url)) {
                $url = wp_get_attachment_url($url);
            }

            $urls[] = strip_tags($url);
        }

        return implode(', ', $urls);
    }

}
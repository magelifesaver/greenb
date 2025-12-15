<?php

namespace ACP\Export\Model\CustomField;

use AC\Storage\MetaData;
use ACP\Export\Service;

class Date implements Service
{

    private MetaData $storage;

    private string $meta_key;

    public function __construct(MetaData $storage, string $meta_key)
    {
        $this->storage = $storage;
        $this->meta_key = $meta_key;
    }

    public function get_value($id): string
    {
        $timestamp = ac_helper()->date->strtotime(
            $this->storage->get($id, $this->meta_key)
        );

        if ( ! $timestamp) {
            return false;
        }

        // Spreadsheet date format
        return date('Y-m-d H:i:s', $timestamp);
    }

}
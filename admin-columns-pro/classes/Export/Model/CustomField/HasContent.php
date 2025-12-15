<?php

namespace ACP\Export\Model\CustomField;

use AC\Storage\MetaData;
use ACP\Export\Service;

class HasContent implements Service
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
        return $this->storage->get($id, $this->meta_key) ? '1' : '0';
    }

}
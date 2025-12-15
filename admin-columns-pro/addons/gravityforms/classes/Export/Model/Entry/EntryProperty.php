<?php

declare(strict_types=1);

namespace ACA\GravityForms\Export\Model\Entry;

use ACP\Export\Service;
use GFAPI;

class EntryProperty implements Service
{

    private $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function get_value($id): string
    {
        return GFAPI::get_entry($id)[$this->property] ?? '';
    }

}
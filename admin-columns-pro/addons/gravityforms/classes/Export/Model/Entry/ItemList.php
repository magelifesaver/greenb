<?php

namespace ACA\GravityForms\Export\Model\Entry;

use ACA\GravityForms\Field\Field;
use ACP\Export;

class ItemList implements Export\Service
{

    private $field;

    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    public function get_value($id): string
    {
        $items = unserialize($this->field->get_entry_value($id), ['allowed_classes' => false]);

        return is_array($items)
            ? ac_helper()->array->implode_recursive(', ', $items)
            : '';
    }

}
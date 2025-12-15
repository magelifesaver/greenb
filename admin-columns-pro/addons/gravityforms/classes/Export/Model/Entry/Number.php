<?php

namespace ACA\GravityForms\Export\Model\Entry;

use ACA\GravityForms\Field\Field;
use ACP\Export;

class Number implements Export\Service
{

    private $field;

    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    public function get_value($id): string
    {
        $number = $this->field->get_entry_value($id);

        return is_numeric($number)
            ? $number
            : '';
    }

}
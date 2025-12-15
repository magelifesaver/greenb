<?php

namespace ACA\GravityForms\Export\Model\Entry;

use ACA\GravityForms\Field\Field;
use ACP\Export;

class StrippedValue implements Export\Service
{

    private $field;

    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    public function get_value($id): string
    {
        return strip_tags(
            str_replace(
                ['<br/>', '<br>', '<br />'],
                ' ',
                $this->field->get_entry_value($id)
            )
        );
    }

}
<?php

namespace ACA\GravityForms\Export\Model;

use ACA\GravityForms\Export;
use ACA\GravityForms\Field;
use ACP;

class EntryFactory
{

    public function create(Field\Field $field): ?ACP\Export\Service
    {
        switch (true) {
            case $field instanceof Field\Type\Address:
                return new Export\Model\Entry\Address($field);
            case $field instanceof Field\Type\Checkbox:
            case $field instanceof Field\Type\Consent:
                return new Export\Model\Entry\Check($field);
            case $field instanceof Field\Type\Product:
            case $field instanceof Field\Type\ItemList:
                return new Export\Model\Entry\ItemList($field);
            case $field instanceof Field\Type\Number:
                return new Export\Model\Entry\Number($field);
            default:
                return new Export\Model\Entry\StrippedValue($field);
        }
    }

}
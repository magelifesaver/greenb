<?php

namespace Wpae\Metabox;

class AsRepeater {
    public function __invoke($field, $value) {
        if ($this->isSequential($value)) {
            $subfields = $field->subfields;
            $transformedValue = [];

            foreach ($value as $row) {
                $transformedRow = [];

                foreach ($subfields as $index => $subfield) {
                    $transformedRow[$subfield['key']] = $row[$index];
                }

                $transformedValue[] = $transformedRow;
            }

            return $transformedValue;
        }

        return $value;
    }

    /**
     * Check if an repeater field is sequential.
     * For some fields, the repeater rows are stored as a sequential array
     * and we need to manually set the subfield keys.
     * @param array $value
     * @return boolean
     */
    public function isSequential($value) {
        if (!is_array($value)) return false;
        if (empty($value)) return false;
        return wp_is_numeric_array($value) && wp_is_numeric_array($value[0]);
    }
}

class AsTaxonomy {
    public function __invoke($field, $value) {
        if ($value instanceof \WP_Term) {
            return $value->name;
        }

        return '';
    }
}

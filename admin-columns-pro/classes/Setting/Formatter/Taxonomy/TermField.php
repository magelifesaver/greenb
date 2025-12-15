<?php

namespace ACP\Setting\Formatter\Taxonomy;

use AC\Setting\Formatter;
use AC\Type\Value;

class TermField implements Formatter
{

    private string $taxonomy;

    private string $field;

    public function __construct(string $field, string $taxonomy)
    {
        $this->taxonomy = $taxonomy;
        $this->field = $field;
    }

    public function format(Value $value): Value
    {
        return $value->with_value(
            ac_helper()->taxonomy->get_term_field($this->field, $value->get_id(), $this->taxonomy)
        );
    }
}
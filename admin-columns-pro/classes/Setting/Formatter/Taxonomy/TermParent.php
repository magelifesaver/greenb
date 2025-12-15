<?php

namespace ACP\Setting\Formatter\Taxonomy;

use AC\Setting\Formatter;
use AC\Type\Value;

class TermParent implements Formatter
{

    private string $taxonomy;

    public function __construct(string $taxonomy)
    {
        $this->taxonomy = $taxonomy;
    }

    public function format(Value $value): Value
    {
        return new Value(ac_helper()->taxonomy->get_term_field('parent', $value->get_id(), $this->taxonomy) ?: null);
    }
}
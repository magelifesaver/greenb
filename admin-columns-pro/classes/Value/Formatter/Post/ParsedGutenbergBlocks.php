<?php

namespace ACP\Value\Formatter\Post;

use AC;
use AC\Type\Value;

class ParsedGutenbergBlocks implements AC\Setting\Formatter
{

    public function format(Value $value)
    {
        if ( ! has_blocks($value->get_value())) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(parse_blocks($value->get_value()));
    }

}
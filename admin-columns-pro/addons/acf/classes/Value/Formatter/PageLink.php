<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Setting\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;
use AC\Value\Formatter\Post\PostLink;
use AC\Value\Formatter\Post\PostTitle;

class PageLink implements Formatter
{

    private PostTitle $title;

    private PostLink $link;

    public function __construct(PostTitle $title, PostLink $link)
    {
        $this->title = $title;
        $this->link = $link;
    }

    public function format(Value $value)
    {
        $data = $value->get_value();

        if ( ! $data) {
            return $value;
        }

        if (is_scalar($data)) {
            return $this->format_value($data);
        }

        if (is_array($data)) {
            return new ValueCollection($value->get_id(), array_map([$this, 'format_value'], $data));
        }

        return $value;
    }

    private function format_value($value): Value
    {
        // Page ID
        if (is_numeric($value)) {
            return $this->link->format($this->title->format(new Value($value)));
        }

        // Page URL
        if (is_string($value) && ac_helper()->string->is_valid_url($value)) {
            return new Value(sprintf('<a href="%1$s">%1$s</a>', $value));
        }

        return new Value(null);
    }

}
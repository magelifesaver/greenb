<?php

declare(strict_types=1);

namespace ACP\Value\Formatter\Taxonomy;

use AC;
use AC\Type\Value;

class UsedByMenu implements AC\Setting\Formatter
{

    private $size;

    public function __construct(?int $size = null)
    {
        $this->size = $size;
    }

    public function format(Value $value)
    {
        $gravatar = get_avatar($value->get_id(), $this->size);

        if ( ! $gravatar) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($gravatar);
    }

}
<?php

declare(strict_types=1);

namespace ACP\Value\Formatter\User;

use AC;
use AC\Type\Value;

class GravatarUrl implements AC\Setting\Formatter
{

    public function format(Value $value)
    {
        return $value->with_value(get_avatar_url($value->get_id()));
    }

}
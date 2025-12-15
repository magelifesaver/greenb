<?php

namespace ACP\Setting\Formatter\NetworkSite;

use AC\Setting\Formatter;
use AC\Type\Value;

class SwitchToBlog implements Formatter
{

    public function format(Value $value): Value
    {
        switch_to_blog($value->get_id());

        return $value;
    }
}
<?php

namespace ACP\Setting\Formatter\NetworkSite;

use AC\Setting\Formatter;
use AC\Type\Value;

class RestoreCurrentBlog implements Formatter
{

    public function format(Value $value): Value
    {
        restore_current_blog();

        return $value;
    }
}
<?php

namespace ACP\Setting\Formatter\NetworkSite;

use AC\Setting\Formatter;
use AC\Type\Value;

class SiteOption implements Formatter
{

    private $option_name;

    public function __construct(string $option_name)
    {
        $this->option_name = $option_name;
    }

    public function format(Value $value): Value
    {
        return $value->with_value(ac_helper()->network->get_site_option($value->get_id(), $this->option_name));
    }
}
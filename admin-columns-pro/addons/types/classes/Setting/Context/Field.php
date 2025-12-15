<?php

declare(strict_types=1);

namespace ACA\Types\Setting\Context;

use AC\Setting\Config;
use AC\Setting\Context;
use ACA\Types;

class Field extends Context\CustomField
{

    private Types\Field $field;

    public function __construct(Config $config, Types\Field $field)
    {
        parent::__construct($config, $field->get_type(), $field->get_meta_key());
        $this->field = $field;
    }

    public function get_field(): Types\Field
    {
        return $this->field;
    }
}
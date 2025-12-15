<?php

declare(strict_types=1);

namespace ACA\MetaBox\Setting\Context;

use AC\Setting\Config;
use AC\Setting\Context;
use ACA\MetaBox;

class Field extends Context\CustomField
{

    private MetaBox\Field\Field $field;

    public function __construct(Config $config, MetaBox\Field\Field $field)
    {
        parent::__construct($config, $field->get_type(), $field->get_id());

        $this->field = $field;
    }

    public function get_field(): MetaBox\Field\Field
    {
        return $this->field;
    }

    public function is_cloneable(): bool
    {
        return $this->field->is_cloneable();
    }

}
<?php

declare(strict_types=1);

namespace ACA\JetEngine\Setting\Context;

use AC\Setting\Config;
use AC\Setting\Context;
use ACA\JetEngine;

class Field extends Context\CustomField
{

    private JetEngine\Field\Field $field;

    public function __construct(Config $config, JetEngine\Field\Field $field)
    {
        parent::__construct($config, $field->get_type(), $field->get_name());

        $this->field = $field;
    }

    public function get_field(): JetEngine\Field\Field
    {
        return $this->field;
    }

}
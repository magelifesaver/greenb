<?php

declare(strict_types=1);

namespace ACA\Pods\Setting\Context;

use AC\Setting\Config;
use AC\Setting\Context;
use Pods\Whatsit;

class Field extends Context\CustomField
{

    private Whatsit\Field $field;

    public function __construct(Config $config, Whatsit\Field $field)
    {
        parent::__construct($config, $field->get_type(), $field->get_name());

        $this->field = $field;
    }

    public function get_field(): Whatsit\Field
    {
        return $this->field;
    }

}
<?php

namespace ACA\Pods;

use Pods;

class FieldFactory
{

    public function create(Pods\Whatsit\Pod $pod, Pods\Whatsit\Field $fields): Field
    {
        return new Field($pod, $fields);
    }
}
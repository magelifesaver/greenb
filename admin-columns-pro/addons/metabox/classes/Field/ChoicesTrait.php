<?php

namespace ACA\MetaBox\Field;

trait ChoicesTrait
{

    public function get_choices(): array
    {
        return isset($this->settings['options'])
            ? (array)$this->settings['options']
            : [];
    }

}
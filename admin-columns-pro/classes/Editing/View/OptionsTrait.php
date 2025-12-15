<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

trait OptionsTrait
{

    public function set_options(array $options): View
    {
        return $this->set('options', $options);
    }

}
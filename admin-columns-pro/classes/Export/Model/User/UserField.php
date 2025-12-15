<?php

namespace ACP\Export\Model\User;

use ACP\Export\Service;

class UserField implements Service
{

    private $field;

    public function __construct(string $field)
    {
        $this->field = $field;
    }

    public function get_value($id): string
    {
        return get_userdata($id)->{$this->field} ?? '';
    }

}
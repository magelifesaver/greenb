<?php

namespace ACP\Export\Model\User;

use ACP\Export\Service;

class Nicename implements Service
{

    public function get_value($id): string
    {
        return get_userdata($id)->user_nicename ?? '';
    }

}
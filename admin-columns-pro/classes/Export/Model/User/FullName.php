<?php

namespace ACP\Export\Model\User;

use ACP\Export\Service;
use WP_User;

class FullName implements Service
{

    public function get_value($id): string
    {
        $user = get_userdata($id);

        if ( ! $user instanceof WP_User) {
            return '';
        }

        return trim($user->first_name . ' ' . $user->last_name);
    }

}
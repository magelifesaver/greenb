<?php

declare(strict_types=1);

namespace ACA\GravityForms\Export\Model\Entry;

use ACP\Export\Service;
use GFAPI;
use WP_User;

class User implements Service
{

    public function get_value($id): string
    {
        $user_id = GFAPI::get_entry($id)['created_by'] ?? null;

        $user = get_userdata($user_id);

        if ( ! $user instanceof WP_User) {
            return '';
        }

        return ac_helper()->user->get_formatted_name($user);
    }

}
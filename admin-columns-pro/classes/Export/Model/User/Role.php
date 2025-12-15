<?php

namespace ACP\Export\Model\User;

use AC;
use ACP\Export\Service;

class Role implements Service
{

    private $allow_non_editable_roles;

    private $user_roles;

    public function __construct(bool $allow_non_editable_roles)
    {
        $this->allow_non_editable_roles = $allow_non_editable_roles;
    }

    private function get_allowed_roles(): AC\Type\UserRoles
    {
        static $editable_roles;

        if (null === $editable_roles) {
            $editable_roles = (new AC\Helper\UserRoles())->find_all($this->allow_non_editable_roles);
        }

        return $editable_roles;
    }

    private function get_allowed_role(string $role): ?AC\Type\UserRole
    {
        foreach ($this->get_allowed_roles() as $editable_role) {
            if ($role === $editable_role->get_name()) {
                return $editable_role;
            }
        }

        return null;
    }

    public function get_value($id): string
    {
        $user = get_userdata($id);

        if ( ! $user) {
            return '';
        }

        $allowed_roles = $this->get_allowed_roles();

        $labels = [];

        foreach ($user->roles as $role_name) {
            $role_object = $this->get_allowed_role($role_name);

            if ( ! $role_object) {
                continue;
            }

            $labels[] = $role_object->get_translate_label();
        }

        return implode(', ', $labels);
    }

}
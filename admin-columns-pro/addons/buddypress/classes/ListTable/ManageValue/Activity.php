<?php

declare(strict_types=1);

namespace ACA\BP\ListTable\ManageValue;

use AC\CellRenderer;
use AC\TableScreen\ManageValueService;

class Activity implements ManageValueService
{

    private CellRenderer $renderable;

    public function __construct(CellRenderer $renderable)
    {
        $this->renderable = $renderable;
    }

    public function register(): void
    {
        add_filter('bp_activity_admin_get_custom_column', [$this, 'render_value'], 100, 3);
    }

    public function render_value(...$args)
    {
        [$value, $column_name, $group] = $args;

        return $this->renderable->render_cell((string)$column_name, (int)$group['id']) ?? $value;
    }

}
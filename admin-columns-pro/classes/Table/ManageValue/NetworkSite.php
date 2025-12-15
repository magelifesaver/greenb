<?php

declare(strict_types=1);

namespace ACP\Table\ManageValue;

use AC\CellRenderer;
use AC\TableScreen\ManageValueService;
use DomainException;

class NetworkSite implements ManageValueService
{

    private CellRenderer $renderable;

    private int $priority;

    public function __construct(
        CellRenderer $renderable,
        int $priority = 100
    ) {
        $this->renderable = $renderable;
        $this->priority = $priority;
    }

    public function register(): void
    {
        if (did_action('manage_sites_custom_column')) {
            throw new DomainException("Method should be called before the action triggers.");
        }

        add_action("manage_sites_custom_column", [$this, 'render_value'], $this->priority, 2);
    }

    public function render_value(...$args): void
    {
        [$column_id, $row_id] = $args;

        echo $this->renderable->render_cell((string)$column_id, (int)$row_id);
    }

}
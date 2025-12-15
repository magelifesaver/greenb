<?php

declare(strict_types=1);

namespace ACA\WC\ListTable\ManageValue;

use AC\CellRenderer;
use AC\TableScreen\ManageValueService;
use DomainException;

class Order implements ManageValueService
{

    private string $order_type;

    private CellRenderer $renderable;

    public function __construct(string $order_type, CellRenderer $renderable)
    {
        $this->order_type = $order_type;
        $this->renderable = $renderable;
    }

    public function register(): void
    {
        $action = sprintf('woocommerce_%s_list_table_custom_column', $this->order_type);

        if (did_action($action)) {
            throw new DomainException(sprintf("Method should be called before the %s action.", $action));
        }

        add_action($action, [$this, 'render_value'], 100, 2);
    }

    public function render_value(...$args): void
    {
        [$column_name, $order] = $args;

        echo $this->renderable->render_cell((string)$column_name, $order->get_id());
    }

}
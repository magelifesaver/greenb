<?php

declare(strict_types=1);

namespace ACP\Table\ManageValue;

use AC\CellRenderer;
use AC\TableScreen\ManageValueService;
use AC\Type\TaxonomySlug;
use DomainException;

class Taxonomy implements ManageValueService
{

    private TaxonomySlug $taxonomy;

    private CellRenderer $renderable;

    private int $priority;

    public function __construct(
        TaxonomySlug $taxonomy,
        CellRenderer $renderable,
        int $priority = 100
    ) {
        $this->taxonomy = $taxonomy;
        $this->renderable = $renderable;
        $this->priority = $priority;
    }

    /**
     * @see WP_Terms_List_Table::column_default
     */
    public function register(): void
    {
        $action = sprintf("manage_%s_custom_column", $this->taxonomy);

        if (did_filter($action)) {
            throw new DomainException("Method should be called before the %s action.", $action);
        }

        add_filter($action, [$this, 'render_value'], $this->priority, 3);
    }

    public function render_value(...$args)
    {
        [$value, $column_id, $row_id] = $args;

        return $this->renderable->render_cell((string)$column_id, $row_id) ?? $value;
    }

}
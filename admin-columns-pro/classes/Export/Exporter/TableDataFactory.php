<?php

declare(strict_types=1);

namespace ACP\Export\Exporter;

use AC;
use AC\ColumnIterator;
use AC\Exception\ValueNotFoundException;
use AC\Setting\ContextFactory;
use ACP\Column;
use ACP\Export\ApplyFilter\ExportValue;
use ACP\Export\EscapeData;

class TableDataFactory
{

    use AC\Column\ColumnLabelTrait;

    private ContextFactory $context_factory;

    private AC\TableScreen $table_screen;

    private EscapeData $escaper;

    private ?AC\CellRenderer $renderer = null;

    public function __construct(
        ContextFactory $context_factory,
        AC\TableScreen $table_screen,
        ?EscapeData $escaper = null
    ) {
        $this->escaper = $escaper ?? new EscapeCsv();
        $this->context_factory = $context_factory;
        $this->table_screen = $table_screen;
    }

    public function create(ColumnIterator $columns, array $row_ids, bool $has_labels): TableData
    {
        $data = new TableData();

        $this->add_cells($data, $columns, $row_ids);

        if ($has_labels) {
            $this->add_headers($data, $columns);
        }

        return $data;
    }

    private function add_cells(TableData $data, ColumnIterator $columns, array $row_ids): void
    {
        foreach ($row_ids as $row_id) {
            /**
             * @var $column Column
             */
            foreach ($columns as $column) {
                $data->add_cell(
                    (string)$row_id,
                    (string)$column->get_id(),
                    $this->cell_value(
                        $column,
                        (string)$row_id
                    )
                );
            }
        }
    }

    private function apply_escape_data(AC\Column $column): bool
    {
        return (bool)apply_filters(
            'ac/export/render/escape',
            true,
            $this->context_factory->create($column, $this->table_screen),
            $this->table_screen
        );
    }

    private function get_headers(ColumnIterator $columns): array
    {
        $headers = [];

        /**
         * @var AC\Column $column
         */
        foreach ($columns as $column) {
            $label = $this->get_column_label($column);

            if ($this->apply_escape_data($column)) {
                $label = $this->escaper->escape($label);
            }

            $headers[(string)$column->get_id()] = $label;
        }

        return apply_filters('ac/export/row_headers', $headers, $this->table_screen);
    }

    private function add_headers(TableData $data, ColumnIterator $columns): void
    {
        foreach ($this->get_headers($columns) as $column_id => $label) {
            $data->add_header(
                (string)$column_id,
                (string)$label
            );
        }
    }

    private function get_column_value(Column $column, $row_id): string
    {
        $service = $column->export();

        if ($service) {
            try {
                return $service->get_value((int)$row_id);
            } catch (ValueNotFoundException $e) {
                return '';
            }
        }

        $cell_renderer = $this->get_cell_renderer();

        if ($cell_renderer) {
            // uses `strip_tags` to strip HTML from original columns
            return strip_tags(
                $cell_renderer->render_cell((string)$column->get_id(), $row_id)
            );
        }

        return '';
    }

    private function get_cell_renderer(): ?AC\CellRenderer
    {
        if (null === $this->renderer && $this->table_screen instanceof AC\TableScreen\ListTable) {
            $this->renderer = $this->table_screen->list_table();
        }

        return $this->renderer;
    }

    private function cell_value(Column $column, string $row_id): string
    {
        $value = $this->get_column_value($column, $row_id);

        $filter = new ExportValue($this->table_screen);
        $value = $filter->apply_filters(
            $value,
            $this->context_factory->create($column, $this->table_screen),
            $row_id
        );

        if ($this->apply_escape_data($column)) {
            return $this->escaper->escape($value);
        }

        return $value;
    }

}
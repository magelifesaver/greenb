<?php

declare(strict_types=1);

namespace ACP\Search;

use AC;
use AC\Asset\Location\Absolute;
use AC\ListScreen;
use AC\Request;
use ACP;
use ACP\Search\Asset\Script;
use ACP\Search\Type\SegmentKey;

class TableScriptFactory
{

    use AC\Column\ColumnLabelTrait;

    private Absolute $location;

    private AC\Setting\ContextFactory $context_factory;

    public function __construct(Absolute $location, AC\Setting\ContextFactory $context_factory)
    {
        $this->location = $location;
        $this->context_factory = $context_factory;
    }

    public function create(
        ListScreen $list_screen,
        Request $request,
        ?SegmentKey $segment_key = null
    ): Script\Table {
        return new Script\Table(
            'aca-search-table',
            $this->location->with_suffix('assets/search/js/table.bundle.js'),
            $this->get_filters($list_screen),
            $request,
            $list_screen,
            $segment_key
        );
    }

    private function get_filter_label(AC\Column $column): string
    {
        return $this->get_column_label($column);
    }

    private function get_filters(ListScreen $list_screen): array
    {
        $filters = [];

        foreach ($list_screen->get_columns() as $column) {
            if ( ! $column instanceof ACP\Column) {
                continue;
            }

            $setting = $column->get_setting('search');

            if ( ! $setting) {
                continue;
            }

            $is_active = $setting->get_input()->get_value() === 'on';

            if ( ! $is_active) {
                continue;
            }

            if ( ! $column->search()) {
                continue;
            }

            $filter = new Middleware\Filter(
                (string)$column->get_id(),
                $column->search(),
                $this->get_filter_label($column)
            );

            $context = $this->context_factory->create($column, $list_screen->get_table_screen());
            $filters[] = apply_filters(
                'ac/search/filters',
                $filter(),
                $context,
                $list_screen->get_table_screen(),
                $list_screen->get_id()
            );
        }

        return $filters;
    }

}
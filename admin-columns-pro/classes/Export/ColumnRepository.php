<?php

declare(strict_types=1);

namespace ACP\Export;

use AC\ColumnCollection;
use AC\ListScreen;
use AC\Setting\ContextFactory;
use ACP\Export\ColumnRepository\Filter;
use ACP\Table\TableSupport;

class ColumnRepository
{

    private ContextFactory $context_factory;

    public function __construct(ContextFactory $context_factory)
    {
        $this->context_factory = $context_factory;
    }

    public function find_all(ListScreen $list_screen): ColumnCollection
    {
        if ( ! TableSupport::is_export_enabled($list_screen)) {
            return new ColumnCollection();
        }

        return (new Filter\ExportableColumns($this->context_factory))->filter(
            $list_screen->get_columns()
        );
    }

}
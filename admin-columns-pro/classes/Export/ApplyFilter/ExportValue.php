<?php

declare(strict_types=1);

namespace ACP\Export\ApplyFilter;

use AC\Setting\Context;
use AC\TableScreen;

class ExportValue
{

    private TableScreen $table_screen;

    public function __construct(TableScreen $table_screen)
    {
        $this->table_screen = $table_screen;
    }

    public function apply_filters(string $value, Context $context, string $row_id): string
    {
        return (string)apply_filters(
            'ac/export/render',
            $value,
            $context,
            $row_id,
            $this->table_screen
        );
    }
}
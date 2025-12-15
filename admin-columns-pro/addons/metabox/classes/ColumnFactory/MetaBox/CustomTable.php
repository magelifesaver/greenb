<?php

namespace ACA\MetaBox\ColumnFactory\MetaBox;

use AC;
use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACA\MetaBox\Value;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat;

class CustomTable extends AdvancedColumnFactory
{

    use ConditionalFormat\ConditionalFormatTrait;

    protected function get_group(): ?string
    {
        return 'metabox_custom';
    }

    public function get_column_type(): string
    {
        return 'column-mb-custom_table';
    }

    public function get_label(): string
    {
        return __('Custom Table', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Value\Formatter\Post\Meta('settings'),
            new Value\Formatter\MetaBox\CustomTable(),
        ]);
    }

}
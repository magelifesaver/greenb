<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\Activity;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACA\BP\Value\Formatter;
use ACP;

class ComponentFactory extends ACP\Column\AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-activity_component';
    }

    public function get_label(): string
    {
        return __('Component', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'buddypress';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->with_formatter(new Formatter\Activity\Component());
    }

}
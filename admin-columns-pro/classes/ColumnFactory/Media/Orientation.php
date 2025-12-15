<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACP;
use ACP\Value\Formatter;

class Orientation extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    public function get_column_type(): string
    {
        return 'column-orientation';
    }

    public function get_label(): string
    {
        return __('Orientation', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'media';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Formatter\Media\Orientation(),
        ]);
    }

}
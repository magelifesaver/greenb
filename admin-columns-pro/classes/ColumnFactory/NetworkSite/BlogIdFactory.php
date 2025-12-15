<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\Id;
use ACP\Column\AdvancedColumnFactory;

class BlogIdFactory extends AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-blog_id';
    }

    public function get_label(): string
    {
        return __('Blog ID', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Id(),
        ]);
    }

}
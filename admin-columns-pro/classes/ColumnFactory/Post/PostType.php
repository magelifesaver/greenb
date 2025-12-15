<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACP;
use ACP\Editing;

class PostType extends ACP\Column\AdvancedColumnFactory
{

    public function get_label(): string
    {
        return __('Post Type', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-post_type';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new ACP\Value\Formatter\Post\PostType(),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\PostType();
    }

}
<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value;
use ACP;
use ACP\Search;
use ACP\Value\Formatter;

class UploadedToPostType extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    public function get_column_type(): string
    {
        return 'column-post_type';
    }

    public function get_label(): string
    {
        return __('Uploaded to Post Type', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'media';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Value\Formatter\Post\PostParentId(),
            new Formatter\Post\PostType(),
        ]);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Media\PostType();
    }

}
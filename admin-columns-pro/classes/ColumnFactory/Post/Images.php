<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter;
use ACP;
use ACP\Export;
use ACP\Sorting;
use ACP\Value;

class Images extends ACP\Column\AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-images';
    }

    public function get_label(): string
    {
        return __('Images', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Formatter\Post\PostContent(),
            new Value\Formatter\ImageUrls(),
            new Value\Formatter\Post\ImagesExtendedLink(
                new Value\ExtendedValue\Post\PostImages()
            ),
        ]);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\ImageFileSizes();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\Post\ImageFileSizes();
    }

}
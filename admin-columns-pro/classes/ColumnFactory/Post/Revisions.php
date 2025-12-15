<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACP;
use ACP\Sorting;
use ACP\Value;

class Revisions extends ACP\Column\AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-revisions';
    }

    public function get_label(): string
    {
        return __('Revisions', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new ACP\Value\Formatter\Post\RevisionCount(),
            new ACP\Value\Formatter\Post\ExtendedRevisionLink(new ACP\Value\ExtendedValue\Post\Revisions()),
        ]);
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\Formatter(new Value\Formatter\Post\RevisionCount());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Revisions();
    }

}
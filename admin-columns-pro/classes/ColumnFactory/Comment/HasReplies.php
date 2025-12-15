<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Comment;

use AC;
use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACP;
use ACP\Export;
use ACP\Search;

class HasReplies extends ACP\Column\AdvancedColumnFactory
{

    public function get_column_type(): string
    {
        return 'column-has_replies';
    }

    public function get_label(): string
    {
        return __('Has Replies', 'codepress-admin-columns');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Comment\HasReplies();
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new ACP\Value\Formatter\Comment\ReplyCount(),
            new AC\Value\Formatter\YesNoIcon(),
        ]);
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\Formatter(
            new ACP\Value\Formatter\Comment\ReplyCount()
        );
    }

}
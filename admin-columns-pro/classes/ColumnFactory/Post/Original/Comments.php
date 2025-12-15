<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Export;
use ACP\Search;
use ACP\Search\Comparison\Post\CommentCount;

class Comments extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\Post\Comments();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\CommentCount([
            CommentCount::STATUS_APPROVED,
        ]);
    }

}
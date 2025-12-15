<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Editing;
use ACP\Export;
use ACP\Search;

class Title extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?Export\Service
    {
        return new Export\Model\Post\Title();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\Title();
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Basic(
            (new Editing\View\Text())->set_placeholder(__('Add title', 'codepress-admin-columns')),
            new Editing\Storage\Post\Field('post_title')
        );
    }

}
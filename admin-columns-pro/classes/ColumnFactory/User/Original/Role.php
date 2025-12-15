<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Editing;
use ACP\Export;
use ACP\Search;
use ACP\Sorting;

class Role extends DefaultColumnFactory
{

    private function get_meta_key(): string
    {
        global $wpdb;

        return $wpdb->get_blog_prefix() . 'capabilities'; // WPMU compatible
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\User\Role(false);
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\User\Role(false);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\Roles($this->get_meta_key());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\User\Role($this->get_meta_key());
    }

}
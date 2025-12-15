<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Column\FeatureSettingBuilder;
use ACP\Editing;
use ACP\Export;
use ACP\Search;

class Email extends DefaultColumnFactory
{

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_bulk_edit();
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\User\Email($this->get_label());
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\User\Email();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\User\Email();
    }

}
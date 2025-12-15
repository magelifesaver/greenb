<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order\Original;

use AC;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use ACA\WC;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Filtering\Setting\ComponentFactory\FilteringDate;

class OrderDateFactory extends DefaultColumnFactory
{

    private $filter_date;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        FilteringDate $filter_date
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $type, $label);
        $this->filter_date = $filter_date;
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_search(null, $this->filter_date);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\Order\Date\CreatedDate();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\FormatterCollection(
            new FormatterCollection([
                new WC\Value\Formatter\Order\Date\CreatedDate(),
                new AC\Value\Formatter\Timestamp(),
                new AC\Value\Formatter\Date\DateFormat('Y-m-d H:i:s'),
            ])
        );
    }

}
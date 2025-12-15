<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Robots;

use AC\MetaType;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\YesIcon;
use ACA\RankMath\Editing;
use ACA\RankMath\Search;
use ACA\RankMath\Value\Formatter\HasRobotKey;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

abstract class SingleDirective extends ACP\Column\AdvancedColumnFactory
{

    private MetaType $meta_type;

    abstract protected function get_key(): string;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        MetaType $meta_type
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->meta_type = $meta_type;
    }

    public function get_column_type(): string
    {
        return 'column-rankmath-robots_' . $this->get_key();
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new HasRobotKey($this->get_key(), $this->meta_type))
                     ->add(new YesIcon());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Robots($this->get_key());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Robots($this->get_key(), $this->meta_type);
    }

    protected function get_group(): string
    {
        return 'rank-math-robots-meta';
    }

}
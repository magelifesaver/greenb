<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\BeforeAfter;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Setting\ComponentFactory\NetworkSite\SiteOptions;
use ACP\Setting\Formatter\NetworkSite\SiteOption;

class OptionsFactory extends AdvancedColumnFactory
{

    private $site_options;

    private $before_after_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        SiteOptions $site_options,
        BeforeAfter $before_after_factory
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->before_after_factory = $before_after_factory;
        $this->site_options = $site_options;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->site_options->create($config),
            $this->before_after_factory->create($config),
        ]);
    }

    public function get_column_type(): string
    {
        return 'column-msite_options';
    }

    public function get_label(): string
    {
        return __('Options', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = new FormatterCollection([
            new SiteOption($config->get('field', '')),
        ]);

        return $formatters->merge(parent::get_formatters($config));
    }

}
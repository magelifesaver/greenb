<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Setting\ComponentFactory\NetworkSite\ThemeStatus;
use ACP\Setting\Formatter\NetworkSite\Themes;

class ThemeFactory extends AdvancedColumnFactory
{

    private ThemeStatus $theme_status;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ThemeStatus $theme_status
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->theme_status = $theme_status;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Themes($config->get('theme_status', '')),
        ]);
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->theme_status->create($config),
        ]);
    }

    public function get_column_type(): string
    {
        return 'column-msite_theme';
    }

    public function get_label(): string
    {
        return __('Theme', 'codepress-admin-columns');
    }

}
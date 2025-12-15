<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Post;

use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use AC\Type\Value;
use AC\Value\Formatter;
use ACP\Column\FeatureSettingBuilderFactory;

final class NoFollow extends MetaBooleanFactory
{

    public function __construct(
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        DefaultSettingsBuilder $default_settings_builder
    ) {
        parent::__construct(
            $feature_setting_builder_factory,
            $default_settings_builder,
            'column-sp_nofollow',
            __('nofollow?', 'wp-seopress-pro'),
            '_seopress_robots_follow'
        );
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(
                         new Formatter\ConditionalValue(
                             new Value('yes'),
                             new Formatter\YesIcon()
                         )
                     );
    }

}
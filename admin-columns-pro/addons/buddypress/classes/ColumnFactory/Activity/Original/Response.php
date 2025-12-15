<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\Activity\Original;

use AC\Setting\DefaultSettingsBuilder;
use ACP\Column\DefaultColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;

class Response extends DefaultColumnFactory
{

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        ?bool $add_sort = true
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $type,
            $label,
            $add_sort,
            false
        );
    }

}
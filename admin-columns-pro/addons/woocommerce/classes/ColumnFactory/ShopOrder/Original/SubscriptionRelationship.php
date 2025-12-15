<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder\Original;

use AC\Setting\DefaultSettingsBuilder;
use ACP\Column\DefaultColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;

class SubscriptionRelationship extends DefaultColumnFactory
{

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $type,
            $label,
            true,
            // disable export
            false
        );
    }

}
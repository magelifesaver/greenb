<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactory\Meta;

use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TableScreenContext;
use ACA\ACF\ColumnFactory;
use ACA\ACF\Field;
use ACA\ACF\Setting\FieldComponentFactory;
use ACA\ACF\Value;
use ACP\Column\FeatureSettingBuilderFactory;

class AdvancedColumnFieldFactory extends ColumnFactory\AcfFactory
{

    protected TableScreenContext $table_context;

    protected FieldComponentFactory $component_factory;

    protected Value\ValueFormatterFactory $formatter_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        TableScreenContext $table_context,
        FieldComponentFactory $component_factory
    ) {
        parent::__construct(
            $feature_settings_builder_factory,
            $default_settings_builder,
            $column_type,
            $label,
            $field,
            $component_factory
        );

        $this->table_context = $table_context;
    }

}
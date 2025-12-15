<?php

declare(strict_types=1);

namespace ACA\Types\ColumnFactory;

use AC;
use AC\MetaType;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use AC\Type\TableScreenContext;
use ACA\Types\Field;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Column\FormatterSettingsTrait;

class FieldFactory extends AdvancedColumnFactory
{

    use FormatterSettingsTrait;

    protected string $column_type;

    protected string $label;

    protected Field $field;

    protected TableScreenContext $context;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        TableScreenContext $context,
        Field $field
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->column_type = $column_type;
        $this->label = $label;
        $this->context = $context;
        $this->field = $field;
    }

    protected function get_group(): string
    {
        return 'types';
    }

    public function get_label(): string
    {
        return $this->label;
    }

    protected function get_meta_type(): MetaType
    {
        return $this->context->get_meta_type();
    }

    public function get_column_type(): string
    {
        return $this->column_type;
    }

    protected function get_base_formatters(): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Value\Formatter\MetaCollection($this->get_meta_type(), $this->field->get_meta_key()),
        ]);
    }

    protected function get_post_formatters(): FormatterCollection
    {
        $formatters = new FormatterCollection();

        if ($this->field->is_repeatable()) {
            $formatters->add(new AC\Value\Formatter\SmallBlocks());
            $formatters->add(new AC\Value\Formatter\Collection\Separator('<br>'));
        }

        return $formatters;
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return $this->get_base_formatters()
                    ->merge($this->get_formatters_from_settings($this->get_settings($config)))
                    ->merge($this->get_post_formatters());
    }

}
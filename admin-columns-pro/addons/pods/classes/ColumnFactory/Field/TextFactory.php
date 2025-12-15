<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC\Setting\ComponentFactory\StringLimit;
use AC\Setting\ConditionalComponentFactoryCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\PostTypeSlug;
use ACA\Pods\ColumnFactory\FieldFactory;
use ACA\Pods\Editing;
use ACA\Pods\Field;
use ACA\Pods\Sorting\DefaultSortingTrait;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing\View;

final class TextFactory extends FieldFactory
{

    use DefaultSortingTrait;
    use MetaQueryTrait;
    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private StringLimit $string_limit;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        StringLimit $string_limit,
        ?PostTypeSlug $post_type = null
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $column_type, $label, $field);
        $this->string_limit = $string_limit;
        $this->post_type = $post_type;
    }

    protected function add_component_factories(ConditionalComponentFactoryCollection $factories): void
    {
        $factories->add($this->string_limit);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\FieldStorage(
            (new Editing\StorageFactory())->create_by_field($this->field),
            (new View\Text())
                ->set_placeholder($this->field->get_label())
                ->set_max_length((int)$this->field->get_arg('text_max_length'))
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\SearchableText(
            $this->field->get_name(),
            $this->get_query_meta()
        );
    }

}
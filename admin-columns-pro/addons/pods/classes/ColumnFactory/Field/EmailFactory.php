<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

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

class EmailFactory extends FieldFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use DefaultSortingTrait;
    use MetaQueryTrait;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $column_type,
        string $label,
        Field $field,
        ?PostTypeSlug $post_type = null
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $column_type, $label, $field);
        $this->post_type = $post_type;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\FieldStorage(
            (new Editing\StorageFactory())->create_by_field($this->field),
            (new View\Email())
                ->set_clear_button('1' === $this->field->get_arg('email_allow_empty'))
                ->set_placeholder($this->field->get_label())
                ->set_max_length((int)$this->field->get_arg('email_max_length'))
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
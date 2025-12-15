<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\PostProperty;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use AC\Type\PostTypeSlug;
use AC\Value\Formatter\Collection\Separator;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Export;
use ACP\Search;
use ACP\Sorting;
use ACP\Value\Formatter\Post\AncestorIds;

class Ancestor extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private PostProperty $post_property;

    private PostTypeSlug $post_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        PostProperty $post_property,
        PostTypeSlug $post_type
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->post_property = $post_property;
        $this->post_type = $post_type;
    }

    public function get_label(): string
    {
        return __('Ancestors', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-ancestors';
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->post_property->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new AncestorIds())
                     ->add(new Separator('<span class="dashicons dashicons-arrow-right-alt2"></span>'));
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\Post\Ancestors();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\Ancestors();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Depth((string)$this->post_type);
    }

}
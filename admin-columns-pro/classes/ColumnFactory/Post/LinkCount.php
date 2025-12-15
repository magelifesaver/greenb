<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\Post\PostContent;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Sorting;

class LinkCount extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;

    private $link_count_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ACP\Setting\ComponentFactory\LinkCountType $link_count_type
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);

        $this->link_count_type = $link_count_type;
    }

    public function get_label(): string
    {
        return __('Link Count', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-linkcount';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new PostContent(),
            new ACP\Value\Formatter\LinkCount(
                $this->get_link_count_type($config),
                $this->get_internal_domains()
            ),
        ]);
    }

    private function get_internal_domains(): array
    {
        return (array)apply_filters('ac/column/linkcount/domains', [home_url()]);
    }

    private function get_link_count_type(Config $config): string
    {
        return $config->get('link_count_type', '');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->link_count_type->create($config),
        ]);
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\Post\LinkCount(
            $this->get_link_count_type($config),
            $this->get_internal_domains()
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        switch ($this->get_link_count_type($config)) {
            case 'internal' :
                return new Sorting\Model\Post\LinkCount($this->get_internal_domains());
            case 'external' :
                return null;
            default :
                return new Sorting\Model\Post\LinkCount(['http']);
        }
    }

}
<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ProductVariation;

use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class ProductTaxonomy extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    private ComponentFactory\Product\ProductTaxonomy $product_taxonomy;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ComponentFactory\Product\ProductTaxonomy $product_taxonomy
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->product_taxonomy = $product_taxonomy;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->product_taxonomy->create($config));
    }

    public function get_column_type(): string
    {
        return 'variation_product_taxonomy';
    }

    public function get_label(): string
    {
        return __('Product Taxonomy', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Post\PostParentId())
                     ->add(new Formatter\Post\PostTerms($config->get('taxonomy', '')))
                     ->add(new Formatter\Term\TermProperty('name'))
                     ->add(new Formatter\Term\TermLink('edit'));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ProductVariation\ProductTaxonomy($config->get('taxonomy', ''));
    }

}
<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACA\MLA\Export;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;

class Taxonomy extends DefaultColumnFactory
{

    private TaxonomySlug $taxonomy;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        string $type,
        string $label,
        TaxonomySlug $taxonomy
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder, $type, $label);
        $this->taxonomy = $taxonomy;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Post\Taxonomy((string)$this->taxonomy, false);
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\Taxonomy((string)$this->taxonomy);
    }

}
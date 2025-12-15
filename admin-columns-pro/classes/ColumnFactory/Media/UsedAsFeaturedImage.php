<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media;

use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use AC\Value;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Search;
use ACP\Value\Formatter;

class UsedAsFeaturedImage extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private ACP\Setting\ComponentFactory\Media\FeaturedImageDisplay $featured_image_display;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ACP\Setting\ComponentFactory\Media\FeaturedImageDisplay $featured_image_display
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);

        $this->featured_image_display = $featured_image_display;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->featured_image_display->create($config),
        ]);
    }

    public function get_column_type(): string
    {
        return 'column-used_as_featured_image';
    }

    public function get_label(): string
    {
        return __('Featured Image', 'codepress-admin-columns');
    }

    protected function get_group(): ?string
    {
        return 'media-image';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = new FormatterCollection([
            new Formatter\Media\PostsHavingFeaturedImageCollection(),
        ]);

        switch ($config->get('featured_image_display')) {
            case 'count':
                $formatters->add(new Value\Formatter\Count());
                break;
            case 'title':
                $formatters->add(new Value\Formatter\Post\PostTitle());
                $formatters->add(new Value\Formatter\CharacterLimit(40));
                $formatters->add(new Value\Formatter\Post\PostLink('edit_post'));
                break;
            default:
                $formatters->add(new Value\Formatter\Count());
                $formatters->add(new Value\Formatter\YesNoIcon());
        }

        return $formatters;
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        switch ($config->get('featured_image_display')) {
            case 'title':
                return parent::get_export($config);
            case 'count':
            default:
                $formatters = new FormatterCollection([
                    new Formatter\Media\PostsHavingFeaturedImageCollection(),
                    new Value\Formatter\Count(),
                ]);

                return new ACP\Export\Model\FormatterCollection($formatters);
        }
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Media\UsedAsFeaturedImage();
    }

}
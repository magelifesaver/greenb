<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User;

use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use ACP;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Setting\ComponentFactory\User\GravatarImageSize;
use ACP\Value\Formatter;

class Gravatar extends AdvancedColumnFactory
{

    private GravatarImageSize $image_size;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        GravatarImageSize $image_size
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->image_size = $image_size;
    }

    public function get_column_type(): string
    {
        return 'column-gravatar';
    }

    public function get_label(): string
    {
        return __('Profile Picture', 'codepress-admin-columns');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->image_size->create($config),
        ]);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Formatter\User\Gravatar((int)$config->get('gravatar_size', 96)),
        ]);
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\FormatterCollection(new FormatterCollection([
            new Formatter\User\GravatarUrl(),
        ]));
    }

}
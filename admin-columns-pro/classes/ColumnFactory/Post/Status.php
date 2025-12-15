<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Post;

use AC;
use AC\Setting\Config;
use AC\Setting\Context;
use AC\Type\PostTypeSlug;
use AC\Value\Formatter;
use ACP;
use ACP\Column\EnhancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;
use ACP\Editing\ApplyFilter\PostStatus;
use ACP\Search;
use ACP\Sorting;

class Status extends EnhancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private PostTypeSlug $post_type;

    public function __construct(
        AC\ColumnFactory\Post\StatusFactory $column_factory,
        FeatureSettingBuilderFactory $feature_setting_builder_factory,
        PostTypeSlug $post_type
    ) {
        parent::__construct($column_factory, $feature_setting_builder_factory);

        $this->post_type = $post_type;
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\Formatter(
            new Formatter\Post\PostStatus()
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Post\PostStatus(
            (string)$this->post_type,
            new PostStatus(new Context($config))
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\Post\Status((string)$this->post_type);
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\Post\Status();
    }

}
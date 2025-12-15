<?php

namespace ACA\MetaBox\ColumnFactory\PostType;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACA\MetaBox\Value;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat;

class Taxonomies extends AdvancedColumnFactory
{

    use ConditionalFormat\ConditionalFormatTrait;

    protected function get_group(): ?string
    {
        return 'metabox_custom';
    }

    public function get_column_type(): string
    {
        return 'column-mb-pt_taxonomies';
    }

    public function get_label(): string
    {
        return __('Taxonomies', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Value\Formatter\PostType\PostTypeSetting('taxonomies'))
                     ->add(new Value\Formatter\PostType\TaxonomyLabel());
    }

}
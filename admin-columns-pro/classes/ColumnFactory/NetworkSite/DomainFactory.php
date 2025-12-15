<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACP;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Setting\Formatter\NetworkSite\SiteProperty;

class DomainFactory extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    public function get_column_type(): string
    {
        return 'column-msite_domain';
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return null;
    }

    public function get_label(): string
    {
        return __('Domain', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new SiteProperty('domain'),
        ]);
    }

}
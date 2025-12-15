<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\NetworkSite;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use ACP\Setting\Formatter\NetworkSite\UploadSpace;

class UploadSpaceFactory extends AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    public function get_column_type(): string
    {
        return 'column-msite_uploadspace';
    }

    public function get_label(): string
    {
        return __('Storage Space', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new UploadSpace(),
        ]);
    }

}
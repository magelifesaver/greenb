<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Export\Order\DownloadPermissionGranted;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class DownloadPermissionGrantedFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Download Permission Granted', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_download_permissions_granted';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Order\DownloadPermissionGranted());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\OperationalData('download_permission_granted');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Order\DownloadPermissionGranted();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new DownloadPermissionGranted();
    }

}
<?php

declare(strict_types=1);

namespace ACA\WC\Type;

use AC\Type\Url\ListTable;

class OrderSubscriptionTableUrl extends ListTable
{

    public function __construct()
    {
        parent::__construct('admin.php');

        $this->add('page', 'wc-orders--shop_subscription');
    }
}
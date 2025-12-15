<?php

namespace ACA\WC\Export\Order;

use ACP;

class DownloadPermissionGranted implements ACP\Export\Service
{

    public function get_value($id): string
    {
        $granted = wc_get_order($id)->get_download_permissions_granted();

        return $granted
            ? 1
            : 0;
    }

}
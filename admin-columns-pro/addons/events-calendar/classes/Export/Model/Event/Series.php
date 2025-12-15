<?php

declare(strict_types=1);

namespace ACA\EC\Export\Model\Event;

use ACP;

class Series implements ACP\Export\Service
{

    public function get_value($id): string
    {
        $series_id = tec_event_series($id);
        if ( ! $series_id) {
            return '';
        }

        return get_the_title($series_id);
    }

}
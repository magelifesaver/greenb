<?php

declare(strict_types=1);

namespace ACA\EC\Export\Model\Event;

use ACP;

class AllDayEvent implements ACP\Export\Service
{

    public function get_value($id): string
    {
        $value = get_post_meta($id, '_EventAllDay', true);

        return $value
            ? '1'
            : '';
    }

}
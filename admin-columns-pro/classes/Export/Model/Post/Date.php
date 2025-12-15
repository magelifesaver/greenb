<?php

namespace ACP\Export\Model\Post;

use ACP\Export\Service;

class Date implements Service
{

    public function get_value($id): string
    {
        return get_post($id)->post_date;
    }

}
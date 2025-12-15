<?php

namespace ACP\Export\Model\Term;

use ACP\Export\Service;

class Description implements Service
{

    public function get_value($id): string
    {
        return get_term($id)->description;
    }

}
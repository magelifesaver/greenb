<?php

namespace ACP\Export\Model\Term;

use ACP\Export\Service;

class Name implements Service
{

    public function get_value($id): string
    {
        return get_term($id)->name ?? '';
    }

}
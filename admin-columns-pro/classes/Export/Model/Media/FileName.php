<?php

namespace ACP\Export\Model\Media;

use ACP\Export\Service;

class FileName implements Service
{

    public function get_value($id): string
    {
        return ac_helper()->image->get_file_name($id) ?: '';
    }

}
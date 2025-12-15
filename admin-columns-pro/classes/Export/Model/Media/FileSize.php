<?php

namespace ACP\Export\Model\Media;

use ACP\Export\Service;

class FileSize implements Service
{

    public function get_value($id): string
    {
        $file = get_attached_file($id);

        if ( ! file_exists($file)) {
            return '';
        }

        $bytes = filesize($file);

        if ($bytes <= 0) {
            return '';
        }

        $size = $bytes / 1024;

        return sprintf('%s KB', number_format($size, 2));
    }

}
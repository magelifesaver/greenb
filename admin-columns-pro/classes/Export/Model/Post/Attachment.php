<?php

namespace ACP\Export\Model\Post;

use AC\Type\Value;
use AC\Value\Formatter\Post\Attachments;
use ACP\Export\Service;

class Attachment implements Service
{

    public function get_value($id): string
    {
        $urls = [];

        $formatter = new Attachments();

        foreach ($formatter->format(new Value((int)$id)) as $value) {
            $media_id = (string)$value;

            if ( ! is_numeric($media_id)) {
                continue;
            }

            $urls[] = wp_get_attachment_url((int)$media_id);
        }

        return implode(', ', $urls);
    }

}
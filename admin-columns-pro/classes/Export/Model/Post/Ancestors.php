<?php

namespace ACP\Export\Model\Post;

use ACP\Export\Service;

class Ancestors implements Service
{

    public function get_value($id): string
    {
        $post = get_post($id);

        if ( ! $post) {
            return '';
        }

        $ancestors = [];

        foreach ($post->ancestors as $ancestor) {
            $ancestors[] = ac_helper()->post->get_title($ancestor);
        }

        return strip_tags(implode(', ', $ancestors));
    }

}
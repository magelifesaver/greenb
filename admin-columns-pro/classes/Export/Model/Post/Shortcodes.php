<?php

namespace ACP\Export\Model\Post;

use ACP\Export\Service;

class Shortcodes implements Service
{

    public function get_value($id): string
    {
        global $shortcode_tags;

        $shortcodes = $shortcode_tags
            ? ac_helper()->string->get_shortcodes(get_post_field('post_content', $id))
            : null;

        return $shortcodes
            ? implode(', ', array_keys($shortcodes))
            : '';
    }

}
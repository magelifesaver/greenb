<?php

namespace ACP\Export\Model\Post;

use ACP\Export\Service;

class AuthorGravatar implements Service
{

    public function get_value($id): string
    {
        $author_id = ac_helper()->post->get_raw_field('post_author', (int)$id);

        return get_avatar_url($author_id);
    }

}
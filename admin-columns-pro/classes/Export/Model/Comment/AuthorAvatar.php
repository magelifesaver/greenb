<?php

namespace ACP\Export\Model\Comment;

use ACP\Export\Service;

class AuthorAvatar implements Service
{

    public function get_value($id): string
    {
        return get_avatar_url(get_comment($id));
    }

}
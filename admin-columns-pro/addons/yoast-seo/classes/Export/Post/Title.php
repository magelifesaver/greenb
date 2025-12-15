<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Export\Post;

use ACP\Export;

class Title implements Export\Service
{

    public function get_value($id): string
    {
        $title = get_post_meta($id, '_yoast_wpseo_title', true);

        return $title ?: get_the_title($id);
    }

}
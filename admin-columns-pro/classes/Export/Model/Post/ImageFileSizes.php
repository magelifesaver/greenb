<?php

namespace ACP\Export\Model\Post;

use ACP\Export\Service;

class ImageFileSizes implements Service
{

    private function get_image_sizes(array $urls): array
    {
        return array_filter(array_map([ac_helper()->image, 'get_local_image_size'], $urls));
    }

    public function get_value($id): string
    {
        $image_sizes = $this->get_image_sizes($this->get_image_urls((int)$id));

        return ac_helper()->file->get_readable_filesize((int)array_sum($image_sizes));
    }

    private function get_image_urls(int $id): array
    {
        $string = ac_helper()->post->get_raw_field('post_content', $id);
        $string = (string)apply_filters('ac/column/images/content', $string, $id, $this);

        return array_unique(ac_helper()->image->get_image_urls_from_string($string));
    }

}
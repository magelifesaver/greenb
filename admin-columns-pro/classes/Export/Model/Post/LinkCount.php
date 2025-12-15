<?php

namespace ACP\Export\Model\Post;

use ACP\Export\Service;

class LinkCount implements Service
{

    private string $type;

    private array $internal_domains;

    public function __construct(string $type, array $internal_domains)
    {
        $this->type = $type;
        $this->internal_domains = $internal_domains;
    }

    public function get_value($id): string
    {
        $content = get_post_field('post_content', $id);

        $links = ac_helper()->html->get_internal_external_links(
            $content,
            $this->internal_domains
        );

        if ( ! $links) {
            return false;
        }

        switch ($this->type) {
            case 'internal':
                return count($links[0]);
            case 'external':
                return count($links[1]);
            default:
                return sprintf('%s / %s', count($links[0]), count($links[1]));
        }
    }

}
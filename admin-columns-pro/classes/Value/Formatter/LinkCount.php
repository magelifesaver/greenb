<?php

declare(strict_types=1);

namespace ACP\Value\Formatter;

use AC;
use AC\Type\Value;

class LinkCount implements AC\Setting\Formatter
{

    private $type;

    private $internal_domains;

    public function __construct(string $type, array $internal_domains)
    {
        $this->type = $type;
        $this->internal_domains = $internal_domains;
    }

    public function format(Value $value)
    {
        $links = ac_helper()->html->get_internal_external_links(
            $value->get_value(),
            $this->internal_domains
        );

        if (empty($links)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $urls = $this->get_urls($links);

        if (empty($urls)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($this->format_tooltip($urls));
    }

    private function format_tooltip(array $urls): string
    {
        return ac_helper()->html->tooltip(
            (string)count($urls),
            implode('<br>', array_map([$this, 'trim_tooltip_url'], $urls))
        );
    }

    private function remove_home_url_prefix(string $url): string
    {
        return str_replace(home_url(), '', $url);
    }

    private function trim_tooltip_url(string $url): string
    {
        return ac_helper()->string->trim_characters($url, 26);
    }

    private function get_urls(array $internal_external_links): array
    {
        switch ($this->type) {
            case 'internal':
                return array_map([$this, 'remove_home_url_prefix'], $internal_external_links[0]);
            case 'external':
                return $internal_external_links[1];
            default:
                return array_merge(...$internal_external_links);
        }
    }

}
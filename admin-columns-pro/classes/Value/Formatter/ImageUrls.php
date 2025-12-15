<?php

declare(strict_types=1);

namespace ACP\Value\Formatter;

use AC;
use AC\Type\Value;

class ImageUrls implements AC\Setting\Formatter
{

    public function format(Value $value)
    {
        $urls = array_unique(ac_helper()->image->get_image_urls_from_string($value->get_value()));

        if (empty($urls)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $collection = new AC\Type\ValueCollection($value->get_id());

        foreach ($urls as $url) {
            $collection->add(new Value($url));
        }

        return $collection;
    }

}
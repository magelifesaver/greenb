<?php

declare(strict_types=1);

namespace ACA\ACF\Export\Model;

use AC\Setting\Formatter;
use AC\Type\Value;
use ACA;
use ACP;
use Exception;

class Link implements ACP\Export\Service
{

    private Formatter $formatter;

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function get_value($id): string
    {
        try {
            $link = $this->formatter->format(new Value($id))->get_value();
        } catch (Exception $e) {
            return '';
        }

        if ( ! $link) {
            return '';
        }

        return $link['url'] ?? '';
    }

}
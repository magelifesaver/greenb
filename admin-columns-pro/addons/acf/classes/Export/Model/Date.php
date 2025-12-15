<?php

declare(strict_types=1);

namespace ACA\ACF\Export\Model;

use AC\Setting\Formatter;
use AC\Type\Value;
use ACA;
use ACP;
use DateTime;

class Date implements ACP\Export\Service
{

    private Formatter $formatter;

    public function __construct(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function get_value($id): string
    {
        $value = $this->formatter->format(new Value($id));

        if ( ! $value instanceof Value) {
            return '';
        }

        $date = DateTime::createFromFormat('Ymd', $value->get_value());

        return $date
            ? $date->format('Y-m-d')
            : '';
    }

}
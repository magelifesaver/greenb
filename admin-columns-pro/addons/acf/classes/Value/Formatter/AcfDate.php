<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Setting\Formatter;
use AC\Type\Value;
use DateTime;

class AcfDate implements Formatter
{

    public function format(Value $value)
    {
        $date_value = (string)$value->get_value();

        if (strlen($date_value) === 8) {
            return $value->with_value(DateTime::createFromFormat('Ymd', $date_value)->format('U'));
        }

        $date = strtotime($date_value);

        if ( ! $date) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($date);
    }

}
<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Formatter\DateFormatter;

use AC\Exception\ValueNotFoundException;
use AC\Expression\DateOperators;
use AC\Setting\FormatterCollection;
use AC\Table\ProcessFormatters;
use AC\Type\Value;
use AC\Value\Formatter\Date\DateMapper;
use ACP\ConditionalFormat\Formatter\DateFormatter;

class BaseDateFormatter extends DateFormatter
{

    private FormatterCollection $formatters;

    public function __construct(FormatterCollection $formatters, ?string $format = null)
    {
        parent::__construct();

        $this->formatters = $formatters;
        $this->formatters->add(new DateMapper($format, 'Y-m-d'));
    }

    public function format(string $value, $id, string $operator_group): string
    {
        if ($operator_group === DateOperators::class) {
            $formatter_value = new Value($id);

            try {
                return (string)(new ProcessFormatters($this->formatters))->format($formatter_value)->get_value();
            } catch (ValueNotFoundException $exception) {
                return '';
            }
        }

        return $value;
    }

}
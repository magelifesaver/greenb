<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Formatter\DateFormatter;

use AC\Exception\ValueNotFoundException;
use AC\Expression\DateOperators;
use AC\Setting\FormatterCollection;
use AC\Table\ProcessFormatters;
use AC\Type\Value;
use ACP\ConditionalFormat\Formatter\DateFormatter;

class DateValueFormatter extends DateFormatter
{

    private FormatterCollection $formatters;

    public function __construct(FormatterCollection $formatters)
    {
        parent::__construct();

        $this->formatters = $formatters;
    }

    public function format(string $value, $id, string $operator_group): string
    {
        if ($operator_group === DateOperators::class) {
            $formatterValue = new Value($id);

            try {
                return (string)(new ProcessFormatters($this->formatters))->format($formatterValue)->get_value();
            } catch (ValueNotFoundException $exception) {
                return '';
            }
        }

        return $value;
    }

}
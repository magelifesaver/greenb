<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Formatter;

use AC\Setting\FormatterCollection;
use AC\Table\ProcessFormatters;
use AC\Type\Value;

class FormatCollectionFormatter extends BaseFormatter
{

    private FormatterCollection $formatters;

    public function __construct(FormatterCollection $formatters, string $type = self::STRING)
    {
        parent::__construct($type);

        $this->formatters = $formatters;
    }

    public static function create(array $formatters): self
    {
        return new self(new FormatterCollection($formatters));
    }

    public function format(string $value, $id, string $operator_group): string
    {
        $value_object = (new ProcessFormatters($this->formatters))->format(
            new Value($id, $value)
        );

        return parent::format(
            $value_object->get_value(),
            $value_object->get_id(),
            $operator_group
        );
    }

}
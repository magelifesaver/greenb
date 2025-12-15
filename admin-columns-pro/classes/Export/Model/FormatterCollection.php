<?php

namespace ACP\Export\Model;

use AC;
use AC\Table\ProcessFormatters;
use AC\Type\Value;
use ACP\Export\Service;

class FormatterCollection implements Service
{

    private AC\Setting\FormatterCollection $formatters;

    private ?string $default;

    public function __construct(AC\Setting\FormatterCollection $formatters, string $default = '')
    {
        $this->formatters = $formatters;
        $this->default = $default;
    }

    public static function create(array $formatters): self
    {
        return new self(new AC\Setting\FormatterCollection($formatters));
    }

    public function get_value($id): string
    {
        return (string)(new ProcessFormatters($this->formatters, $this->default))->format(new Value($id));
    }

}
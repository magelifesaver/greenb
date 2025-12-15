<?php

namespace ACP\Export\Model;

use AC;
use AC\Type\Value;
use ACP\Export\Service;

class Formatter implements Service
{

    private AC\Setting\Formatter $formatter;

    public function __construct(AC\Setting\Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function get_value($id): string
    {
        return (string)$this->formatter->format(new Value($id));
    }

}
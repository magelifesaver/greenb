<?php

declare(strict_types=1);

namespace ACA\Types\Setting\Context;

use AC\Setting\Config;
use AC\Setting\Context;

class Relationship extends Context
{

    private array $relation;

    public function __construct(Config $config, array $relation)
    {
        parent::__construct($config);

        $this->relation = $relation;
    }

    public function get_relation(): array
    {
        return $this->relation;
    }
}
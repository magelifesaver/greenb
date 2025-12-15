<?php

declare(strict_types=1);

namespace ACA\MetaBox\Setting\Context;

use AC\Setting\Config;
use AC\Setting\Context;
use ACA\MetaBox\Entity;

class Relation extends Context
{

    private Entity\Relation $relation;

    public function __construct(Config $config, Entity\Relation $relation)
    {
        parent::__construct($config);

        $this->relation = $relation;
    }

    public function get_relation(): Entity\Relation
    {
        return $this->relation;
    }

}
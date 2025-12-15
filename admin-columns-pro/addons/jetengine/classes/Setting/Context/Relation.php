<?php

declare(strict_types=1);

namespace ACA\JetEngine\Setting\Context;

use AC\Setting\Config;
use AC\Setting\Context;
use Jet_Engine;

class Relation extends Context
{

    private Jet_Engine\Relations\Relation $relation;

    public function __construct(Config $config, Jet_Engine\Relations\Relation $relation)
    {
        parent::__construct($config);

        $this->relation = $relation;
    }

    public function get_relation(): Jet_Engine\Relations\Relation
    {
        return $this->relation;
    }

}
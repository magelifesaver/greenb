<?php

declare(strict_types=1);

namespace ACP\Export\Model\User;

use AC\MetaType;
use ACP\Export\Model;

class Meta extends Model\Meta
{

    public function __construct(string $meta_key)
    {
        parent::__construct(new MetaType(MetaType::USER), $meta_key);
    }

}
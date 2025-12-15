<?php

declare(strict_types=1);

namespace ACA\BP\ListTable\SaveHeadings;

use AC\Table\SaveHeading\ScreenColumnsFactory;
use AC\TableScreen;
use ACA\BP;

class ActivityFactory extends ScreenColumnsFactory
{

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof BP\TableScreen\Activity;
    }

}
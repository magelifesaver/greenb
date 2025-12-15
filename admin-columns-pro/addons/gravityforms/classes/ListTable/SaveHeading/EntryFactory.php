<?php

declare(strict_types=1);

namespace ACA\GravityForms\ListTable\SaveHeading;

use AC\Registerable;
use AC\Storage\Repository\DefaultColumnsRepository;
use AC\Table\SaveHeadingFactory;
use AC\TableScreen;
use ACA\GravityForms\TableScreen\Entry;

class EntryFactory implements SaveHeadingFactory
{

    private DefaultColumnsRepository $repository;

    public function __construct(DefaultColumnsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof Entry;
    }

    public function create(TableScreen $table_screen): ?Registerable
    {
        return new ScreenColumns(
            $table_screen->get_id(),
            $this->repository
        );
    }

}
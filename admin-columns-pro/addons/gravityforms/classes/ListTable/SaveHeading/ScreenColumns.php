<?php

declare(strict_types=1);

namespace ACA\GravityForms\ListTable\SaveHeading;

use AC\Registerable;
use AC\Storage\Repository\DefaultColumnsRepository;
use AC\Type\DefaultColumns;
use AC\Type\TableId;

class ScreenColumns implements Registerable
{

    private DefaultColumnsRepository $repository;

    private TableId $table_id;

    private bool $do_exit;

    public function __construct(TableId $table_id, DefaultColumnsRepository $repository, bool $do_exit = true)
    {
        $this->repository = $repository;
        $this->table_id = $table_id;
        $this->do_exit = $do_exit;
    }

    public function register(): void
    {
        add_filter('gform_entry_list_columns', [$this, 'handle'], 200);
    }

    public function handle(array $headings): void
    {
        if ($headings && is_array($headings)) {
            $this->repository->update(
                $this->table_id,
                DefaultColumns::create_by_headings($headings)
            );
        }

        if ($this->do_exit) {
            ob_clean();
            exit('ac_success');
        }
    }

}
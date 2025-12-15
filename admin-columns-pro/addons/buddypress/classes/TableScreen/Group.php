<?php

declare(strict_types=1);

namespace ACA\BP\TableScreen;

use AC;
use AC\TableScreen;
use AC\Type\Labels;
use AC\Type\TableId;
use AC\Type\Uri;
use ACA\BP\Editing;
use ACA\BP\ListTable;
use BP_Groups_List_Table;

class Group extends TableScreen implements TableScreen\ListTable
{

    public function __construct(Uri $uri)
    {
        parent::__construct(
            new TableId('bp-groups'),
            'toplevel_page_bp-groups',
            new Labels(
                __('Group', 'codepress-admin-columns'),
                __('Groups', 'codepress-admin-columns')
            ),
            $uri,
            '#bp-groups-form'
        );
    }

    public function list_table(): AC\ListTable
    {
        if ( ! isset($GLOBALS['hook_suffix'])) {
            $GLOBALS['hook_suffix'] = $this->screen_id;
        }

        return new ListTable\Group(new BP_Groups_List_Table());
    }

}
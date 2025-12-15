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
use BP_Activity_List_Table;

class Activity extends TableScreen implements TableScreen\ListTable
{

    public function __construct(Uri $uri)
    {
        parent::__construct(
            new TableId('bp-activity'),
            'toplevel_page_bp-activity',
            new Labels(
                __('Activity', 'codepress-admin-columns'),
                __('Activities', 'codepress-admin-columns')
            ),
            $uri,
            '#the-comment-list'
        );
    }

    public function list_table(): AC\ListTable
    {
        if ( ! isset($GLOBALS['hook_suffix'])) {
            $GLOBALS['hook_suffix'] = $this->screen_id;
        }

        return new ListTable\Activity(new BP_Activity_List_Table());
    }

}
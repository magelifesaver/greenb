<?php

namespace ACP\QuickAdd\Admin\TableElement;

use AC\ListScreen;
use ACP;

class QuickAdd extends ACP\Settings\ListScreen\TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_new_inline',
            sprintf(
                '%s (%s)',
                __('Add Row', 'codepress-admin-columns'),
                __('Quick Add', 'codepress-admin-columns')
            ),
            'feature'
            ,
            null,
            false
        );
    }

    public function is_enabled(ListScreen $list_screen): bool
    {
        return null !== $list_screen->get_preference($this->name) || parent::is_enabled($list_screen);
    }

}
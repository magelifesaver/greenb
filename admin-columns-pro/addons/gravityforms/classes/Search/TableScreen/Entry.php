<?php

namespace ACA\GravityForms\Search\TableScreen;

use ACP\Search;

class Entry extends Search\TableMarkup
{

    public function register(): void
    {
        parent::register();

        add_action('gform_pre_entry_list', [$this, 'filters_markup']);
    }

}
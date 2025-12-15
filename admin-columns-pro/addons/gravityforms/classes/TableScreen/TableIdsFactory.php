<?php

declare(strict_types=1);

namespace ACA\GravityForms\TableScreen;

use AC\Type\TableId;
use AC\Type\TableIdCollection;
use GFAPI;

class TableIdsFactory implements \AC\TableIdsFactory
{

    public function create(): TableIdCollection
    {
        $ids = new TableIdCollection();

        $forms = array_merge(
            GFAPI::get_forms(),
            GFAPI::get_forms(false)
        );

        foreach ($forms as $form) {
            $ids->add(new TableId('gf_entry_' . $form['id']));
        }

        return $ids;
    }

}
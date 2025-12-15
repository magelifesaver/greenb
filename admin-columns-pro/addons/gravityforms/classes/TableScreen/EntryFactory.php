<?php

declare(strict_types=1);

namespace ACA\GravityForms\TableScreen;

use AC;
use AC\TableScreen;
use AC\TableScreenFactory;
use AC\Type\Labels;
use AC\Type\TableId;
use GFAPI;
use GFForms;
use GFFormsModel;
use WP_Screen;

class EntryFactory implements TableScreenFactory
{

    public function create(TableId $id): TableScreen
    {
        return $this->create_table_screen(
            $this->extract_form_id_from_table_id($id)
        );
    }

    public function can_create(TableId $id): bool
    {
        $form_id = $this->extract_form_id_from_table_id($id);

        return $form_id && GFAPI::get_form($form_id);
    }

    private function create_table_screen(int $form_id): Entry
    {
        $form = GFAPI::get_form($form_id);

        $url = new AC\Type\Url\ListTable('admin.php');
        $url = $url->with_arg('id', (string)$form_id)
                   ->with_arg('page', 'gf_entries');

        return new Entry(
            $form_id,
            new Labels(
                (string)$form['title'],
                (string)$form['title']
            ),
            $url
        );
    }

    public function create_from_wp_screen(WP_Screen $screen): TableScreen
    {
        return $this->create_table_screen(
            $this->get_current_form_id()
        );
    }

    public function can_create_from_wp_screen(WP_Screen $screen): bool
    {
        return strpos($screen->id, '_page_gf_entries') !== false &&
               strpos($screen->base, '_page_gf_entries') !== false &&
               $this->has_form_id();
    }

    private function extract_form_id_from_table_id(TableId $table_id): ?int
    {
        if ( ! ac_helper()->string->starts_with((string)$table_id, 'gf_entry_')) {
            return null;
        }

        $entry_id = ac_helper()->string->remove_prefix((string)$table_id, 'gf_entry_');

        return is_numeric($entry_id)
            ? (int)$entry_id
            : null;
    }

    private function has_form_id(): bool
    {
        return $this->get_current_form_id() > 0;
    }

    private function get_current_form_id(): int
    {
        $form_id = GFForms::get('id');

        if ( ! $form_id) {
            $forms = GFFormsModel::get_forms();

            if ($forms) {
                $form_id = $forms[0]->id;
            }
        }

        return (int)$form_id;
    }

}
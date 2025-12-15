<?php

declare(strict_types=1);

namespace ACA\ACF;

use AC\TableScreen;

class FieldRepository
{

    private FieldGroup\QueryFactory $query_factory;

    private FieldFactory $field_factory;

    public function __construct(FieldGroup\QueryFactory $query_factory, FieldFactory $field_factory)
    {
        $this->query_factory = $query_factory;
        $this->field_factory = $field_factory;
    }

    public function find_by_table_screen(TableScreen $table_screen): array
    {
        $group_query = $this->query_factory->create($table_screen);

        if ( ! $group_query instanceof FieldGroup\Query) {
            return [];
        }

        do_action('ac/acf/before_get_field_options', $table_screen);
        $groups = $group_query->get_groups();
        do_action('ac/acf/after_get_field_options', $table_screen);

        if ( ! $groups) {
            return [];
        }

        $fields = array_map('acf_get_fields', $groups);
        $fields = array_filter(array_merge(...$fields));
        $fields = array_map([$this, 'extract_sub_fields'], $fields);

        if ( ! empty($fields)) {
            $fields = array_merge(...$fields);
        }

        $field_collection = [];

        foreach ($fields as $field) {
            $field_collection[] = $this->field_factory->create($field);
        }

        return $field_collection;
    }

    private function extract_sub_fields($field): array
    {
        switch ($field['type']) {
            case FieldType::TYPE_GROUP:
                return $this->get_fields_from_group($field);

            case FieldType::TYPE_CLONE:
                return (array)$field['sub_fields'];

            default:
                return [$field];
        }
    }

    private function get_fields_from_group(array $field): array
    {
        $fields = [];

        foreach ($field['sub_fields'] as $sub_field) {
            $sub_field['_ac_type'] = 'group';
            $sub_field['_ac_group'] = $field;

            $fields[] = $sub_field;
        }

        return $fields;
    }

}
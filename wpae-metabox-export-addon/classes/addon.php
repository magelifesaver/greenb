<?php

namespace Wpae\Metabox;

class PMME_Metabox_Addon extends \Wpae\AddonAPI\PMXE_Addon_Base {
    use \Wpae\AddonAPI\Updatable;

    public $slug = 'metabox';
    public $version = PMME_VERSION;
    public $rootDir = PMME_ROOT_DIR;

	public $fields = [
		'group'         => PMME_Group_Field::class,
		'background'    => PMME_Background_Field::class,
		'text_list'     => PMME_Text_List_Field::class,
		'fieldset_text' => PMME_Fieldset_Text_Field::class,
		'key_value'     => PMME_Key_Value_Field::class,
		'mb_taxonomy'   => PMME_Mb_Taxonomy::class,
	];

    public $casts = [
        'repeater' => AsRepeater::class,
        'taxonomy_advanced' => AsTaxonomy::class,
    ];

    public function name(): string {
        return __('Meta Box', 'wp_all_import_metabox_add_on');
    }

    public function description(): string {
        return __('Export data from Meta Box', 'wp_all_import_metabox_add_on');
    }

	public function getEddName() {
		return 'Meta Box Export Add-On Pro';
	}

    public function canRun() {
        $error = $this->getMissingDependencyError('Meta Box', 'https://metabox.io/');
        if (!function_exists('rwmb_meta')) return $error;
        return true;
    }

    public function resolveFieldValue(
        \Wpae\AddonAPI\PMXE_Addon_Field $field,
        $record,
        $recordId
    ) {
        if ($field->repeater_row_index !== null) {
            $rows = $field->parent->value;

            if (isset($rows[$field->repeater_row_index][$field->key])) {
                return $rows[$field->repeater_row_index][$field->key];
            }

            if (isset($rows[$field->key])) {
                return $rows[$field->key];
            }

            return '';
        }

        $registry    = PMME_Registry::getInstance();
        $type        = \XmlExportEngine::$post_types[0]; // TODO: Why is this hardcoded?
        $subtype     = \XmlExportEngine::$exportOptions['taxonomy_to_export'];
        $object_type = $registry->getObjectType($type);
        $args        = $registry->getRegistryArgs($object_type, $type, $subtype);

        return rwmb_get_value(
            $field->key,
            $args,
            $recordId
        );
    }

    public function resolveFieldClass($field, $class) {
        // Cloneable fields are handled by the cloneable field class, except repeaters and key_value fields.
        if ($field['args']['is_cloneable'] && !in_array($field['type'], ['repeater', 'key_value'])) {
            return PMME_Cloneable_Field::class;
        }

        return $class;
    }

    public static function fields(array $types, string $subtype = null) {
        $registry = PMME_Registry::getInstance();
        return $registry->fields($types, $subtype);
    }

    public static function groups(array $types, string $subtype = null) {
        $registry = PMME_Registry::getInstance();
        return $registry->groups($types, $subtype);
    }
}

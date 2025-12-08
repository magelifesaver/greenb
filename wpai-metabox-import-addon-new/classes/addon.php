<?php

namespace Wpai\Metabox;

use \Wpai\AddonAPI\Updatable;
use \Wpai\AddonAPI\PMXI_Addon_Base;
use Wpai\JetEngine\PMMI_Map_Field;

class PMMI_Metabox_Addon extends PMXI_Addon_Base {
    use Updatable;

    public $slug = 'metabox';
    public $version = PMMI_VERSION;

    public $edition = 'paid';

    public $rootDir = PMMI_ROOT_DIR;

    public $fields = [
        'background' => PMMI_Background_Field::class,
        'group' => PMMI_Group_Field::class,
        'fieldset_text' => PMMI_Fieldset_Text_Field::class,
        'text_list' => PMMI_Text_List_Field::class,
        'key_value' => PMMI_Key_Value_Field::class,
        'image_select' => PMMI_Image_Select_Field::class,
	    'taxonomy' => PMMI_Taxonomy_Field::class,
	    'map' => PMMI_Map_Field::class,
    ];

    public $casts = [
        'text_list' => AsTextList::class,
        'key_value' => AsKeyValue::class,
	    'taxonomy' => AsTaxonomy::class,
    ];

    public function name(): string {
        return __('Meta Box Add-On', 'wp_all_import_metabox_add_on');
    }

    public function description(): string {
        return __('Import data from Meta Box', 'wp_all_import_metabox_add_on');
    }

	public function getEddName() {
		return 'Meta Box Import Add-On Pro';
	}

    public function canRun() {
        $error = $this->getMissingDependencyError('Meta Box', 'https://wordpress.org/plugins/meta-box');
        if (!function_exists('rwmb_meta')) return $error;
        return true;
    }

    public function resolveFieldClass($field, $class) {
        // Cloneable fields are handled by the cloneable field class, except repeaters.
        if ($field['args']['is_cloneable'] && $field['type'] !== 'repeater') {
            return PMMI_Cloneable_Field::class;
        }

        return $class;
    }

    public static function fields(string $type, string $subtype = null) {
        $registry = PMMI_Registry::getInstance();
        return $registry->fields($type, $subtype);
    }

    public static function groups(string $type, string $subtype = null) {
        $registry = PMMI_Registry::getInstance();
        return $registry->groups($type, $subtype);
    }

    public static function import(
        int $postId,
        array $fields,
        array $values,
        \PMXI_Import_Record $record,
        array $post,
        $logger
    ) {
        $type = $record->options['custom_type'];
        $subtype = $record->options['taxonomy_type'];

        $registry = PMMI_Registry::getInstance();
        $object_type = $registry->getObjectType($type);
        $args = $registry->getRegistryArgs($object_type, $type, $subtype);

        foreach ($fields as $field) {
            $name = $field['key'];
            $value = $values[$name] ?? '';

            rwmb_set_meta($postId, $name, $value, $args);
            call_user_func($logger, "- Importing field `$name`");
        }
    }
}

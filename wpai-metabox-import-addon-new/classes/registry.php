<?php

namespace Wpai\Metabox;

class PMMI_Registry {

    use \Wpai\AddonAPI\Singleton;

    /**
     * Convert Meta Box field types into WP All Import field types
     */
    public $mapping = [
        'image' => 'gallery',
        'single_image' => 'media',
        'image_upload' => 'gallery',
        'image_advanced' => 'gallery',
        'autocomplete' => 'select',
        'color' => 'colorpicker',
        'email' => 'text',
        'url' => 'text',
        'hidden' => 'text',
        'password' => 'text',
        'video' => 'gallery',
        'select_advanced' => 'select',
        'button_group' => 'select',
        'oembed' => 'text',
        'checkbox_list' => 'checkbox',
        'slider' => 'number',
        'range' => 'number',
        'wysiwyg' => 'textarea',
        'switch' => 'toggle',
        'file' => 'gallery',
        'file_input' => 'media_url',
        'file_upload' => 'gallery',
        'file_advanced' => 'gallery',
        'sidebar' => 'text',
        'taxonomy_advanced' => 'taxonomy',
        'icon' => 'iconpicker',
        'osm' => 'map'
    ];

    /**
     * Field types that support multiple values
     */
    public $multiple = [
        'autocomplete',
        'checkbox_list',
    ];

    /**
     * Field types to skip because they are not supported
     */
    public $unsupported = [
        'button',
        'custom_html',
        'divider',
        'heading',
	    'tab'
    ];

    public function getObjectType($type) {
        $object_types = [
            'import_users' => 'user',
            'shop_customer' => 'user',
            'taxonomies' => 'term',
            'comments' => 'comment',
        ];
        return $object_types[$type] ?? 'post';
    }

    public function getRegistryArgs($object_type, $type, $subtype) {
        $args = ['object_type' => $object_type];

        if ($object_type === 'post') {
            $args['post_types'] = [$type];
        } else if ($object_type === 'term') {
            $args['taxonomies'] = [$subtype];
        }

        return $args;
    }

	public function get_meta_boxes($args){
		$meta_box_registry = rwmb_get_registry('meta_box');

		$meta_boxes = $meta_box_registry->all();

		foreach ( $meta_boxes as $index => $meta_box ) {
			foreach ( $args as $key => $value ) {
				$meta_box_key = 'object_type' === $key ? $meta_box->get_object_type() : $meta_box->$key;

				$value = is_array( $value ) && $key === 'post_types' ? array_shift( $value ) : $value;

				if ( is_array( $meta_box_key ) && ! in_array( $value, $meta_box_key ) && $meta_box_key !== $value ) {
					unset( $meta_boxes[ $index ] );
					continue 2;
				}
			}
		}

		return $meta_boxes;
	}

    /**
     * Turns an array of metabox fields into an array of fields that can be used by the plugin
     * @param string $post_type
     * @return array
     */
    public function fields($type, $subtype) {
        $object_type = $this->getObjectType($type);
        $args = $this->getRegistryArgs($object_type, $type, $subtype);

	    $meta_boxes = $this->get_meta_boxes( $args );
        $meta_boxes = array_filter($meta_boxes);

        $fields = [];

        foreach ($meta_boxes as $meta_box) {
            foreach ($meta_box->fields as $field) {
                $fields[] = static::prepareField($field, $meta_box->meta_box['id']);
            }
        }

        $fields = array_filter($fields, fn ($field) => !in_array($field['type'], $this->unsupported));

        return $fields;
    }

    /**
     * @param string $type
     * @return array
     */
    public function groups($type, $subtype) {
        $object_type = $this->getObjectType($type);
        $args = $this->getRegistryArgs($object_type, $type, $subtype);

	    $meta_boxes  = $this->get_meta_boxes( $args );

        $callable = self::class . '::prepareGroup';
        $meta_boxes = array_map($callable, $meta_boxes);
        return $meta_boxes;
    }

    public function isMultiple($field, $type) {
        return in_array($field['type'], $this->multiple) || ($field['multiple'] ?? false);
    }

    public function getFieldType($field) {
        $type = $this->mapping[$field['type']] ?? $field['type'];

        if ($type === 'group' && $field['clone']) {
            return 'repeater';
        }

        return $type;
    }

    public function getFieldChoices($field) {
        if ($field['type'] === 'checkbox') {
            return [
                [
                    'label' => $field['desc'],
                    'value' => true,
                ]
            ];
        }

        // There are too many options to list them all, 
        // and we don't show these options on the frontend, 
        // so we'll just return an empty array.
        if ($field['type'] === 'icon') {
            return [];
        }

        return array_map(
            fn ($value, $key) => [
                'label' => $value,
                'value' => $key,
            ],
            $field['options'] ?? [],
            array_keys($field['options'] ?? [])
        );
    }

    public function getSubfields($field) {
        $contains_subfields = $field['type'] === 'fieldset_text' || $field['type'] === 'text_list';

        if ($contains_subfields) {
            $options = $field['options'] ?? [];

            return array_map(
                fn ($label, $key) => [
                    'type' => 'text',
                    'label' => $label,
                    'key' => $key,
                    'args' => [
                        'is_cloneable' => false,
                    ]
                ],
                $options,
                array_keys($options)
            );
        }

        if ($field['type'] === 'key_value') {
            return [
                [
                    'type' => 'text',
                    'label' => 'Key',
                    'key' => 'key',
                    'args' => [
                        'is_cloneable' => false,
                    ]
                ],
                [
                    'type' => 'text',
                    'label' => 'Value',
                    'key' => 'value',
                    'args' => [
                        'is_cloneable' => false,
                    ]
                ],
            ];
        }

        // If the field is a cloneable field, we'll just return a single subfield with the same properties.
        $is_self_cloning = $field['clone'] && empty($field['fields']) && $field['type'] !== 'group';

        if ($is_self_cloning) {
            $subfield = array_merge($field, [
                'clone' => false
            ]);

            return [
                $this->prepareField($subfield)
            ];
        }

        return array_map(
            fn ($subfield) => $this->prepareField($subfield, $field['id']),
            $field['fields'] ?? []
        );
    }

    /**
     * Convert a Meta Box field into a WP All Import field
     * @param $field
     * @param string $group
     * @return array
     */
    public function prepareField($field, string $group = 'post-type') {
        $type = $this->getFieldType($field);
        $multiple = $this->isMultiple($field, $type);
        $choices = $this->getFieldChoices($field);
        $subfields = $this->getSubfields($field);

        $field_details = [
            'label' => $field['name'],
            'key' => $field['id'],
            'type' => $type,
            'default' => $field['std'] ?? null,
            'placeholder' => $field['placeholder'] ?? null,
            'group' => $group,
            'multiple' => $multiple,
            'choices' => $choices,
            'subfields' => $subfields,
            'args' => array_filter(
                [
                    'is_cloneable' => $field['clone'] ?? false,
                    'add_button_label' => $field['add_button'] ?? null,
                    'is_array' => $field['is_array'] ?? null,
                    'is_timestamp' => $field['timestamp'] ?? null,
                    'search_post_type' => $field['query_args']['post_type'] ?? null,
                    'value_format' => $field['value_format'] ?? null,
                    'taxonomy' => $field['taxonomy'] ?? null,
                    'map_language' => $field['language'] ?? null,
                    'map_region' => $field['region'] ?? null,
                    'map_api_key' => $field['api_key'] ?? null,
                ],
                fn ($value) => $value !== null
            )
        ];

		// Customize fields based on original field type before mapping.
	    switch($field['type']){
		    case 'sidebar':
			    $field_details['hint'] = __( 'The sidebar\'s slug must be provided. A sidebar named \'My Custom Sidebar\' should have the slug \'my-custom-sidebar\' by default.', 'wp_all_import_metabox_add_on');
				break;
		    case 'taxonomy':
				$field_details['hint'] = __("Use this field only for taxonomies that are not tied to the record type being imported. For others use the 'Taxonomies, Categories, Tags' section below.", 'wp_all_import_metabox_add_on');
				break;
		    case 'text_list':
				// The text list field requires an array default or it generates an exception when unmapped during import.
				$field_details['default'] = [];
				break;
	    }

		return $field_details;
    }

    /**
     * Convert a Meta Box Group into a WP All Import Group
     * @param RW_Meta_Box $group
     * @return array
     */
    public function prepareGroup($group) {
        return [
            'id' => (string) $group->meta_box['id'],
            'label' => $group->meta_box['title']
        ];
    }
}

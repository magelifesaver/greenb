<?php

namespace Wpae\Metabox;


class PMME_Registry {

    use \Wpae\AddonAPI\Singleton;

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
        'oembed' => 'text',
        'checkbox_list' => 'checkbox',
        'slider' => 'number',
        'range' => 'number',
        'wysiwyg' => 'textarea',
        'switch' => 'toggle',
        'file' => 'gallery',
        'file_input' => 'media',
        'file_upload' => 'gallery',
        'file_advanced' => 'gallery',
        'sidebar' => 'text',
	    'taxonomy' => 'mb_taxonomy',
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
        'button_group',
        'custom_html',
        'divider',
        'heading',
        'tab',
    ];

    public function getObjectType($type) {
        $object_types = [
            'users' => 'user',
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
     * @param string[] $types
     * @param string|null $subtype
     * @return array
     */
	public function fields( $types, $subtype = null ) {
		$fields = array_map( function ( $type ) use ( $subtype ) {
			$object_type = $this->getObjectType( $type );
			$args        = $this->getRegistryArgs( $object_type, $type, $subtype );

			$meta_boxes  = $this->get_meta_boxes( $args );

			$meta_boxes  = array_filter( $meta_boxes );

			$fields = [];

			foreach ( $meta_boxes as $meta_box ) {
				foreach ( $meta_box->fields as $field ) {
					$fields[] = static::prepareField( $field, $meta_box->meta_box['id'] );
				}
			}

			return array_filter( $fields, fn( $field ) => ! in_array( $field['type'], $this->unsupported ) );
		}, $types );

		$fields = array_merge( ...$fields );

		return $fields;
	}

    /**
     * @param string[] $types
     * @param string|null $subtype
     * @return array
     */
    public function groups($types, $subtype = null) {
        $meta_boxes = array_map(function ($type) use ($subtype) {
            $object_type = $this->getObjectType($type);
            $args = $this->getRegistryArgs($object_type, $type, $subtype);

	        $meta_boxes        = $this->get_meta_boxes( $args );

            $callable = self::class . '::prepareGroup';
            return array_map($callable, $meta_boxes);
        }, $types);
        $meta_boxes = array_merge(...$meta_boxes);

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
        if ($field['type'] === 'fieldset_text' || $field['type'] === 'text_list') {
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

        return [
            'label' => $field['name'] ?: $field['id'],
            'key' => $field['id'],
            'type' => $type,
            'default' => $field['default'] ?? null,
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
                    'search_post_type' => $field['search_post_type'] ?? null,
                    'value_format' => $field['value_format'] ?? null,
                ],
                fn ($value) => $value !== null
            ),
			'target' => $field['taxonomy'] ?? $field['post_type'] ?? []
        ];
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

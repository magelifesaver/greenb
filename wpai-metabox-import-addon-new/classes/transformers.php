<?php

namespace Wpai\Metabox;

class AsKeyValue {
    public function __invoke($field, $value, $post_id, $post_type) {
        $transformedValue = [];

        foreach ($value as $item) {
            $transformedValue[] = [$item['key'], $item['value']];
        }

        return $transformedValue;
    }
}

class AsTextList {
    public function __invoke($field, $value, $post_id, $post_type) {
        $transformedValue = [];

        foreach ($value as $item) {
            if (is_array($item)) {
                $transformedValue[] = array_values($item);
            } else {
                $transformedValue[] = $item;
            }
        }

        return $transformedValue;
    }
}

class AsTaxonomy {
	public function __invoke($field, $value, $post_id, $post_type){
		$transformedValue = [];

		// Support the multiple option if used.
		if(!empty($field['multiple']) && !empty($value['value'])){
			$value = explode($value['delim'], $value['value']);
		}

		$taxonomies = isset($field['args']['taxonomy']) && is_array($field['args']['taxonomy']) && count($field['args']['taxonomy']) > 0 ? $field['args']['taxonomy'] : [];

		// Ensure we're working with an array in case broken data comes in as the value.
		if (!is_array($value)) {
			$value = array($value); // Convert $value to array if it's not an array.
		}

		foreach ($value as $item) {
			$term = null;

			if (is_numeric($item)) {
				$term = get_term((int) $item);

				if (isset($term->taxonomy) && $term && ! is_wp_error($term) && in_array($term->taxonomy, $taxonomies)) {
					$transformedValue[] = $term->term_id;
					continue;
				}
			}

			if (!empty($taxonomies)) {
				foreach ($taxonomies as $taxonomy) {
					$term = get_term_by('slug', $item, $taxonomy);

					// If the term is not found by slug, then try to find it by name.
					if (!$term || is_wp_error($term)) {
						$term = get_term_by('name', $item, $taxonomy);
					}

					// If the term is found, add it to the results and break the loop.
					if ($term && ! is_wp_error($term)) {
						$transformedValue[] = $term->term_id;
						break;
					}
				}
			}
		}

		return !empty($transformedValue) ? $transformedValue : [];
	}
}
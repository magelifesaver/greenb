<?php

namespace UkrSolution\ProductLabelsPrinting\Models;

use Exception;
use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;

class PostsUtils
{
    public function getPostIdByField($fieldData, $fieldSource = 'posts')
    {
        $result = array();
        try {
            if (empty($fieldData['field']) || empty($fieldData['value'])) {
                $result = $fieldData;
                throw new Exception(__('Incorrect search field data.', 'wpbcu-barcode-generator'));
            }
            $fieldType = $fieldData['field'];
            $fieldValue = trim($fieldData['value']);
            $result[$fieldType] = $fieldValue;

            switch ($fieldSource) {
                case 'posts':
                    switch ($fieldType) {
                        case 'id':
                            $posts = $this->getPostsById($fieldValue);
                            break;
                        default:
                            $posts = array();
                    }
                    break;
                case 'postmeta':
                    switch ($fieldType) {
                        case '_sku':
                            $posts = $this->getPostsBySku($fieldValue);
                            break;
                        default:
                            $posts = array();
                    }
                    break;
                case 'attribute':
                    $posts = $this->getPostsByAttributeValue($fieldType, $fieldValue);
                    break;
                default:
                    $posts = array();
            }

            if (!empty($posts)) {
                $result['ids'] = $posts;
            } else {
                throw new Exception(__('Not found.', 'wpbcu-barcode-generator'));
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    protected function getPostsBySku($value)
    {
        return uswbg_a4bGetPosts(array(
            'meta_key' => '_sku',
            'meta_value' => $value,
            'post_type' => array('product', 'product_variation'),
            'fields' => 'ids',
        ));
    }
    protected function getPostsById($value)
    {
        $value = (int)$value;
        return uswbg_a4bGetPosts(array(
            'post_type' => array('product', 'product_variation'),
            'post__in' => empty($value) ? array(0) : array($value),
            'fields' => 'ids',
        ));
    }
    protected function getPostsByAttributeValue($attr, $value)
    {
        if (false !== strpos($attr, '^')) {
            $attrParams = explode('^', $attr);
            $attr = isset($attrParams[0]) ? $attrParams[0] : '';
            $productIndex = isset($attrParams[1]) && !empty(intval($attrParams[1])) ? intval($attrParams[1]) : null;
        }

        $attrPriority = UserSettings::getOption('attrPriority', 'one');

        if ('global' === $attrPriority) {
            $posts = $this->getPostsByGlobalAttributeValue($attr, $value, $productIndex);
        } elseif ('local' === $attrPriority) {
            $posts = $this->getPostsByLocalAttributeValue($attr, $value, $productIndex);
        } else {
            $posts = $this->getPostsByLocalAttributeValue($attr, $value, $productIndex);
            if (empty($posts)) {
                $posts = $this->getPostsByGlobalAttributeValue($attr, $value, $productIndex);
            }
        }

        return $posts;
    }
    protected function getPostsByGlobalAttributeValue($attr, $value, $index = null)
    {
        global $wpdb;

        $taxonomy = get_taxonomy('pa_'.$attr);

        if (empty($taxonomy)) {
            $wc_attribute_taxonomy = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE `attribute_label` = %s",
                $attr
            ));

            if (null !== $wc_attribute_taxonomy) {
                $taxonomy_slug = 'pa_'.$wc_attribute_taxonomy->attribute_name;
            } else {
                $taxonomy_slug = null;
            }
        } else {
            $taxonomy_slug = 'pa_'.$attr;
        }

        $posts = uswbg_a4bGetPosts(array(
            'post_type' => array('product', 'product_variation'),
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy_slug,
                    'field' => 'slug',
                    'terms' => $value,
                ),
            ),
            'fields' => 'ids',
        ));

        return !empty($index)
            ? (
                isset($posts[$index - 1])
                ? array($posts[$index - 1])
                : array()
            )
            : $posts;
    }
    protected function getPostsByLocalAttributeValue($attr, $value, $index = null)
    {
        $foundSimplePostsIds = array();
        $posts = array();

        $postsIds = uswbg_a4bGetPosts(array(
            'post_type' => array('product', 'product_variation'),
            'meta_query' => array(
                array(
                    'key'     => '_product_attributes',
                    'value'   => $attr,
                    'compare' => 'LIKE'
                ),
            ),
            'fields' => 'ids',
        ));

        $localAttribute = null;
        foreach ($postsIds as $postsId) {
            $productAttributes = get_post_meta($postsId, '_product_attributes', true);

            if (!empty($productAttributes) && is_array($productAttributes)) {
                foreach ($productAttributes as $productAttribute) {
                    if ($productAttribute['name'] === $attr) {
                        $localAttribute = $productAttribute;
                        $localAttributeValues = (!empty($productAttribute['value']) && is_string($productAttribute['value']))
                            ? array_map('trim', explode('|', $productAttribute['value']))
                            : array();

                        if (in_array($value, $localAttributeValues)) {
                            $product = wc_get_product($postsId);

                            if (!empty($product) && $product->get_type() === 'simple') {
                                $foundSimplePostsIds[] = $postsId;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($localAttribute) && is_array($localAttribute)) {
            $posts = uswbg_a4bGetPosts(array(
                'post_type' => array('product', 'product_variation'),
                'meta_query' => array(
                    array(
                        'key'     => 'attribute_'.sanitize_title($localAttribute['name']),
                        'value'   => $value,
                    ),
                ),
                'fields' => 'ids',
            ));
        }

        $result = array_merge($foundSimplePostsIds, $posts);

        return !empty($index)
            ? (
            isset($result[$index - 1])
                ? array($result[$index - 1])
                : array()
            )
            : $result;
    }

}

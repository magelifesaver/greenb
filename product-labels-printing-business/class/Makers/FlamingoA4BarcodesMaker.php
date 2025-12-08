<?php
namespace UkrSolution\ProductLabelsPrinting\Makers;

use UkrSolution\ProductLabelsPrinting\Database;
use UkrSolution\ProductLabelsPrinting\Filters\Items;
use UkrSolution\ProductLabelsPrinting\Helpers\UserFieldsMatching;
use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;

class FlamingoA4BarcodesMaker extends A4BarcodesMaker
{
    protected $fieldNames = array(
        "standart" => array(
            "ID" => "Message Id",
        ),
    );

    public function __construct($data, $type = '')
    {
        parent::__construct($data, $type);
    }

    protected function getItems()
    {
        $messagesIds = isset($this->data['messagesIds']) ? $this->data['messagesIds'] : null;

        $args = array(
            'post_type' => 'flamingo_inbound',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'post__in' => $messagesIds
        );
        $query = new \WP_Query($args);

        $this->items = $query->posts;

        $itemsFilter = new Items();
        $itemsFilter->sortItemsResult($this->items);
    }

    protected function getFileOptions($post, $algorithm)
    {
        if (
            !empty($this->data['options'])
            && !empty($this->data['options'][$post->ID])
            && isset($this->data['options'][$post->ID]['qty'])
        ) {
            $quantityData = $this->data['options'][$post->ID]['qty'];
            $quantity = (empty($quantityData) || '0' === $quantityData) ? 0 : (int) $quantityData;
        } else {
            $quantity = 1;
        }

        $thumbnailUrl = get_the_post_thumbnail_url($post, get_option(Database::$optionPostImageSize, 'medium'));

        $fieldLine1 = !empty($this->data['fieldLine1']) ? $this->getField($post, $this->data['fieldLine1'], 1) : '';
        $fieldLine2 = !empty($this->data['fieldLine2']) ? $this->getField($post, $this->data['fieldLine2'], 2) : '';
        $fieldLine3 = !empty($this->data['fieldLine3']) ? $this->getField($post, $this->data['fieldLine3'], 3) : '';
        $fieldLine4 = !empty($this->data['fieldLine4']) ? $this->getField($post, $this->data['fieldLine4'], 4) : '';
        $fieldSepLine1 = $this->data['lineSeparator1'] && !empty($this->data['fieldSepLine1']) ? $this->getField($post, $this->data['fieldSepLine1'], 1) : '';
        $fieldSepLine2 = $this->data['lineSeparator2'] && !empty($this->data['fieldSepLine2']) ? $this->getField($post, $this->data['fieldSepLine2'], 2) : '';
        $fieldSepLine3 = $this->data['lineSeparator3'] && !empty($this->data['fieldSepLine3']) ? $this->getField($post, $this->data['fieldSepLine3'], 3) : '';
        $fieldSepLine4 = $this->data['lineSeparator4'] && !empty($this->data['fieldSepLine4']) ? $this->getField($post, $this->data['fieldSepLine4'], 4) : '';

        return array(
            'quantity' => $quantity,
            'post_image' => $thumbnailUrl,
            'lineBarcode' => $this->getCodeField($post), 
            'fieldLine1' => $this->twoColumnLineFormat($fieldLine1, $fieldSepLine1), 
            'fieldLine2' => $this->twoColumnLineFormat($fieldLine2, $fieldSepLine2), 
            'fieldLine3' => $this->twoColumnLineFormat($fieldLine3, $fieldSepLine3), 
            'fieldLine4' => $this->twoColumnLineFormat($fieldLine4, $fieldSepLine4), 
            'algorithm' => $algorithm, 
            'showName' => $this->showName, 
            'showLine3' => $this->showLine3, 
            'showLine4' => $this->showLine4,
            'replacements' => $this->getTemplateReplacements($post, $this->templateShortcodesArgs),
        );
    }

    protected function getCodeField($post)
    {
        if ($this->activeTemplate->code_match) {
            return $this->getCodeValue($post, preg_split("/\\r\\n|\\r|\\n/", $this->activeTemplate->single_product_code));
        } else {
            return isset($this->data['lineBarcode']) ? $this->getField($post, $this->data['lineBarcode']) : '';
        }
    }

    protected function getCodeValue($post, $shortcodes)
    {
        $texts = array();
        foreach ($shortcodes as $shortcode) {
            $shortcode = trim($shortcode);

            $j = 1;
            foreach (shortcode_parse_atts(trim($shortcode, '[]')) as $key => $value) {

                if ($j === 1) {
                    $parameterValues = explode('|', $value);

                    foreach ($parameterValues as $parameterValue) {
                        $params = array('term_meta' => null);
                        $i = 1;
                        foreach (shortcode_parse_atts(trim($shortcode, '[]')) as $key => $value) {
                            if ($i === 1) {
                                $value = $parameterValue;
                                if (0 === strpos($value, 'parent.')) {
                                    $post = get_post($post->post_parent);
                                    $value = str_replace('parent.', '', $value);
                                }
                                $params['type'] = $key;
                                $params['value'] = $value;
                            } elseif ('term' === $key) {
                                $params['term_meta'] = $value;
                            } else {
                                $params[$key] = $value;
                            }
                            $i++;
                        }

                        $shortcodeFieldValue = $this->getShortcodeFieldValue($post, $params);

                        if (!empty($shortcodeFieldValue)) {
                            $texts[] = $shortcodeFieldValue;
                            break;
                        }
                    }
                }
            }
        }

        return implode('', $texts);
    }

    protected function getShortcodeFieldValue($post, $args)
    {
        $result = '';
        $args['value'] = isset($args['value']) ? $args['value'] : '';

        $argsValues = explode('|', $args['value']);
        foreach ($argsValues as $argsValue) {
            if (0 === strpos($argsValue, 'parent.')) {
                $post = !empty($post->post_parent) ? get_post($post->post_parent) : $post;
                $argsValue = str_replace('parent.', '', $argsValue);
            }

            switch ($args['type']) {
                case 'cf':
                    $text = $this->getField($post, array('type' => 'custom', 'value' => $argsValue));
                    break;
                case 'field':
                    $text = $this->getField($post, array('type' => 'standart', 'value' => $argsValue));
                    break;
                case 'static':
                    $text = $argsValue;
                    break;
                default:
                    $text = '';
                    break;
            }

            if (!empty($text)) {
                $texts[] = $text;
                $result = implode(' ', $texts);

                if (!empty($args['before'])) {
                    $result = $args['before'] . $result;
                }

                if (!empty($args['after'])) {
                    $result = $result . $args['after'];
                }

                break;
            }
        }

        $result = $this->convertValueToBarcodeImageUrlIfNeed(array('args' => $args), $result);

        return $result;
    }

    protected function getField($post, $field, $lineNumber = "")
    {
        $fieldName = '';
        $isAddFieldName = UserSettings::getoption('fieldNameL' . $lineNumber, false);

        switch ($field['type']) {
            case 'standart':
                $value = $this->getStandardPostField($post, $field['value']);
                $fieldName = isset($this->fieldNames['standart'][$field['value']]) ? $this->fieldNames['standart'][$field['value']] : '';
                break;
            case 'static':
                $value = $field['value'];
                break;
            case 'custom':
                $value = $this->getCustomFieldsValues($post, $field);
                break;
            default:
                $value = '';
        }

        $value = UserFieldsMatching::prepareFieldValue($isAddFieldName, $fieldName, $value, $lineNumber);

        return (string) apply_filters("label_printing_field_value", $value, $field, $post);
    }

    protected function getStandardPostField($post, $field)
    {
        return isset($post->{$field}) ? $post->{$field} : '';
    }

    protected function getCustomFieldsValues($post, $field)
    {
        $customFields = array_map('trim', explode(',', $field['value']));
        $values = array();

        foreach ($customFields as $customField) {
            if (empty($customField)) {
                continue;
            }

            $values[] = $this->getProductMeta($post, $customField, true);

            $values = array_filter($values);
        }

        return implode(',', $values);
    }

    protected function getProductMeta($post, $param, $single = true)
    {
        $customKeys = get_post_custom_keys($post->ID);
        if ($customKeys && in_array($param, $customKeys)) {
            return get_post_meta($post->ID, $param, $single);
        } elseif (in_array('_field_' . $param, get_post_custom_keys($post->ID))) {
            return get_post_meta($post->ID, '_field_' . $param, $single);
        } else {
            return '';
        }
    }

    protected function getTemplateReplacements($post, $shortcodesArgs)
    {
        $replacements = new \ArrayObject();

        foreach ($shortcodesArgs as $shortCode => $args) {
            $texts = array();

            switch ($args['type']) {
                case 'cf':
                    $text = $this->getField($post, array('type' => 'custom', 'value' => $args['value']));
                    break;
                case 'field':
                    $text = $this->getField($post, array('type' => 'standart', 'value' => $args['value']));
                    break;
                case 'static':
                    $text = $args['value'];
                    break;
                case 'taxonomy':
                    $text = $this->getField($post, array('type' => 'taxonomy', 'value' => $args['value']));
                    break;
                case 'date':
                    $text = $this->getField($post, array('type' => 'date', 'value' => $args['value']));
                    break;
                default:
                    $text = '';
                    break;
            }

            if (!empty($text)) {
                $texts[] = $text;
            }

            $replacements[$shortCode] = implode(' ', $texts);
        }

        return $replacements;
    }
}

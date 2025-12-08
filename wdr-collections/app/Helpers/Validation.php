<?php
/**
 * Woo Discount Rules: Collections
 *
 * @package   wdr-collections
 * @author    Anantharaj B <anantharaj@flycart.org>
 * @copyright 2022 Flycart
 * @license   GPL-3.0-or-later
 * @link      https://flycart.org
 */

namespace WDR_COL\App\Helpers;

use Wdr\App\Helpers\Validation as WdrValidation;
use Valitron\Validator;

defined('ABSPATH') or exit;

class Validation extends WdrValidation
{
    /**
     * Validate collections before saving data
     * @param $post_values
     * @return array|bool
     */
    static function validateCollections($post_values)
    {
        $input_validator = new Validator($post_values);
        Validator::addRule('checkPlainInputText', array(__CLASS__, 'validatePlainInputText'), __('Accepts only letters a-z, numbers 0-9 and spaces with special characters', 'wdr-collections'));
        Validator::addRule('conditionValues', array(__CLASS__, 'validateConditionFields'), __('Invalid characters', 'wdr-collections'));
        //may contain
        $input_validator->rule('checkPlainInputText',
            array(
                'title',
            )
        );
        //Validation condition values
        $input_validator->rule('conditionValues',
            array(
                'filters.*.value.*',
            )
        );
        //validate slug may contains a-zA-Z0-9_-
        $input_validator->rule('slug',
            array(
                'filters.*.type',
                'filters.*.method',
                'additional.condition_relationship',
            )
        );
        if ($input_validator->validate()) {
            return true;
        } else {
            return $input_validator->errors();
        }
    }
}
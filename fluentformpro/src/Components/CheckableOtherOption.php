<?php

namespace FluentFormPro\Components;

class CheckableOtherOption
{
    /**
     * Other option for checkbox
     */
    public static function boot()
    {
        // Add settings placement for form builder
        add_filter('fluentform/editor_element_settings_placement', function ($placements) {
            if (isset($placements['input_checkbox']['general'])) {
                $placements['input_checkbox']['general'][] = 'enable_other_option';
                $placements['input_checkbox']['general'][] = 'other_option_label';
                $placements['input_checkbox']['general'][] = 'other_option_placeholder';
            }
            return $placements;
        });

        // Add default settings for existing forms upgrade
        add_filter('fluentform/editor_init_element_input_checkbox', function ($element) {
            if (!isset($element['settings']['enable_other_option'])) {
                $element['settings']['enable_other_option'] = 'no';
            }
            if (!isset($element['settings']['other_option_label'])) {
                $element['settings']['other_option_label'] = __('Other', 'fluentformpro');
            }
            if (!isset($element['settings']['other_option_placeholder'])) {
                $element['settings']['other_option_placeholder'] = __('Please specify...', 'fluentformpro');
            }
            return $element;
        });
    }
}

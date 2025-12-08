<?php

namespace UkrSolution\ProductLabelsPrinting;

use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;

class Dimensions
{
    public function get($isAjax = true)
    {

        if (!current_user_can('read')) {
            wp_die();
        }

        global $wpdb;

        $tableDimension = $wpdb->prefix . Database::$tableDimension;

        $dimensions = $wpdb->get_results("SELECT * FROM `{$tableDimension}`", ARRAY_A);

        if ($isAjax) {
            uswbg_a4bJsonResponse(array("dimensions" => $dimensions));
        } else {
            return $dimensions;
        }
    }
    public function getActive()
    {
        global $wpdb;

        if (UserSettings::$activeDimension) {
            return UserSettings::$activeDimension;
        }

        $tableDimension = Database::$tableDimension;

        $dimension = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}{$tableDimension} WHERE `is_default` = 1;");


        UserSettings::$activeDimension = $dimension ? $dimension->id : 1;

        return UserSettings::$activeDimension;
    }
}

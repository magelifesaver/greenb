<?php

namespace UkrSolution\ProductLabelsPrinting;

use UkrSolution\ProductLabelsPrinting\Helpers\Variables;

class Categories
{
    public function addImportButton($args)
    {
        global $post_type;

        if ($post_type === 'product' && is_admin()) {
            include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/categories/import-categories-button.php';
        }

        return $args;
    }
}

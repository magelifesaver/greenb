<?php
namespace UkrSolution\ProductLabelsPrinting;

use UkrSolution\ProductLabelsPrinting\Helpers\Variables;

class Dokan
{
    public function addFrontendImportButton($args)
    {
        if (function_exists("ProductLabelPrintingAppScriptsFooter")) {
            include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/products/import-dokan-products.php';
            ProductLabelPrintingAppScriptsFooter();
        }

        return $args;
    }
}

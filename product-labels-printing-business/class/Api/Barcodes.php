<?php

namespace UkrSolution\ProductLabelsPrinting\Api;

use UkrSolution\ProductLabelsPrinting\Generators\Generator;
use UkrSolution\ProductLabelsPrinting\Request;
use UkrSolution\ProductLabelsPrinting\Validator;

class Barcodes
{
    public function generateBarcodesByCodes()
    {
        Request::ajaxRequestAccess();

        if (!current_user_can('read')) {
            wp_die();
        }

        $post = array();
        $result = array();

        if (isset($_POST['format'])) {
            $post['format'] = sanitize_text_field($_POST['format']);
        }
        if (isset($_POST['batch'])) {
            $post['batch'] = USWBG_a4bRecursiveSanitizeTextField($_POST['batch']);
        }

        $validationRules = array(
            'format' => 'string',
            'batch' => 'array',
        );

        $data = Validator::create($post, $validationRules, true)->validate();

        if ('CODE128' === strtoupper($data['format'])) {
            $data['format'] = 'C128';
        } elseif ('CODE39' === strtoupper($data['format'])) {
            $data['format'] = 'C39';
        }

        $w = $h = 250;
        $barcodeGenerator = new Generator();

        foreach ($data['batch'] as $code) {
            $result[$code] = $barcodeGenerator->getGeneratedBarcodeSVGFileName($code, $data['format'], $w, $h, 'black', true);
        }

        uswbg_a4bJsonResponse($result);
    }
}

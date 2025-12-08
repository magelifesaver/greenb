/******/ (() => { // webpackBootstrap
jQuery(document).ready(function ($) {
    'use strict';
    let $product_screen = $('.edit-php.post-type-product'),
        $title_action = $product_screen.find('.page-title-action:first'),
        $blankslate = $product_screen.find('.woocommerce-BlankState');

    if (0 === $blankslate.length) {
        if (viWbeParams.url) {
            $title_action.after(`<a href="${viWbeParams.url}" class="page-title-action" style="display: inline-block">
                                    <i class="dashicons dashicons-media-spreadsheet" style="height: auto; font-size: 18px; line-height: 26px"> </i>
                                    ${viWbeParams.text}
                                </a>`);
        }
    }

});
/******/ })()
;
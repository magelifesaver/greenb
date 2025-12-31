/**
 * @package PublishPress
 * @author PublishPress
 *
 * Copyright (C) 2018 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

(function ($, window, document, PP_Checklists, PPCH_WooCommerce) {
    'use strict';

    $(function () {
        /**
         *
         * Categories number
         *
         */
        var $categoriesCountElements = $('.post-type-product [id^="pp-checklists-req-categories_count"]');
        if ($categoriesCountElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var count = $('#product_catchecklist input:checked').length;

                $categoriesCountElements.each(function () {
                    var $element = $(this);
                    var requirementId = $element.attr('id').replace('pp-checklists-req-', '');

                    // Get config for this specific requirement (try specific first, then fallback to original)
                    var config = (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements[requirementId])
                      ? objectL10n_checklist_requirements.requirements[requirementId]
                      : (typeof objectL10n_checklist_requirements !== 'undefined' && objectL10n_checklist_requirements.requirements['categories_count'])
                        ? objectL10n_checklist_requirements.requirements['categories_count']
                        : null;

                    if (config && config.value) {
                        var min_value = parseInt(config.value[0]),
                            max_value = parseInt(config.value[1]);

                        $element.trigger(
                            PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                            PP_Checklists.check_valid_quantity(count, min_value, max_value)
                        );
                    }
                });
            });
        }

        /**
         *
         * Virtual
         *
         */
        var $virtualElements = $('.post-type-product [id^="pp-checklists-req-virtual_checkbox"]');
        if ($virtualElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var is_virtual = $('#woocommerce-product-data #_virtual:checked').length > 0;

                $virtualElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        is_virtual
                    );
                });
            });
        }

        /**
         *
         * Downloadable
         *
         */
        var $downloadableElements = $('.post-type-product [id^="pp-checklists-req-downloadable"]');
        if ($downloadableElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var is_downloadable = $('#woocommerce-product-data #_downloadable:checked').length > 0;

                $downloadableElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        is_downloadable
                    );
                });
            });
        }

        /**
         *
         * Regular price
         *
         */
        var $regularPriceElements = $('.post-type-product [id^="pp-checklists-req-regular_price"]');
        if ($regularPriceElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var price = $('#woocommerce-product-data #_regular_price').val();

                price = price.replace(',', '.');
                price = parseFloat(price);

                $regularPriceElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        price > 0
                    );
                });
            });
        }

        /**
         *
         * Sale price
         *
         */
        var $salePriceElements = $('.post-type-product [id^="pp-checklists-req-sale_price"]');
        if ($salePriceElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var price = $('#woocommerce-product-data #_sale_price').val();

                price = price.replace(',', '.');
                price = parseFloat(price);

                $salePriceElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        price > 0
                    );
                });
            });
        }

        /**
         *
         * Scheduled Sale price
         *
         */
        var $salePriceScheduledElements = $('.post-type-product [id^="pp-checklists-req-sale_price_scheduled"]');
        if ($salePriceScheduledElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var scheduled_from = $('#woocommerce-product-data #_sale_price_dates_from').val(),
                    is_scheduled = scheduled_from !== null && scheduled_from !== false && typeof scheduled_from !== 'undefined' && scheduled_from !== '';

                $salePriceScheduledElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        is_scheduled
                    );
                });
            });
        }

        /**
         *
         * Discount
         *
         */
        var $discountElements = $('.post-type-product [id^="pp-checklists-req-discount"]');
        if ($discountElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var regular_price = $('#woocommerce-product-data #_regular_price').val(),
                    sale_price = $('#woocommerce-product-data #_sale_price').val(),
                    discount = 0,
                    state = false;

                regular_price = regular_price.replace(',', '.');
                regular_price = parseFloat(regular_price);
                sale_price = sale_price.replace(',', '.');
                sale_price = parseFloat(sale_price);

                // Discount in percent
                discount = (regular_price - sale_price) / regular_price * 100;

                state = PP_Checklists.check_valid_quantity(
                    discount,
                    PPCH_WooCommerce.discount_min,
                    PPCH_WooCommerce.discount_max
                );

                $discountElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        state
                    );
                });
            });
        }

        /**
         *
         * SKU
         *
         */
        var $skuElements = $('.post-type-product [id^="pp-checklists-req-sku"]');
        if ($skuElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var sku = $('#woocommerce-product-data #_sku').val().trim(),
                    state = false;

                state = sku !== '' && sku != null && sku != 0 && sku != false;

                $skuElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        state
                    );
                });
            });
        }

        /**
         *
         * Manage Stock
         *
         */
        var $manageStockElements = $('.post-type-product [id^="pp-checklists-req-manage_stock"]');
        if ($manageStockElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var is_stock_manageable = $('#woocommerce-product-data #_manage_stock:checked').length > 0;

                $manageStockElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        is_stock_manageable
                    );
                });
            });
        }

        /**
         *
         * Sold Individually
         *
         */
        var $soldIndividuallyElements = $('.post-type-product [id^="pp-checklists-req-sold_individually"]');
        if ($soldIndividuallyElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var is_sold_individually = $('#woocommerce-product-data #_sold_individually:checked').length > 0;

                $soldIndividuallyElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        is_sold_individually
                    );
                });
            });
        }

        /**
         *
         * Backorder
         *
         */
        var $backorderElements = $('.post-type-product [id^="pp-checklists-req-backorder"]');
        if ($backorderElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                const $fieldsetElement = $('#woocommerce-product-data fieldset.form-field._backorders_field');
                var backorder = $fieldsetElement.find('input[type="radio"]:checked').val(),
                    state = backorder === PPCH_WooCommerce.backorder;

                $backorderElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        state
                    );
                });
            });
        }

        /**
         *
         * Upsells
         *
         */
        var $upsellElements = $('.post-type-product [id^="pp-checklists-req-upsell"]');
        if ($upsellElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var options = $('#woocommerce-product-data #upsell_ids').next().find('.select2-selection__choice'),
                    state = false;

                state = PP_Checklists.check_valid_quantity(
                    options.length,
                    parseInt(PPCH_WooCommerce.upsell_min),
                    parseInt(PPCH_WooCommerce.upsell_max)
                );

                $upsellElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        state
                    );
                });
            });
        }

        /**
         *
         * Crosssells
         *
         */
        var $crosssellElements = $('.post-type-product [id^="pp-checklists-req-crosssell"]');
        if ($crosssellElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var options = $('#woocommerce-product-data #crosssell_ids').next().find('.select2-selection__choice'),
                    state = false;

                state = PP_Checklists.check_valid_quantity(
                    options.length,
                    parseInt(PPCH_WooCommerce.crosssell_min),
                    parseInt(PPCH_WooCommerce.crosssell_max)
                );

                $crosssellElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        state
                    );
                });
            });
        }

        /**
         *
         * Product image
         *
         */
        var $imageElements = $('.post-type-product [id^="pp-checklists-req-image"]:not([id*="image_count"])');        
        if ($imageElements.length > 0) {
            $(document).on(PP_Checklists.EVENT_TIC, function (event) {
                var has_image = $('#postimagediv').find('#set-post-thumbnail').find('img').length > 0;

                $imageElements.each(function () {
                    $(this).trigger(
                        PP_Checklists.EVENT_UPDATE_REQUIREMENT_STATE,
                        has_image
                    );
                });
            });
        }
    });

})(jQuery, window, document, PP_Checklists, PPCH_WooCommerce);
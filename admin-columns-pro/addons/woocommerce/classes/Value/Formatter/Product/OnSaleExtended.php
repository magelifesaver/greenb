<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use Exception;
use WC_DateTime;
use WC_Product;
use WC_Product_Variable;

class OnSaleExtended extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        $is_scheduled = $this->is_scheduled($product);
        $is_future_sale = $this->is_future_sale($product);
        $is_on_sale = $product->is_on_sale();

        if ( ! $is_on_sale && ! $is_future_sale) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        switch ($product->get_type()) {
            case 'variable' :

                /**
                 * @var WC_Product_Variable $product
                 */
                $range = array_filter([
                    $product->get_variation_sale_price(),
                    $product->get_variation_sale_price('max'),
                ]);

                $range = array_unique($range);
                $range = array_map('wc_price', $range);
                $price = implode(' - ', $range);
                break;

            default:
                /** @var WC_Product $product */
                $price = wc_price($product->get_sale_price());
        }

        if ($is_scheduled) {
            $icon = ac_helper()->icon->dashicon(['icon' => 'clock']);

            $tooltip_title = __('Scheduled');

            if ($is_on_sale) {
                $icon = ac_helper()->icon->dashicon(['icon' => 'clock', 'class' => 'green']);
                $tooltip_title = sprintf('%s &amp; %s', $tooltip_title, __('Active'));
            }

            return $value->with_value(
                ac_helper()->html->tooltip(
                    sprintf('%s %s', $price, $icon),
                    sprintf('<strong>%s</strong><br><em>%s</em>', $tooltip_title, $this->get_scheduled_label($product))
                )
            );
        }

        return $value->with_value($price);
    }

    private function is_future_sale(WC_Product $product): bool
    {
        try {
            $date = new WC_DateTime();
        } catch (Exception $e) {
            return false;
        }

        return $product->get_date_on_sale_from() && $product->get_date_on_sale_from() > $date;
    }

    private function is_scheduled(WC_Product $product): bool
    {
        return $product->get_date_on_sale_from() || $product->get_date_on_sale_to();
    }

    private function get_scheduled_label(WC_Product $product): string
    {
        $labels = [
            $this->format_scheduled_label(
                _x('From', 'Product on sale from (date)', 'codepress-admin-columns'),
                $product->get_date_on_sale_from()
            ),
            $this->format_scheduled_label(
                _x('Until', 'Product on sale until (date)', 'codepress-admin-columns'),
                $product->get_date_on_sale_to()
            ),
        ];

        if ( ! array_filter($labels)) {
            return '';
        }

        return implode('<br>', array_filter($labels));
    }

    private function format_scheduled_label($label, ?WC_DateTime $date_time = null)
    {
        if ( ! $date_time) {
            return false;
        }

        return sprintf(
            '%s: %s',
            $label,
            $date_time->format(get_option('date_format'))
        );
    }

}
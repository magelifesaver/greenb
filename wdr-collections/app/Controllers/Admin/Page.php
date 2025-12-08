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

namespace WDR_COL\App\Controllers\Admin;

defined('ABSPATH') or exit;

use WDR_COL\App\Controllers\Controller;

class Page extends Controller
{
    /**
     * Add Tab
     */
    public function addTab($tabs)
    {
        $tabs['collections'] = new Tabs\Collections();
        return $tabs;
    }

    /**
     * To load this page assets
     */
    public function loadAssets()
    {
        if ($this->app->request->get('page', '', 'query') == 'woo_discount_rules') {
            $data = [
                'i18n' => [
                    'deleted_collection' => esc_html__('Collection deleted successfully!', 'wdr-collections'),
                    'delete_collection_confirm' => esc_html__('Are you sure want to delete this collection!', 'wdr-collections'),
                    'delete_collection_linked' => esc_html__('This collection was linked to the following rules, so you cannot remove this collection', 'wdr-collections'),
                    'save_collection' => esc_html__('Collection saved successfully!', 'wdr-collections'),
                    'save_collection_linked' => esc_html__('Attention: This collection was linked to the following rules, so your changes will be reflect in all the linked rules', 'wdr-collections'),
                    'bxgy_collection_discount_content' => __('<p>Discount will be applied <b>only the selected collections (based on mode of apply)</b></p><p>Note : Enable recursive checkbox if the discounts should be applied in sequential ranges. </p>', 'wdr-collections'),
                ],
            ];
            $this->app->assets
                ->addCss('admin', 'collections')
                ->addJs('admin', 'collections', $data)
                ->enqueue('admin');
        }
    }
}
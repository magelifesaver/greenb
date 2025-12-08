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

namespace WDR_COL\App\Controllers\Admin\Tabs;

defined('ABSPATH') or exit;

use Wdr\App\Controllers\Admin\Tabs\Base;
use Wdr\App\Controllers\Configuration;
use Wdr\App\Helpers\Helper;
use WDR_COL\App\Helpers\Collection;

class Collections extends Base
{
    public $priority = 15;
    protected $tab = 'collections';

    /**
     * GeneralSettings constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = __('Collections', 'wdr-collections');
    }

    /**
     * Render settings page
     * @param null $page
     * @return mixed|void
     */
    public function render($page = NULL)
    {
        $collection_helper = new Collection();
        $params = array(
            'configuration' => new Configuration(),
            'is_pro' => Helper::hasPro(),
            'template_helper' => self::$template_helper,
            'base' => $this,
        );
        if (isset($page) && !empty($page)) {
            $id = intval($this->input->get('id', 0));
            if ($id <= 0) {
                $id = 0;
            }

            $product_filters = $this->getProductFilterTypes();
            if (isset($product_filters['Product']['all_products'])) {
                unset($product_filters['Product']['all_products']);
            }
            if (isset($product_filters['Collections'])) {
                unset($product_filters['Collections']);
            }
            $params = array_merge($params, [
                'collection' => $collection_helper->getCollection($id),
                'product_filters' => $product_filters,
            ]);
            self::$template_helper->setPath(WDR_COL_PLUGIN_PATH . 'app/Views/Admin/Collections/Manage.php' )->setData($params)->display();
        } else {
            $params = array_merge($params, ['collections' => $collection_helper->getCollections()]);
            self::$template_helper->setPath(WDR_COL_PLUGIN_PATH . 'app/Views/Admin/Tabs/Collections.php')->setData($params)->display();
        }
    }
}
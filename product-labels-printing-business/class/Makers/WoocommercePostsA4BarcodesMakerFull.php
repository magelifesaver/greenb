<?php
namespace UkrSolution\ProductLabelsPrinting\Makers;

use Atum\Inc\Helpers;
use TierPricingTable\PriceManager;
use UkrSolution\ProductLabelsPrinting\Database;
use UkrSolution\ProductLabelsPrinting\Filters\Items;
use UkrSolution\ProductLabelsPrinting\Helpers\UserFieldsMatching;
use UkrSolution\ProductLabelsPrinting\Helpers\UserSettings;
use UkrSolution\ProductLabelsPrinting\Helpers\Variables;

class WoocommercePostsA4BarcodesMakerFull extends GeneralPostsA4BarcodesMaker
{
    protected $currency = '';
    protected $currencyPosition = 'left';
    protected $getItemsMethods = array(
        true => array(
            true => 'getItemsForCategoriesWithVariations',
            false => 'getItemsForCategories',
        ),
        false => array(
            true => 'getItemsForProductsWithVariations',
            false => 'getItemsForProducts',
        ),
    );
    protected $fieldNames = array(
        "standart" => array(
            "ID" => "Product Id",
            "post_title" => "Name",
            "post_content" => "Description",
            "post_excerpt" => "Short Description",
        ),
        "orderStandart" => array(
            "ID" => "Order Id",
        ),
        "orderCustom" => array(
            "billing_user_name" => "Billing user name",
        ),
        "custom" => array(
            "_sku" => "SKU",
            "_price" => "Actual price",
            "_regular_price" => "Regular price",
            "_sale_price" => "Sale price",
            "_weight" => "Weight",
            "_length" => "Length",
            "_width" => "Width",
            "_height" => "Height",
            "_order_total" => "Order total",
            "_order_tax" => "Order tax",
            "_order_shipping" => "Order shipping price",
            "_cart_discount" => "Discount",
        ),
        "price_with_tax" => "Price + Tax",
        "sale-price-with-tax" => "Sale price + Tax",
        "regular-price-with-tax" => "Regular price + Tax",
        "actual-price-with-tax" => "Actual price + Tax",
        "product_dimensions" => "Product dimensions",
        "wc_category" => "Category",
        "order-total-items" => "Order total items",
        "wcAppointment" => "Appointment Id",
        "order-tax" => "Order Tax",
        "items-subtotal" => "Items Subtotal",
        "order-id" => "Order Id",
        "order-create-date" => "Order create date",
        "order_create_date" => "Order create date",
        "order-completed-date" => "Order completed date",
        "order-product-qty" => "Quantity",
        "product-description" => "Product description",
        "main-product-description" => "Product description",
        "discount" => "Discount",
        "pickup-location-address" => "Pickup location address",
        "pickup-location-title" => "Pickup location title",
        "pickup-date" => "Pickup date",
    );
    protected $usersA4BarcodesMaker;
    protected $excludedProdStatusesArr = array();
    public $shortcodesToOrderCfMap = array(


    );
    protected $orderCfToOrderWcMethodMap = array(
        '_order_subtotal' => 'getOrderSubtotal',
        '_order_tax' => 'getOrderTax',
        '_order_shipping' => 'getOrderShipping',
        '_cart_discount' => 'getOrderCartDiscount',
        '_order_total' => 'getOrderTotal',

        '_shipping_first_name' => 'getOrderShippingFirstName',
        '_shipping_last_name' => 'getOrderShippingLastName',
        '_shipping_city' => 'getOrderShippingCity',
        '_shipping_address_1' => 'getOrderShippingAddress1',
        '_shipping_address_2' => 'getOrderShippingAddress2',
        '_shipping_postcode' => 'getOrderShippingPostcode',
        '_shipping_phone' => 'getOrderShippingPhone',
        '_shipping_country' => 'getOrderShippingCountry',
        '_shipping_country_full_name' => 'getOrderShippingCountryFullName',
        '_shipping_state' => 'getOrderShippingState',
        '_shipping_state_full_name' => 'getOrderShippingStateFullName',
        '_shipping_company' => 'getOrderShippingCompany',

        '_billing_first_name' => 'getOrderBillingFirstName',
        '_billing_last_name' => 'getOrderBillingLastName',
        '_billing_city' => 'getOrderBillingCity',
        '_billing_address_1' => 'getOrderBillingAddress1',
        '_billing_address_2' => 'getOrderBillingAddress2',
        '_billing_postcode' => 'getOrderBillingPostcode',
        '_billing_phone' => 'getOrderBillingPhone',
        '_billing_email' => 'getOrderBillingEmail',
        '_billing_country' => 'getOrderBillingCountry',
        '_billing_country_full_name' => 'getOrderBillingCountryFullName',
        '_billing_state' => 'getOrderBillingState',
        '_billing_state_full_name' => 'getOrderBillingStateFullName',
        '_billing_company' => 'getOrderBillingCompany',
    );
    protected $orderPostFieldToOrderWcMethodMap = array(
        'post_title' => 'getOrderPostTitle',
        'post_date' => 'getOrderCreateDate',
    );

    public function __construct($data, $type = '')
    {
        parent::__construct($data, $type);
        $this->usersA4BarcodesMaker = new UsersA4BarcodesMaker($data, $type);

        $excludedProdStatusesStr = UserSettings::getOption('excludedProdStatuses', '');
        if (strlen($excludedProdStatusesStr) > 0) {
            $this->excludedProdStatusesArr = explode(',', $excludedProdStatusesStr);
        }
    }

    protected function getItems()
    {
        $forCategories = !empty($this->data['productsCategories']);
        $withVariations = !empty($this->data['withVariations']);
        $isAppointment = isset($this->data['appointmentsIds']);

        if ($this->type === "orders") {
            $this->items = $this->getItemsByOrders();

            if (!empty($this->data['existingIds'])) {
                $this->data['ordersIds'] = $this->data['existingIds'];
                $existingItems = $this->getItemsByOrders();
            }
        } elseif ($this->type === "order-products") {
            $this->items = $this->getItemsByOrderProducts();

            if (!empty($this->data['existingIds'])) {
                $existingItems = uswbg_a4bGetPosts(array('post__in' => $this->data['existingIds'], 'post_type' => array('product', 'product_variation')));
            }
        } elseif ($this->type === "products") {
            $this->items = $this->getItemsByProducts();

            if (!empty($this->data['existingIds'])) {
                $existingItems = uswbg_a4bGetPosts(array('post__in' => $this->data['existingIds'], 'post_type' => array('product', 'product_variation')));
            }
        } elseif ($this->type === "atum-po-order-products") {
            $this->items = $this->getItemsByAtumPoOrderProducts();
        } elseif ($isAppointment) {
            $this->items = $this->getAppointments();
        } else {
            $getItemsMethod = $this->getItemsMethods[$forCategories][$withVariations];
            $this->items = $this->$getItemsMethod();

            if (!empty($this->data['existingIds'])) {
                $existingItems = uswbg_a4bGetPosts(array('post__in' => $this->data['existingIds'], 'post_type' => array('product', 'product_variation')));
            }
        }

        if (!empty($this->data['sortAlphabetically']) && '1' === $this->data['sortAlphabetically']) {

            if (!empty($this->data['existingIds']) && !empty($existingItems)) {
                $this->items = array_merge(
                    $this->duplicatePostsByGivenIds(
                        $existingItems,
                        $this->data['existingIds']
                    ),
                    $this->items
                );
            }

            $this->items = $this->sortByTitle($this->items);
        }

        $itemsFilter = new Items();
        $itemsFilter->sortItemsResult($this->items);
    }

    protected function duplicatePostsByGivenIds($posts, $ids, $field = 'ID')
    {
        $counts = array_count_values($ids);

        $objectMap = [];
        foreach ($posts as $object) {
            $objectMap[$object->$field][] = $object;
        }

        $newObjects = $posts;

        foreach ($counts as $value => $requiredCount) {
            if (isset($objectMap[$value])) {
                $existingCount = count($objectMap[$value]);
                while ($existingCount < $requiredCount) {
                    $duplicate = $objectMap[$value][0]; 
                    $newObjects[] = $duplicate;
                    $existingCount++;
                }
            }
        }

        return $newObjects;
    }

    protected function getItemsForCategoriesWithVariations()
    {
        $productsCategories = isset($this->data['productsCategories']) ? $this->data['productsCategories'] : array();

        $products = $this->getProductsByCategoriesFilteredByProductsStatus($productsCategories);
        $variations = uswbg_a4bGetPosts(array('post_type' => 'product_variation', 'post_parent__in' => uswbg_a4bObjectsFieldToArray($products, 'ID')));
        $productsWithoutVariations = uswbg_a4bExcludePostsByIds($products, uswbg_a4bObjectsFieldToArray($variations, 'post_parent'));
        $variations = $this->filterVariationsByProductsStatus($variations);

        return array_merge($productsWithoutVariations, $variations);
    }

    protected function getItemsForCategories()
    {
        $productsCategories = isset($this->data['productsCategories']) ? $this->data['productsCategories'] : array();

        return $this->getProductsByCategoriesFilteredByProductsStatus($productsCategories);
    }

    protected function getItemsForProductsWithVariations()
    {
        $productsIds = isset($this->data['productsIds']) ? $this->data['productsIds'] : null;
        $products = $this->getProductsByIdsFilteredByProductsStatus($productsIds);
        $variableProducts = $this->getVariableProductsByIdsFilteredByProductsStatus($productsIds);

        if (!empty($productsIds)) {
            $variationsByGivenProductsIds = uswbg_a4bGetPosts(array(
                'post_type' => 'product_variation',
                'post__in' =>  $productsIds,
                'fields' => 'id=>parent',
            ));

            $parentsIdsOfVariationsByGivenProductsIds = wp_list_pluck($variationsByGivenProductsIds, 'post_parent');
            $actualProductsIdsOfVariationsByGivenProductsIds = uswbg_a4bGetPosts(array(
                'post_type' => 'product',
                'post_status' => array('publish', 'pending', 'draft', 'future', 'private', 'inherit', 'trash'),
                'post__in' => empty($parentsIdsOfVariationsByGivenProductsIds) ? array(0) : array_values($parentsIdsOfVariationsByGivenProductsIds),
                'fields' => 'ids',
            ));

            if (!empty($variationsByGivenProductsIds) && !empty($actualProductsIdsOfVariationsByGivenProductsIds)) {
                $actualVariationsByGivenProductsIds = uswbg_a4bGetPosts(array(
                    'post_type' => 'product_variation',
                    'post_parent__in' => array_values($actualProductsIdsOfVariationsByGivenProductsIds),
                    'post__in' => wp_list_pluck($variationsByGivenProductsIds, 'ID'),
                ));
            } else {
                $actualVariationsByGivenProductsIds = array();
            }
        } else {
            $actualVariationsByGivenProductsIds = array();
        }

        if (!empty($variableProducts)) {
            $variations = uswbg_a4bGetPosts(array(
                'post_type' => 'product_variation',
                'post_parent__in' =>  uswbg_a4bObjectsFieldToArray($variableProducts, 'ID'),
            ));
        } else {
            $variations = array();
        }

        $productsWithoutVariations = uswbg_a4bExcludePostsByIds($products, uswbg_a4bObjectsFieldToArray($variations, 'post_parent'));
        $variations = $this->filterVariationsByProductsStatus($variations);
        $productsWithVariations = array_values(array_merge($productsWithoutVariations, $variations, $actualVariationsByGivenProductsIds));

        return $this->sortByIds($productsWithVariations, $productsIds);
    }

    protected function getItemsForProducts()
    {
        $productsIds = isset($this->data['productsIds']) ? $this->data['productsIds'] : array();
        $importType = isset($this->data["isImportSingleVariation"]) ? $this->data["isImportSingleVariation"] : "";

        if ($importType === "variation") {
            $products = uswbg_a4bGetPosts(array('post__in' => !empty($productsIds) ? $productsIds : array(0), 'post_type' => 'product_variation'));
        } else {
            $products = $this->getProductsByIdsFilteredByProductsStatus($productsIds);
        }

        return $this->sortByIds($products, $productsIds);
    }

    protected function getItemsByProducts()
    {
        $productsIds = isset($this->data['productsIds']) ? $this->data['productsIds'] : array();

        $onlySelectedVariations = !empty($productsIds)
            ? uswbg_a4bGetPosts(array(
                'post_type'       => 'product_variation',
                'post__in' => !empty($productsIds) ? $productsIds : array(0),
            ))
            : array();

        $onlySelectedVariations = $this->filterVariationsByProductsStatus($onlySelectedVariations);

        if (!empty($this->data['withVariations'])) {
            $products = $this->getItemsForProductsWithVariations();

            $all = array_unique(array_merge($products, $onlySelectedVariations), SORT_REGULAR);

            $all = $this->sortByIds($all, $productsIds);
        } else {
            $products = $this->getProductsByIdsFilteredByProductsStatus($productsIds);

            $all = array_unique(array_merge($products, $onlySelectedVariations), SORT_REGULAR);

            usort($all, function ($obj1, $obj2) use ($all, $productsIds) {
                return array_search($obj1->ID, $productsIds) > array_search($obj2->ID, $productsIds) ? 1 : 0;
            });
        }

        return $all;
    }

    protected function getProductsByIdsFilteredByProductsStatus($productsIds)
    {
        $products = uswbg_a4bGetPosts(array('post__in' => !empty($productsIds) ? $productsIds : array(0)));

        $productsExcludedByStatus = !empty($this->excludedProdStatusesArr)
            ? uswbg_a4bGetPosts(array(
                'post__in' => uswbg_a4bObjectsFieldToArray($products, 'ID'),
                'post_status' =>  $this->excludedProdStatusesArr,
            ))
            : array();

        return uswbg_a4bExcludePostsByIds($products, uswbg_a4bObjectsFieldToArray($productsExcludedByStatus, 'ID'));
    }

    protected function getVariableProductsByIdsFilteredByProductsStatus($productsIds)
    {
        $products = uswbg_a4bGetPosts(array(
            'post__in' => !empty($productsIds) ? $productsIds : array(0),
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => 'variable',
                ),
            ),
        ));

        $productsExcludedByStatus = !empty($this->excludedProdStatusesArr)
            ? uswbg_a4bGetPosts(array(
                'post__in' => uswbg_a4bObjectsFieldToArray($products, 'ID'),
                'post_status' =>  $this->excludedProdStatusesArr,
            ))
            : array();

        return uswbg_a4bExcludePostsByIds($products, uswbg_a4bObjectsFieldToArray($productsExcludedByStatus, 'ID'));
    }

    protected function filterVariationsByProductsStatus($variations)
    {
        $variationsForExclude = !empty($this->excludedProdStatusesArr)
            ? uswbg_a4bGetPosts(array(
                'post_type' => 'product_variation',
                'post__in' =>  !empty($variations) ? uswbg_a4bObjectsFieldToArray($variations, 'ID') : array(0),
                "post_status" => $this->excludedProdStatusesArr,
            ))
            : array();

        return uswbg_a4bExcludePostsByIds($variations, uswbg_a4bObjectsFieldToArray($variationsForExclude, 'ID'));
    }

    protected function getProductsByCategoriesFilteredByProductsStatus($productsCategories)
    {
        $productsWithGivenCategories = uswbg_a4bGetPostsByCategories($productsCategories);

        $productsExcludedByStatus = !empty(UserSettings::getOption('excludedProdStatuses'))
            ? uswbg_a4bGetPostsByCategories($productsCategories, array('post_status' => explode(',', UserSettings::getOption('excludedProdStatuses'))))
            : array();

        return uswbg_a4bExcludePostsByIds($productsWithGivenCategories, uswbg_a4bObjectsFieldToArray($productsExcludedByStatus, 'ID'));
    }

    protected function getItemsByOrders()
    {
        $ordersIds = isset($this->data['ordersIds']) ? $this->data['ordersIds'] : null;

        if (!$ordersIds) {
            return array();
        }


        $orders = array();
        foreach ($ordersIds as $orderId) {
            $orderPost = get_post($orderId)
                ?: new \WP_Post((object) array(
                    'ID' => $orderId,
                    'post_type' => 'shop_order',
                ));

            $orders[] = $orderPost;
        }

        return $orders;
    }

    protected function getItemsByOrderProducts()
    {
        $ordersIds = isset($this->data['ordersIds']) ? $this->data['ordersIds'] : null;
        $orderQuantity = isset($this->data['orderQuantity']) ? $this->data['orderQuantity'] : null;
        $itemsIds = isset($this->data['itemsIds']) ? $this->data['itemsIds'] : null;

        if (!$ordersIds) {
            return array();
        }

        $items = $this->getOrderItemsProducts($ordersIds, $orderQuantity, $itemsIds);

        return $items;
    }

    protected function getOrderItemsProducts($ordersIds, $orderQuantity = '', $itemsIds = null)
    {
        $items = array();

        foreach ($ordersIds as $id) {
            $itemIdToProductIdMap = array();
            $quantities = array();
            $itemQuantities = array();
            $orderItems = array();
            $orderItemsByItemId = array();

            $order = wc_get_order($id);

            if (!$order) {
                continue;
            }

            foreach ($order->get_items() as $itemId => $itemData) {
                if (!$itemsIds || in_array($itemId, $itemsIds)) {
                    $product = $itemData->get_product();
                    $productId = $product->get_ID();
                    $quantity = $itemData->get_quantity();
                    $quantities[$productId] = isset($quantities[$productId]) ? $quantities[$productId] + $quantity : $quantity;
                    $itemQuantities[$itemId] = $quantity;
                    $orderItems[$productId] = $itemData;
                    $itemIdToProductIdMap[$itemId] = $productId;
                    $orderItemsByItemId[$itemId] = $itemData;
                }
            }

            $orderProducts = uswbg_a4bGetPosts(array(
                'post_type' => array('product', 'product_variation'),
                'post__in' => empty($itemIdToProductIdMap) ? array(0) : array_values($itemIdToProductIdMap), 
                'orderby' => 'post__in',
            ));

            $orderSimpleProducts = uswbg_a4bGetPosts(array(
                'post_type' => 'any',
                'post__in' => empty($itemIdToProductIdMap) ? array(0) : array_values($itemIdToProductIdMap), 
                'orderby' => 'post__in',
            ));

            $orderProductsIndexedByPostId = array();
            foreach ($orderProducts as $orderProduct) {
                $orderProductsIndexedByPostId[$orderProduct->ID] = $orderProduct;
            }
            foreach ($orderSimpleProducts as $orderProduct) {
                $orderProductsIndexedByPostId[$orderProduct->ID] = $orderProduct;
            }

            $orderItemConsequentialNumber = 0;
            foreach ($itemIdToProductIdMap as $itemId => $productId) {
                if (empty($orderProductsIndexedByPostId[$productId])) {
                    continue;
                }
                $item = clone $orderProductsIndexedByPostId[$productId];
                $item->orderId = $id;
                $item->productInOrderQty = isset($itemQuantities[$itemId]) ? $itemQuantities[$itemId] : '';
                $item->orderItem = isset($orderItemsByItemId[$itemId]) ? $orderItemsByItemId[$itemId] : null;

                if ($orderQuantity === 'by-orders') {
                    if (isset($itemQuantities[$itemId])) {
                        for ($i = 0; $i < $itemQuantities[$itemId]; $i++) {
                            $item->orderItemConsequentialNumber = ++$orderItemConsequentialNumber;
                            $items[] = clone $item;
                        }
                    }
                } else {
                    $item->orderItemConsequentialNumber = ++$orderItemConsequentialNumber;
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    protected function getItemsByAtumPoOrderProducts()
    {
        if (empty($this->data['ordersIds'])) {
            return array();
        }

        return $this->getAtumPoOrderItemsProducts(
            isset($this->data['ordersIds']) ? $this->data['ordersIds'] : null,
            isset($this->data['orderQuantity']) ? $this->data['orderQuantity'] : null,
            isset($this->data['itemsIds']) ? $this->data['itemsIds'] : null
        );
    }

    protected function getAtumPoOrderItemsProducts($ordersIds, $orderQuantity = '', $itemsIds = null)
    {
        $items = array();

        foreach ($ordersIds as $id) {
            $itemIdToProductIdMap = array();
            $quantities = array();
            $itemQuantities = array();
            $orderItems = array();
            $orderItemsByItemId = array();

            $order = Helpers::get_atum_order_model( $id, TRUE);

            if (!$order) {
                continue;
            }

            foreach ($order->get_items() as $itemId => $itemData) {
                if (!$itemsIds || in_array($itemId, $itemsIds)) {
                    $productId = $itemData->get_meta('_product_id');
                    $quantity = $itemData->get_meta('_qty');
                    $quantities[$productId] = isset($quantities[$productId]) ? $quantities[$productId] + $quantity : $quantity;
                    $itemQuantities[$itemId] = $quantity;
                    $orderItems[$productId] = $itemData;
                    $itemIdToProductIdMap[$itemId] = $productId;
                    $orderItemsByItemId[$itemId] = $itemData;
                }
            }

            $orderProducts = uswbg_a4bGetPosts(array(
                'post_type' => array('product', 'product_variation'),
                'post__in' => empty($itemIdToProductIdMap) ? array(0) : array_values($itemIdToProductIdMap), 
                'orderby' => 'post__in',
            ));

            $orderProductsIndexedByPostId = array();
            foreach ($orderProducts as $orderProduct) {
                $orderProductsIndexedByPostId[$orderProduct->ID] = $orderProduct;
            }

            foreach ($itemIdToProductIdMap as $itemId => $productId) {
                $item = clone $orderProductsIndexedByPostId[$productId];
                $item->orderId = $id;
                $item->productInOrderQty = isset($itemQuantities[$itemId]) ? $itemQuantities[$itemId] : '';
                $item->orderItem = isset($orderItemsByItemId[$itemId]) ? $orderItemsByItemId[$itemId] : null;

                if ($orderQuantity === 'by-orders') {
                    if (isset($itemQuantities[$itemId])) {
                        for ($i = 0; $i < $itemQuantities[$itemId]; $i++) {
                            $items[] = $item;
                        }
                    }
                } else {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    protected function getAppointments()
    {
        $appointmentsIds = isset($this->data['appointmentsIds']) ? $this->data['appointmentsIds'] : null;

        if (!$appointmentsIds) {
            return array();
        }

        $request  = new \WP_Query(array('post_type' => 'wc_appointment', 'post__in' => $appointmentsIds, 'posts_per_page' => -1, 'ignore_sticky_posts' => 1));

        if ($request->have_posts()) {
            return $request->posts;
        } else {
            return array();
        }
    }

    protected function getFileOptions($post, $algorithm)
    {
        if (
            !empty($this->data['options'])
            && !empty($this->data['options'][$post->ID])
            && isset($this->data['options'][$post->ID]['qty'])
        ) {
            $quantityData = $this->data['options'][$post->ID]['qty'];
            $quantity = (empty($quantityData) || '0' === $quantityData) ? 0 : (int) $quantityData;
        } elseif (
            isset($_POST['useStockQuantity'])
            && 'true' === sanitize_key($_POST['useStockQuantity'])
            && 'yes' === get_post_meta($post->ID, '_manage_stock', true) 
        ) {
            $quantity = get_post_meta($post->ID, '_stock', true);
            $quantity = (empty($quantity) || '0' === $quantity) ? 0 : (int) $quantity;
        } elseif (
            isset($_POST['useStockQuantity'])
            && 'outofstock' === get_post_meta($post->ID, '_stock_status', true)
            && 'true' === sanitize_key($_POST['useStockQuantity'])
        ) {
            $quantity = 0;
        } else {
            $quantity = 1;
        }


        $fileOptions = parent::getFileOptions($post, $algorithm);
        $fileOptions['quantity'] = $quantity;

        return $fileOptions;
    }

    protected function getCodeField($post)
    {
        if ($this->activeTemplate->code_match) {
            if ('product_variation' === $post->post_type) {
                return $this->getCodeValue($post, $this->activeTemplate->variable_product_code);
            } elseif ('product' === $post->post_type) {
                return $this->getCodeValue($post, $this->activeTemplate->single_product_code);
            } elseif ('shop_order' === $post->post_type || 'shop_order_placehold' === $post->post_type) {
                return $this->getCodeValue($post, $this->activeTemplate->single_product_code);
            } else {
                return '';
            }
        } else {
            return isset($this->data['lineBarcode']) ? $this->getField($post, $this->data['lineBarcode']) : '';
        }
    }

    protected function getField($post, &$field, $lineNumber = "")
    {
        $value = parent::getField($post, $field, $lineNumber);

        if (!empty($value) || '0' === $value) {
            return $value;
        }

        $fieldName = (isset($this->fieldNames[$field['type']]) && is_string($this->fieldNames[$field['type']]))
            ? $this->fieldNames[$field['type']]
            : '';
        $isAddFieldName = UserSettings::getoption('fieldNameL' . $lineNumber, false);

        if (isset($this->shortcodesToOrderCfMap[$field['type']])) {
            $field['value'] = $this->shortcodesToOrderCfMap[$field['type']];
            $field['type'] = 'orderCustom';
        }

        switch ($field['type']) {
            case 'orderStandart':
                $value = $this->getStandardOrderField($post, $field['value']);
                $fieldName = isset($this->fieldNames['orderStandart'][$field['value']]) ? $this->fieldNames['orderStandart'][$field['value']] : '';
                break;
            case 'orderCustom':
                $value = $this->getCustomOrderField($post, $field['value']);
                $fieldName = isset($this->fieldNames['orderCustom'][$field['value']]) ? $this->fieldNames['orderCustom'][$field['value']] : '';
                break;
            case 'standart':
                $value = $this->getOrderPostField($post, $field);
                break;
            case 'price_with_tax':
            case 'actual-price-with-tax':
                $value = $this->getProductPriceWithTax($post);
                break;
            case 'sale-price-with-tax':
                $value = $this->getProductPriceWithTax($post, '_sale_price');
                break;
            case 'regular-price-with-tax':
                $value = $this->getProductPriceWithTax($post, '_regular_price');
                break;
            case 'wc_category':
                $value = $this->getProductCategories($post, $field);
                break;
            case 'wc_taxonomy':
                $termMeta = !empty($field['term_meta']) ? $field['term_meta'] : null;
                $value = $this->type === "order-products"
                    ? $this->getOrderProductAttribute($post, $field['value'], $termMeta)
                    : $this->getWoocommerceProductAttributeValueByName($post, $field['value'], $termMeta);
                $fieldName = $termMeta === null ? ucfirst($field['value']) : "";
                break;
            case 'wc_taxonomy_name':
                $termMeta = !empty($field['term_meta']) ? $field['term_meta'] : null;
                $value = $this->getWoocommerceProductAttributeValueByName($post, $field['value'], $termMeta);
                break;
            case 'local_attr':
                $value = $this->type === "order-products"
                    ? $this->getOrderProductAttribute($post, $field['value'], null, false)
                    : $this->getWoocommerceCustomAttributeValueByName($post, $field['value']);
                break;
            case 'product_dimensions':
                $value = $this->getProductDimensions($post);
                break;
            case 'order-total-items':
                $value = $this->getOrderTotalItems($post);
                break;
            case 'order-id':
                $value = $this->getOrderId($post);
                break;
            case 'order-product-qty':
                $value = $this->getOrderProductQty($post);
                break;
            case 'order-create-date':
            case 'order_create_date':
                $value = $this->getOrderCreateDate($post, $field);
                break;
            case 'order-completed-date':
                $value = $this->getOrderCompleteDate($post, $field);
                break;
            case 'order-cf':
                $value = $this->getOrderCustomField($post, $field);
                break;
            case 'product-description':
                $value = $this->getProductDescription($post, $field);
                break;
            case 'main-product-description':
                $value = $this->getMainProductDescription($post, $field);
                break;
            case 'product-name':
                $value = $this->getProductName($post, $field);
                break;
            case 'main-product-name':
                $value = $this->getMainProductName($post, $field);
                break;
            case 'order-tax':
                $value = $this->getOrderTax($post);
                break;
            case 'order-shipping-method':
                $value = $this->getOrderShippingMethod($post);
                break;
            case 'order-customer-provided-note':
                $value = $this->getOrderCustomerProvidedNote($post);
                break;
            case 'items-subtotal':
                $value = $this->getItemsSubtotal($post);
                break;
            case 'order-shipping':
                $value = $this->getOrderShipping($post);
                break;
            case 'order-cart-discount':
                $value = $this->getOrderCartDiscount($post);
                break;
            case 'order-subtotal':
                $value = $this->getOrderSubtotal($post);
                break;
            case 'order-total':
                $value = $this->getOrderTotal($post);
                break;
            case 'order-shipping-first-name':
                $value = $this->getOrderShippingFirstName($post);
                break;
            case 'order-shipping-last-name':
                $value = $this->getOrderShippingLastName($post);
                break;
            case 'order-shipping-city':
                $value = $this->getOrderShippingCity($post);
                break;
            case 'order-shipping-address-1':
                $value = $this->getOrderShippingAddress1($post);
                break;
            case 'order-shipping-address-2':
                $value = $this->getOrderShippingAddress2($post);
                break;
            case 'order-shipping-postcode':
                $value = $this->getOrderShippingPostcode($post);
                break;
            case 'order-shipping-phone':
                $value = $this->getOrderShippingPhone($post);
                break;
            case 'order-shipping-country':
                $value = $this->getOrderShippingCountry($post);
                break;
            case 'order-shipping-state':
                $value = $this->getOrderShippingState($post);
                break;
            case 'order-shipping-company':
                $value = $this->getOrderShippingCompany($post);
                break;
            case 'order-shipping-country-full-name':
                $value = $this->getOrderShippingCountryFullName($post);
                break;
            case 'order-shipping-state-full-name':
                $value = $this->getOrderShippingStateFullName($post);
                break;
            case 'order-billing-first-name':
                $value = $this->getOrderBillingFirstName($post);
                break;
            case 'order-billing-last-name':
                $value = $this->getOrderBillingLastName($post);
                break;
            case 'order-billing-city':
                $value = $this->getOrderBillingCity($post);
                break;
            case 'order-billing-address-1':
                $value = $this->getOrderBillingAddress1($post);
                break;
            case 'order-billing-address-2':
                $value = $this->getOrderBillingAddress2($post);
                break;
            case 'order-billing-postcode':
                $value = $this->getOrderBillingPostcode($post);
                break;
            case 'order-billing-phone':
                $value = $this->getOrderBillingPhone($post);
                break;
            case 'order-billing-email':
                $value = $this->getOrderBillingEmail($post);
                break;
            case 'order-billing-country':
                $value = $this->getOrderBillingCountry($post);
                break;
            case 'order-billing-state':
                $value = $this->getOrderBillingState($post);
                break;
            case 'order-billing-company':
                $value = $this->getOrderBillingCompany($post);
                break;
            case 'order-billing-country-full-name':
                $value = $this->getOrderBillingCountryFullName($post);
                break;
            case 'order-billing-state-full-name':
                $value = $this->getOrderBillingStateFullName($post);
                break;
            case 'main_product_image_url':
                $value = 'product_variation' === $post->post_type
                    ? get_the_post_thumbnail_url($post->post_parent, $this->getImageSizeAttributeForField($field))
                    : get_the_post_thumbnail_url($post, $this->getImageSizeAttributeForField($field));
                break;
            case 'wcAppointment':
                $value = $this->getStandardPostField($post, $field['value']);
                break;
            case 'wprm-nutrition-label':
                $id = $field["value"] ? get_post_meta($post->ID, $field["value"], true) : null;
                $value = $id ? do_shortcode('[wprm-nutrition-label id="' . $id . '"]') : "";
                break;
            case 'discount':
                $value = $this->getDiscountValue($post, $field);
                break;
            case 'pickup-location-address':
                $value = is_plugin_active('woocommerce-shipping-local-pickup-plus/woocommerce-shipping-local-pickup-plus.php')
                    ? $this->getOrderItemPickupLocationAddress($post, $field)
                    : '';
                break;
            case 'pickup-location-title':
                $value = is_plugin_active('woocommerce-shipping-local-pickup-plus/woocommerce-shipping-local-pickup-plus.php')
                    ? $this->getOrderItemPickupLocationTitle($post, $field)
                    : '';
                break;
            case 'pickup-date':
                $value = is_plugin_active('woocommerce-shipping-local-pickup-plus/woocommerce-shipping-local-pickup-plus.php')
                    ? $this->getOrderItemPickupDate($post, $field)
                    : '';
                break;
            case 'order-item-meta-field':
                $value = $this->getOrderItemMetaField($post, $field);
                break;
            case 'order-customer-id':
                $value = $this->getOrderCustomerId($post, $field);
                break;
            case 'order-user-display-name':
                $value = $this->getOrderUserDisplayName($post, $field);
                break;
            case 'order-coupon':
                $value = $this->getOrderCoupon($post, $field);
                break;
            case 'product_image_url':
                $value = $this->getProductImageUrl($post, $field);
                break;
            case 'main_gallery':
                $value = $this->getGalleryImageUrl($post, $field);
                break;
            case 'product-all-parent-attributes':
                $value = $this->getProductAllParentAttributes($post, $field);
                break;
            case 'product-all-attr':
                $value = $this->getProductAllAttribute($post, $field);
                break;
            case 'variation-all-attr':
                $value = $this->getVariationAllAttribute($post, $field);
                break;
            case 'variation-all-attr-with-names':
                if (!isset($field['args']) || !is_array($field['args'])) {
                    $field['args'] = array();
                }
                $field['args']['show-attr-name'] = 'true';
                $value = $this->getVariationAllAttribute($post, $field);
                break;
            case 'product_id_prefix':
                $value = $this->getProductIdWithPrefix($post, $field);
                break;
            case 'product-store-link':
                $value = $this->getPostPermalinkShort($post);
                break;
            case 'atum':
                $value = $this->getAtumInventoryManagementFieldValue($post, $field);
                break;
            case 'license_manager':
                $value = $this->getLicenseManagerFieldValue($post, $field);
                break;
            case 'link_add_to_cart':
                $value = $this->getLinkAddToCart($post, $field);
                break;
            case 'order-billing-full-name':
                $field['value'] = 'billing_user_name';
                $value = $this->getCustomOrderField($post, $field['value']);
                $fieldName = isset($this->fieldNames['orderCustom']['billing_user_name']) ? $this->fieldNames['orderCustom']['billing_user_name'] : '';
                break;
            case 'order-shipping-full-name':
                $field['value'] = 'shipping_user_name';
                $value = $this->getCustomOrderField($post, $field['value']);
                $fieldName = isset($this->fieldNames['orderCustom']['shipping_user_name']) ? $this->fieldNames['orderCustom']['shipping_user_name'] : '';
                break;
            case 'atum-order-supplier-name':
                $value = $this->getSupplierName($post, $field);
                break;
            case 'atum-order-supplier-code':
                $value = $this->getSupplierCode($post, $field);
                break;
            case 'atum-product-location':
                $value = $this->getAtumProductLocation($post, $field);
                break;
            case 'pbet-product-expire-date':
                $value = $this->getPbetProductExpireDate($post, $field);
                break;
            case 'pbet-product-batch':
                $value = $this->getSuppliegetPbetProductBatch($post, $field);
                break;
            case 'yoast-gtin8':
                $value = $this->getYoastGlobalIdentifierValue($post, $field, "gtin8");
                break;
            case 'yoast-gtin12-upc':
                $value = $this->getYoastGlobalIdentifierValue($post, $field, "gtin12");
                break;
            case 'yoast-gtin13-ean':
                $value = $this->getYoastGlobalIdentifierValue($post, $field, "gtin13");
                break;
            case 'yoast-gtin14-itf14':
                $value = $this->getYoastGlobalIdentifierValue($post, $field, "gtin14");
                break;
            case 'yoast-mpn':
                $value = $this->getYoastGlobalIdentifierValue($post, $field, "mpn");
                break;
            case 'woo-booking_item-booking-date':
                $value = $this->wooBookingItemBookingDate($post, $field);
            case 'order-item-consequential-number':
                $value = $this->getOrderItemConsequentialNumber($post, $field);
                break;
            case 'dokan-vendor-name':
                $value = $this->getDokanVendorName($post, $field);
                break;
            default:
                if (
                    ('shop_order' === $post->post_type || 'shop_order_placehold' === $post->post_type)
                    && !empty(get_post_meta($post->ID, '_customer_user', true))
                ) {
                    $value = $this->usersA4BarcodesMaker->getField(get_user_by('id', get_post_meta($post->ID, '_customer_user', true)), $field, $lineNumber);
                } else {
                    $value = '';
                }
        }

        $value = UserFieldsMatching::prepareFieldValue($isAddFieldName, $fieldName, $value, $lineNumber);

        return (string) apply_filters("label_printing_field_value", $value, $field, $post);
    }

    protected function getPostPermalinkShort($post)
    {
        if ('product' === $post->post_type) {
            $postLink = parent::getPostPermalinkShort($post);
            $postLink = str_replace('post_type=product&', '', $postLink);
            return $postLink;
        } elseif ('product_variation' === $post->post_type) {
            return $this->getVariationPermalinkShort($post);
        } else {
            return '';
        }
    }

    protected function getVariationPermalinkShort($post)
    {
        $variation = wc_get_product($post->ID);
        $url = parent::getPostPermalinkShort(get_post($variation->get_parent_id()));

        $variationPermalink = get_post_permalink($post->ID);
        $parts = parse_url($variationPermalink);
        parse_str($parts['query'], $query);
        $data        = array_intersect_key( array_combine( array_keys($query), array_values($query) ), $variation->get_variation_attributes() );

        if (empty($data)) {
            return $url;
        }

        $data = array_map( 'urlencode', $data );
        $keys = array_map( 'urlencode', array_keys( $data ) );

        $postLink = add_query_arg(array_combine($keys, $data ), $url);

        $postLink = str_replace('post_type=product&', '', $postLink);

        return $postLink;
    }

    protected function getPbetProductExpireDate($post, $field)
    {
        if (!is_plugin_active('product-batch-expiry-tracking-for-woocommerce/product-batch-expiry-tracking-for-woocommerce.php')) {
            return '';
        }

        global $wpdb;
        $expireDateStr = $wpdb->get_var("
            SELECT `expiry_date` 
            FROM {$wpdb->prefix}webis_pbet 
            WHERE `post_id` = {$post->ID}
            ORDER BY `id` DESC
        ");
        $format = isset($field['args']['format']) ? $field['args']['format'] : get_option('date_format');

        return !empty($expireDateStr) ? date($format, strtotime($expireDateStr)) : '';

    }

    protected function getSuppliegetPbetProductBatch($post, $field)
    {
        if (!is_plugin_active('product-batch-expiry-tracking-for-woocommerce/product-batch-expiry-tracking-for-woocommerce.php')) {
            return '';
        }

        global $wpdb;
        $batch = $wpdb->get_var("
            SELECT `batch_num` 
            FROM {$wpdb->prefix}webis_pbet 
            WHERE `post_id` = {$post->ID}
            ORDER BY `id` DESC
        ");

        return !empty($batch) ? $batch : '';
    }

    protected function getSupplierName($post, $field)
    {
        $supplierId = get_post_meta($post->ID, '_supplier', true);
        $supplierPost = !empty($supplierId) ? get_post($supplierId) : null;

        return !empty($supplierPost) ? $supplierPost->post_title : '';
    }

    protected function getSupplierCode($post, $field)
    {
        $supplierId = get_post_meta($post->ID, '_supplier', true);

        return !empty($supplierId) ? get_post_meta($supplierId, '_code', true) : '';
    }

    protected function getAtumProductLocation($post, $field)
    {
        return $this->termsObjectsToString(get_the_terms($post, 'atum_location'));
    }

    protected function getAtumInventoryManagementFieldValue($post, $field)
    {
        if (!is_plugin_active('atum-stock-manager-for-woocommerce/atum-stock-manager-for-woocommerce.php')) {
            return '';
        }

        $product = Helpers::get_atum_product($post->ID);

        if (!empty($product)) {
            switch ($field['value']) {
                case 'barcode':
                    $value = $product->get_barcode();
                    break;
                case 'supplier_sku':
                    $value = $product->get_supplier_sku();
                    break;
                default:
                    $value = '';
            }
        } else {
            $value = '';
        }

        return $value;
    }

    protected function getLicenseManagerFieldValue($post, $field)
    {
        if (!is_plugin_active('license-manager-for-woocommerce/license-manager-for-woocommerce.php')) {
            return '';
        }

        global $wpdb;

        $value = '';

        try {
            switch ($field['value']) {
                case 'license_key':
                    $order = $this->getOrder($post);

                    if (!$order || !$order->ID) {
                        return "";
                    }

                    $lmfwcLicenses = $wpdb->prefix . 'lmfwc_licenses';
                    $sql = "SELECT * FROM {$lmfwcLicenses} WHERE 1=1 ";
                    $sql .= $wpdb->prepare(' AND product_id = %d', intval($post->ID));
                    $sql .= $wpdb->prepare(' AND order_id = %d', intval($order->ID));
                    $data = $wpdb->get_row($sql);

                    if ($data && $data->license_key) {
                        $value = apply_filters('lmfwc_decrypt', $data->license_key);
                    }

                    break;
                default:
                    $value = '';
            }
        } catch (\Throwable $th) {
        }

        return $value;
    }

    protected function getLinkAddToCart($post, $field)
    {
        $value = '';

        try {
            switch ($field['value']) {
                case 'add_open':
                    $value = get_bloginfo("url") . "/us-barcodes-print-add-to-cart?id={$post->ID}";
                    break;
                default:
                    $value = '';
            }
        } catch (\Throwable $th) {
        }

        return $value;
    }

    protected function getProductIdWithPrefix($post, $field)
    {
        $prefixSymbol = isset($field['args']['symbol']) ? $field['args']['symbol'] : '0';
        $maxSize = isset($field['args']['max-size']) ? $field['args']['max-size'] : 0;

        return str_pad($post->ID, $maxSize, $prefixSymbol, STR_PAD_LEFT);
    }

    protected function getProductAllParentAttributes($post, $field)
    {
        $separator = isset($field['args']['separator']) ? $field['args']['separator'] : ', ';
        $showLabel = isset($field['args']['show-attr-name']) && 'true' === $field['args']['show-attr-name'];
        $product = wc_get_product($post->ID);

        if (empty($product)) {
            return '';
        }

        if (isset($post->orderItem)) {
            $allAttrsValues = array();
            foreach ($post->orderItem->get_all_formatted_meta_data('') as $metaData) {
                if (substr($metaData->key, 0, 1) !== '_') {
                    $attributeValue = $metaData->value;
                    if (0 === strpos($metaData->key, 'pa_')) {
                        $option_term = get_term_by('slug', $attributeValue, $metaData->key);
			            $attributeValue = $option_term && ! is_wp_error($option_term) ? str_replace(',', '\\,', $option_term->name) : str_replace(',', '\\,', $attributeValue);
                        $taxonomy = get_taxonomy($metaData->key);
                        $attributeLabel = !empty($taxonomy) ? $taxonomy->labels->singular_name : $metaData->key;
                    } else {
                        $attributeLabel = $metaData->key;
                    }
                    $allAttrsValues[] = ($showLabel ? $attributeLabel . ': ' : '') . $attributeValue;
                }
            }

            return implode($separator, array_filter($allAttrsValues));
        } elseif ('product_variation' === $post->post_type) {
            $parentPost = get_post($post->post_parent);
            $parentProduct = wc_get_product($parentPost->ID);

            $parentNotVariableAttributes = array_filter($parentProduct->get_attributes(), function($wcAttribute){
                return !$wcAttribute->get_variation();
            });
            $parentNotVariableAttributesOptions = $this->getAttributesOptions($parentNotVariableAttributes);

            $attributesValues = array();
            foreach ($parentNotVariableAttributesOptions as $attributesOption) {
                $attributesValues[] = ($showLabel ? ($attributesOption['label'] . ': ') : '') . implode($separator, array_filter($attributesOption['options']));
            }
            return implode($separator, $attributesValues);
        } elseif ('product' === $post->post_type) {
            if ('variable' === $product->get_type()) {
                $parentNotVariableAttributes = array_filter($product->get_attributes(), function($wcAttribute){
                    return !$wcAttribute->get_variation();
                });
                $parentNotVariableAttributesOptions = $this->getAttributesOptions($parentNotVariableAttributes);

                $attributesValues = array();
                foreach ($parentNotVariableAttributesOptions as $attributesOption) {
                    $attributesValues[] = ($showLabel ? ($attributesOption['label'] . ': ') : '') . implode($separator, array_filter($attributesOption['options']));
                }
                return implode($separator, $attributesValues);
            } elseif ('simple' === $product->get_type()) {
                $simpleProductAttributes = $product->get_attributes();
                $simpleProductAttributesOptions = $this->getAttributesOptions($simpleProductAttributes);

                $attributesValues = array();
                foreach ($simpleProductAttributesOptions as $attributesOption) {
                    $attributesValues[] = ($showLabel ? ($attributesOption['label'] . ': ') : '') . implode($separator, array_filter($attributesOption['options']));
                }
                return implode($separator, $attributesValues);
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    protected function getProductAllAttribute($post, $field)
    {
        $separator = isset($field['args']['separator']) ? $field['args']['separator'] : ', ';
        $showLabel = isset($field['args']['show-attr-name']) && 'true' === $field['args']['show-attr-name'];
        $product = wc_get_product($post->ID);

        if (empty($product)) {
            return '';
        }

        if (isset($post->orderItem)) {
            $allAttrsValues = array();
            foreach ($post->orderItem->get_all_formatted_meta_data('') as $metaData) {
                if (substr($metaData->key, 0, 1) !== '_') {
                    $attributeValue = $metaData->value;
                    if (0 === strpos($metaData->key, 'pa_')) {
                        $option_term = get_term_by('slug', $attributeValue, $metaData->key);
                        $attributeValue = $option_term && ! is_wp_error($option_term) ? str_replace(',', '\\,', $option_term->name) : str_replace(',', '\\,', $attributeValue);
                        $taxonomy = get_taxonomy($metaData->key);
                        $attributeLabel = !empty($taxonomy) ? $taxonomy->labels->singular_name : $metaData->key;
                    } else {
                        $attributeLabel = $metaData->key;
                    }
                    $allAttrsValues[] = ($showLabel ? $attributeLabel . ': ' : '') . $attributeValue;
                }
            }

            return implode($separator, array_filter($allAttrsValues));
        } elseif ('product_variation' === $post->post_type) {

            if (!method_exists($product, 'get_variation_attributes')) {
                return '';
            }

            $attrsInfo = $product->get_variation_attributes(false);

            array_walk($attrsInfo, function(&$attribute, $attribute_name) use ($showLabel) {
                if (0 === strpos($attribute_name, 'pa_')) {
                    $option_term = get_term_by('slug', $attribute, $attribute_name);
                    $attribute = $option_term && ! is_wp_error($option_term)
                        ? str_replace(',', '\\,', $option_term->name)
                        : str_replace(',', '\\,', $attribute);

                    $taxonomy = get_taxonomy($attribute_name);
                    $attributeLabel = !empty($taxonomy) ? $taxonomy->labels->singular_name : $attribute_name;
                } else {
                    $attributeLabel = $attribute_name;
                }

                if ($showLabel && !empty($attribute)) {
                    $attribute = $attributeLabel . ': ' . $attribute;
                }
            });

            return implode($separator, array_filter($attrsInfo));
        } elseif ('product' === $post->post_type) {
            if ('variable' === $product->get_type()) {
                $parentNotVariableAttributes = array_filter($product->get_attributes(), function($wcAttribute){
                    return !$wcAttribute->get_variation();
                });
                $parentNotVariableAttributesOptions = $this->getAttributesOptions($parentNotVariableAttributes);

                $attributesValues = array();
                foreach ($parentNotVariableAttributesOptions as $attributesOption) {
                    $attributesValues[] = ($showLabel ? ($attributesOption['label'] . ': ') : '') . implode($separator, array_filter($attributesOption['options']));
                }
                return implode($separator, $attributesValues);
            } elseif ('simple' === $product->get_type()) {
                $simpleProductAttributes = $product->get_attributes();
                $simpleProductAttributesOptions = $this->getAttributesOptions($simpleProductAttributes);

                $attributesValues = array();
                foreach ($simpleProductAttributesOptions as $attributesOption) {
                    $attributesValues[] = ($showLabel ? ($attributesOption['label'] . ': ') : '') . implode($separator, array_filter($attributesOption['options']));
                }
                return implode($separator, $attributesValues);
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    protected function getVariationAllAttribute($post, $field)
    {
        $separator = isset($field['args']['separator']) ? $field['args']['separator'] : ', ';
        $showLabel = isset($field['args']['show-attr-name']) && 'true' === $field['args']['show-attr-name'];
        $product = wc_get_product($post->ID);

        if (empty($product)) {
            return '';
        }

        if (isset($post->orderItem)) {
            $allAttrsValues = array();
            foreach ($post->orderItem->get_all_formatted_meta_data('') as $metaData) {
                if (substr($metaData->key, 0, 1) !== '_') {
                    $attributeValue = $metaData->value;
                    if (0 === strpos($metaData->key, 'pa_')) {
                        $option_term = get_term_by('slug', $attributeValue, $metaData->key);
                        $attributeValue = $option_term && ! is_wp_error($option_term) ? str_replace(',', '\\,', $option_term->name) : str_replace(',', '\\,', $attributeValue);
                        $taxonomy = get_taxonomy($metaData->key);
                        $attributeLabel = !empty($taxonomy) ? $taxonomy->labels->singular_name : $metaData->key;
                    } else {
                        $attributeLabel = $metaData->key;
                    }
                    $allAttrsValues[] = ($showLabel ? $attributeLabel . ': ' : '') . $attributeValue;
                }
            }

            return implode($separator, array_filter($allAttrsValues));
        } elseif ('product_variation' === $post->post_type) {

            if (!method_exists($product, 'get_variation_attributes')) {
                return '';
            }

            $attrsInfo = $product->get_variation_attributes(false);
            array_walk($attrsInfo, function(&$attribute, $attribute_name) use ($showLabel) {
                if (0 === strpos($attribute_name, 'pa_')) {
                    $option_term = get_term_by('slug', $attribute, $attribute_name);
                    $attribute = $option_term && ! is_wp_error($option_term)
                        ? str_replace(',', '\\,', $option_term->name)
                        : str_replace(',', '\\,', $attribute);

                    $taxonomy = get_taxonomy($attribute_name);
                    $attributeLabel = !empty($taxonomy) ? $taxonomy->labels->singular_name : $attribute_name;
                } else {
                    $attributeLabel = $attribute_name;
                }

                if ($showLabel && !empty($attribute)) {
                    $attribute = $attributeLabel . ': ' . $attribute;
                }
            });

            return implode($separator, array_filter($attrsInfo));
        }  else {
            return '';
        }
    }

    private function getAttributesOptions($parentNotVariableAttributes)
    {
        $attributesOptions = array();

        foreach ($parentNotVariableAttributes as $parentNotVariableAttribute) {
            if ($parentNotVariableAttribute->is_taxonomy()) {
                $attributesOptions[] = array(
                    'label' => $parentNotVariableAttribute->get_taxonomy_object()->attribute_label,
                    'options' => wp_list_pluck($parentNotVariableAttribute->get_terms(), 'name'),
                );
            } else {
                $attributesOptions[] = array(
                    'label' => $parentNotVariableAttribute->get_name(),
                    'options' => $parentNotVariableAttribute->get_options(),
                );
            }
        }

        return $attributesOptions;
    }

    protected function getProductImageUrl($post, $field)
    {
        return get_the_post_thumbnail_url(
            $post,
            $this->getImageSizeAttributeForField($field)
        );
    }

    protected function getGalleryImageUrl($post, $field)
    {
        $galleryImageIndex = isset($field['args']['image-number']) && !empty(intval($field['args']['image-number']))
            ? intval($field['args']['image-number']) - 1
            : 0;

        if (in_array($post->post_type, array('product_variation', 'product'))) {
            $galleryImagesIds = (new \WC_product('product_variation' === $post->post_type ? $post->post_parent : $post->ID))->get_gallery_image_ids();
        } else {
            $galleryImagesIds = array();
        }

        $useMainIfNoGallery = isset($field['args']['use_main_if_no_gallery']) && 'true' === $field['args']['use_main_if_no_gallery'];

        return isset($galleryImagesIds[$galleryImageIndex])
            ? wp_get_attachment_image_url($galleryImagesIds[$galleryImageIndex], $this->getImageSizeAttributeForField($field))
            : ($useMainIfNoGallery ? $this->getProductImageUrl($post, $field) : Variables::$A4B_PLUGIN_BASE_URL . 'assets/img/no-gallery-image.png');
    }

    protected function getRegisteredImageSizesArray()
    {
        global $_wp_additional_image_sizes;
        return array_unique(array_merge(get_intermediate_image_sizes(), array_keys($_wp_additional_image_sizes)), SORT_REGULAR);
    }

    protected function getImageSizeAttributeForField($field)
    {
        $size = isset($field['args']['size']) ? $field['args']['size'] : 'medium';

        return in_array($size, $this->getRegisteredImageSizesArray())
            ? $size
            : 'medium';
    }

    protected function getOrderItemMetaField($post, $field)
    {
        try {
            if (!property_exists($post, 'orderItem')) {
                return '';
            }

            if (metadata_exists('order_item', $post->orderItem->get_id(), $field['value'])) {
                $index = isset($field['args']['index']) && !empty(intval($field['args']['index']))
                    ? intval($field['args']['index'])
                    : 0;
                $valueArr = get_metadata('order_item', $post->orderItem->get_id(), $field['value'], false);
                $value = is_array($valueArr) && isset($valueArr[$index]) ? $valueArr[$index] : '';
            }

            return !empty($value) ? $value : '';
        } catch (\Exception $e) {
            return '';
        }
    }

    protected function getOrderCustomerId($post, $field)
    {
        $order = $this->getOrder($post);

        if (empty($order)) {
            return '';
        }

        $orderCustomerId = $order->get_customer_id();

        return !empty($orderCustomerId) ? $orderCustomerId : '';
    }

    protected function getOrderUserDisplayName($post, $field)
    {
        $order = $this->getOrder($post);

        if (empty($order)) {
            return '';
        }

        $orderUser = $order->get_user();

        return !empty($orderUser) ? $orderUser->display_name : '';
    }

    protected function getOrderCoupon($post, $field)
    {
        $order = $this->getOrder($post);

        if (empty($order)) {
            return '';
        }

        $orderCoupons = $order->get_coupons();

        if (empty($orderCoupons) || !is_array($orderCoupons)) {
            return '';
        }

        $orderCouponsCodes = array();
        foreach ($orderCoupons as $coupon) {
            $orderCouponsCodes[] = $coupon->get_code();
        }

        return !empty($orderCouponsCodes) ? implode(', ', $orderCouponsCodes) : '';
    }

    protected function getOrderCustomField($post, &$field)
    {
        $order = $this->getOrder($post);

        if (empty($order)) {
            return '';
        }

        $orderPost = get_post($order->get_id());

        return $this->getCustomFieldsValues($orderPost, $field);
    }

    protected function getDiscountValue($post, &$field)
    {
        if (empty($field['args']['old-price']) || empty($field['args']['new-price'])) {
            return '';
        }

        $oldPrice = (float)$this->getProductMeta($post, $field['args']['old-price'], true);
        $newPrice = (float)$this->getProductMeta($post, $field['args']['new-price'], true);

        if (
            empty($oldPrice)
            || empty($newPrice)
            || $newPrice > $oldPrice
        ) {
            return '';
        }

        $discountValue = ($oldPrice - $newPrice) * 100 / $oldPrice;

        if ((isset($field['args']['show-decimal']) && 'true' === $field['args']['show-decimal'])) {

            $discountValue = $this->round($discountValue, $field);

            $result = number_format($discountValue, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
        } else {

            $discountValue = $this->round($discountValue, $field);

            if (false !== strpos((string)$discountValue, '.')) {
                $parts = explode('.', $discountValue);
                $result = $parts[0];
            } else {
                $result = $discountValue;
            }

        }

        return $result;
    }

    protected function getOrderItemPickupLocationAddress($post, $field)
    {
        $order = $this->getOrder($post);
        $pickupLocationAddresses = array();

        if (!empty($order)) {
            foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
                if ( wc_local_pickup_plus_shipping_method_id() === $shipping_item['method_id'] ) {
                    $pickupLocationAddresses[] = wc_local_pickup_plus()->get_orders_instance()->get_order_items_instance()->get_order_item_pickup_location_address($shipping_item_id); 
                }
            }
        }

        return !empty($pickupLocationAddresses) ? implode(', ', $pickupLocationAddresses) : '';
    }

    protected function getOrderItemPickupLocationTitle($post, $field)
    {
        $order = $this->getOrder($post);
        $pickupLocationAddresses = array();

        if (!empty($order)) {
            foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
                if ( wc_local_pickup_plus_shipping_method_id() === $shipping_item['method_id'] ) {
                    $pickupLocationAddresses[] = wc_local_pickup_plus()->get_orders_instance()->get_order_items_instance()->get_order_item_pickup_location_name( $shipping_item_id );
                }
            }
        }

        return !empty($pickupLocationAddresses) ? implode(', ', $pickupLocationAddresses) : '';
    }

    protected function getOrderItemPickupDate($post, $field)
    {
        $order = $this->getOrder($post);
        $pickupDates = array();

        if (!empty($order)) {
            foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
                if ( wc_local_pickup_plus_shipping_method_id() === $shipping_item['method_id'] ) {
                    $appointment = wc_local_pickup_plus()->get_appointments_instance()->get_shipping_item_appointment($shipping_item_id);
                    $pickupDate = $appointment ? $appointment->get_start() : null;
                    if ( $pickupDate && ( $appointment && $appointment->is_anytime() ) ) {
                        $pickupDates[] = esc_html(date_i18n( wc_date_format(), $pickupDate->getTimestamp() + $pickupDate->getOffset()));
                    } elseif ( $pickupDate ) {
                        $pickupDates[] = esc_html(sprintf(__('%1$s at %2$s', 'woocommerce' ), date_i18n( wc_date_format(), $pickupDate->getTimestamp() + $pickupDate->getOffset() ), date_i18n( wc_time_format(), $pickupDate->getTimestamp() + $pickupDate->getOffset())));
                    } else {
                        $pickupDates[] = '&mdash';
					}
                }
            }
        }

        return !empty($pickupDates) ? implode(', ', $pickupDates) : '';
    }

    protected function getOrderProductAttribute($post, $taxonomyName, $termMeta = null, $isGlobal = true)
    {
        if (!isset($post->orderItem)) {
            return '';
        }

        $metaKey = $isGlobal ? 'pa_' . $taxonomyName : strtolower($taxonomyName);

        if ($post->orderItem->meta_exists($metaKey)) {
            $value = $post->orderItem->get_meta($metaKey);
        } else {
            $value = $isGlobal
                ? $this->getWoocommerceProductAttributeValueByName($post, $taxonomyName, $termMeta)
                : $this->getWoocommerceCustomAttributeValueByName($post, $taxonomyName);
        }

        return !empty($value) ? $value : '';
    }

    protected function getProductDimensions($post)
    {
        try {
            $str = UserSettings::getoption('lwhFormat', '%L x %W x %H');

            $l = get_post_meta($post->ID, "_length", true);
            $str = str_replace("%L", $l, $str);

            $w = get_post_meta($post->ID, "_width", true);
            $str = str_replace("%W", $w, $str);

            $h = get_post_meta($post->ID, "_height", true);
            $str = str_replace("%H", $h, $str);

            return $str;
        } catch (\Throwable $th) {
            return "";
        }
    }

    protected function getCustomFieldsValues($post, &$field)
    {
        $customFields = array_map('trim', explode(',', $field['value']));
        $values = array();
        foreach ($customFields as $customField) {
            if (empty($customField) && !$this->shouldAddCurrency(array_merge($field, array('value' => $customField)))) {
                continue;
            }

            if (isset($this->orderCfToOrderWcMethodMap[$customField]) && method_exists($this, $this->orderCfToOrderWcMethodMap[$customField])) {
                $values[] = $this->{$this->orderCfToOrderWcMethodMap[$customField]}($post);
            } elseif (in_array($customField, array('hwp_product_gtin'))) {
                $states = apply_filters('woocommerce_countries', include WC()->plugin_path() . '/i18n/states.php');
                $hwpProductGtin = $this->getProductMeta($post, 'hwp_product_gtin', true);
                $hwpVarGtin = $this->getProductMeta($post, 'hwp_var_gtin', true);
                $values[] = $hwpVarGtin ? $hwpVarGtin : $hwpProductGtin;
            }  else {
                $values[] = ($this->shouldAddCurrency(array_merge($field, array('value' => $customField))))
                    ? $this->getValueWithCurrency($this->getProductMeta($post, $customField, true), $field)
                    : $this->getProductMeta($post, $customField, true);
            }

            $values = array_filter($values, function($value) {
                return ($value !== null && $value !== false && $value !== '');
            });
        }

        return implode(',', $values);
    }

    protected function getCountryFullName($post, $customField)
    {
        $countries = apply_filters('woocommerce_countries', include WC()->plugin_path() . '/i18n/countries.php');
        $countryCode = $this->getProductMeta($post, str_replace('_full_name', '', $customField), true);
        $countryFullName = (is_array($countries) && isset($countries[$countryCode])) ? $countries[$countryCode] : $countryCode;

        return preg_replace("/\s\(.*\)/", '', $countryFullName);
    }

    protected function getStateFullName($post, $customField)
    {
        $states = apply_filters('woocommerce_countries', include WC()->plugin_path() . '/i18n/states.php');
        $countryCode = $this->getProductMeta($post, str_replace('_state_full_name', '_country', $customField), true);
        $stateCode = $this->getProductMeta($post, str_replace('_full_name', '', $customField), true);

        return (is_array($states) && isset($states[$countryCode][$stateCode])) ? $states[$countryCode][$stateCode] : $stateCode;
    }

    protected function getOrderSubtotal($post)
    {
        $order = $this->getOrder($post);

        if (empty($order)) {
            return '';
        }

        $orderTotal = $order->get_total();
        $orderTax = $order->get_total_tax();
        $orderSubtotal = $orderTotal - $orderTax;

        return $this->getValueWithCurrency($orderSubtotal);
    }

    protected function getOrderTotal($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $this->getValueWithCurrency($order->get_total())
            : '';
    }
    protected function shouldAddCurrency($field)
    {
        return (isset($field['args']['is_price']) && 'true' === $field['args']['is_price'])
            || in_array($field['value'], array(
                '_price',
                '_regular_price',
                '_sale_price',
                '_order_total',
                '_order_shipping',
                '_cart_discount',
                '_order_tax',
                '_order_shipping_tax',
                '_cart_discount_tax',
                '_order_subtotal',
            ));
    }

    protected function getProductMeta($post, $param, $single = true)
    {
        if (0 === strpos($param, 'product.')) {
            $priority = CustomFieldPriority::PRODUCT;
            $param = substr($param, 8); 
        } elseif (0 === strpos($param, 'variation.')) {
            $priority = CustomFieldPriority::VARIATION;
            $param = substr($param, 10); 
        } else {
            $priority = UserSettings::getOption('cfPriority', 'variation');
        }

        if (
            'product' === $priority
            && 'product_variation' === $post->post_type
            && in_array($param, get_post_custom_keys($post->ID))
            && in_array($param, get_post_custom_keys($post->post_parent))
        ) {
            return get_post_meta($post->post_parent, $param, $single);
        } else {
            $customKeys = get_post_custom_keys($post->ID);
            if ($customKeys && in_array($param, $customKeys)) {
                return get_post_meta($post->ID, $param, $single);
            } elseif ('product_variation' === $post->post_type) {
                return get_post_meta($post->post_parent, $param, $single);
            } elseif ('shop_order_placehold' === $post->post_type) {
                $result = \Automattic\WooCommerce\Utilities\OrderUtil::get_post_or_object_meta($post, $this->getOrder($post), $param, true);
                if ($this->shouldAddCurrency(array_merge(array('value' => $param)))) {
                    $result = $this->getValueWithCurrency($result);
                }

                return $result;
            } else {
                return '';
            }
        }
    }

    protected function getProductPriceWithTax($post, $priceType = '_price')
    {
        $productFactory = new \WC_Product_Factory();
        $product = $productFactory->get_product($post->ID);

        if (!empty($product)) {
            switch ($priceType) {
                case '_price':
                    $price = wc_get_price_including_tax($product);
                    break;
                case '_regular_price':
                    $price = wc_get_price_including_tax($product, array('qty' => 1, 'price' => get_post_meta($post->ID, '_regular_price', true)));
                    break;
                case '_sale_price':
                    $price = wc_get_price_including_tax($product, array('qty' => 1, 'price' => get_post_meta($post->ID, '_sale_price', true)));
                    break;
                default:
                    $price = wc_get_price_including_tax($product);
            }

            return $this->getValueWithCurrency($price);
        } else {
            return '';
        }
    }

    protected function getWoocommerceProductTerms($post, $taxonomy, $termMeta = null)
    {
        if ('product' === $post->post_type) {
            return $this->termsObjectsToString(get_the_terms($post, 'pa_' . $taxonomy), $termMeta);
        }

        if ('product_variation' === $post->post_type) {
            $term = get_term_by(
                'slug',
                get_post_meta($post->ID, 'attribute_pa_' . $taxonomy, true),
                'pa_' . $taxonomy
            );

            return $term
                ? (empty($termMeta)
                    ? $term->name
                    : get_term_meta($term->term_id, $termMeta, true))
                : (
                    'nothing' === UserSettings::getOption('attributeIsntSpec', 'nothing')
                    ? ''
                    : $this->termsObjectsToString(get_the_terms($post->post_parent, 'pa_' . $taxonomy), $termMeta));
        }
    }

    protected function getWoocommerceProductAttributeValueByName($post, $taxonomyName, $termMeta = null)
    {
        $attrPriority = UserSettings::getOption('attrPriority', 'one');

        $globalAttributeValue = $this->getGlobalAttributeValue($post, $taxonomyName, $termMeta);

        if (empty($globalAttributeValue) && $post->post_parent > 0 ) {
            $parentPost = get_post($post->post_parent);
            $globalAttributeValue = $this->getGlobalAttributeValue($parentPost, $taxonomyName, $termMeta);
        }

        if ('product' === $post->post_type) {
            $attributes = get_post_meta($post->ID, '_product_attributes', true);
        }

        if ('product_variation' === $post->post_type) {
            $attributes = get_post_meta($post->post_parent, '_product_attributes', true);
        }

        if (!empty($attributes)) {

            $taxonomy = null;
            foreach ($attributes as $slug => $attribute) {
                if ($taxonomyName === $attribute['name']) {
                    $taxonomy = $slug;
                    break;
                }
            }

            foreach ($attributes as $slug => $attribute) {
                if ($taxonomyName === $slug) {
                    $taxonomy = $slug;
                    break;
                }
            }

            if (null !== $taxonomy) {
                if ('product' === $post->post_type) {
                    $localAttributeValue = $this->getWcAttributesComaSeparatedString($attributes[$taxonomy]['value']);
                }

                if ('product_variation' === $post->post_type) {
                    $localAttributeValue = get_post_meta($post->ID, "attribute_{$taxonomy}", true)
                        ?: $this->getWcAttributesComaSeparatedString($attributes[$taxonomy]['value']);
                }
            } else {
                $localAttributeValue = null;
            }
        } else {
            $localAttributeValue = null;
        }

        if ('global' === $attrPriority) {
            $attrValue = !empty($globalAttributeValue) ? $globalAttributeValue : '';
        } elseif ('local' === $attrPriority) {
            $attrValue = !empty($localAttributeValue) ? $localAttributeValue : '';
        } else {
            if (!empty($localAttributeValue)) {
                $attrValue = $localAttributeValue;
            } elseif (!empty($globalAttributeValue)) {
                $attrValue = $globalAttributeValue;
            } else {
                $attrValue = "";
            }
        }

        return $attrValue;
    }

    protected function getGlobalAttributeValue($post, $taxonomyName, $termMeta)
    {
        global $wpdb;

        $globalAttributeValue = $this->getWoocommerceProductTerms($post, $taxonomyName, $termMeta);

        if (empty($globalAttributeValue)) {

            $wc_attribute_taxonomy = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE `attribute_label` = %s",
                $taxonomyName
            ));

            if (null !== $wc_attribute_taxonomy) {
                $taxonomy_slag = $wc_attribute_taxonomy->attribute_name;
                $globalAttributeValue = $this->getWoocommerceProductTerms($post, $taxonomy_slag, $termMeta);

            } else {
                $globalAttributeValue = null;
            }
        }

        return $globalAttributeValue;
    }

    protected function getWoocommerceCustomAttributeValueByName($post, $taxonomyName)
    {
        $localAttributeValue = '';
        $attributes = array();

        if ('product' === $post->post_type) {
            $attributes = get_post_meta($post->ID, '_product_attributes', true);
        } elseif ('product_variation' === $post->post_type) {
            $attributes = get_post_meta($post->post_parent, '_product_attributes', true);
        }

        if (!empty($attributes)) {
            $taxonomy = null;

            foreach ($attributes as $slug => $attribute) {
                if ($taxonomyName === $attribute['name']) {
                    $taxonomy = $slug;
                    break;
                }
            }

            if (null === $taxonomy) {
                foreach ($attributes as $slug => $attribute) {
                    if ($taxonomyName === $slug) {
                        $taxonomy = $slug;
                        break;
                    }
                }
            }

            if (null !== $taxonomy) {
                if ('product' === $post->post_type) {
                    $localAttributeValue = $this->getWcAttributesComaSeparatedString($attributes[$taxonomy]['value']);
                }

                if ('product_variation' === $post->post_type) {
                    $localAttributeValue = get_post_meta($post->ID, "attribute_{$taxonomy}", true)
                        ?: $this->getWcAttributesComaSeparatedString($attributes[$taxonomy]['value']);
                }
            }
        }

        return $localAttributeValue;
    }

    protected function categoryObjectsToString($terms, $termMeta = null, $args = array())
    {
        if ($terms && !is_wp_error($terms)) {

            $categoryChains = array();
            foreach ($terms as $term) {
                $levels = $this->getLevels(array($term));
                if (!empty($levels)) {
                    if (isset($args['level']) && 'top' === $args['level']) {
                        $categoryChains[] = reset($levels);
                    } elseif (isset($args['level']) && 'last' === $args['level']) {
                        $categoryChains[] = end($levels);
                    } else {
                        $categoryChains[] = implode(isset($args['separator']) ? $args['separator'] : "&#10141;", $levels);
                    }
                }
            }

            if (!empty($categoryChains)) {
                return implode('; ', $categoryChains);
            } else {
                return null;
            }
        }

        return null;
    }

    protected function getLevels($parents, $result = array())
    {
        $levelItems = array();
        $nextLevelParents = array();

        foreach ($parents as $currentLevelParent) {
            $levelItems[] = $currentLevelParent->name;

            if (!empty($currentLevelParent->childrens)) {
                $nextLevelParents += $currentLevelParent->childrens;
            }
        }

        $result[] = implode(', ', $levelItems);

        return !empty($nextLevelParents) ? $this->getLevels($nextLevelParents, $result) : $result;
    }

    protected function getWcAttributesComaSeparatedString($attributeValuesString)
    {
        $values = explode('|', $attributeValuesString);
        $values = array_map('trim', $values);

        return implode(', ', $values);
    }

    protected function sortByIds($targetArray, $orderArray)
    {
        $targetArray = array_values($targetArray);
        $foundProductsIds = array();
        foreach ($targetArray as $key => $product) {
            if ('product_variation' === $product->post_type) {
                $foundProductsIds[$key] = $product->post_parent;
            } else {
                $foundProductsIds[$key] = $product->ID;
            }
        }


        if ($orderArray) {
            uksort($targetArray, function ($key1, $key2) use ($orderArray, $foundProductsIds) {
                $product1Id = $foundProductsIds[$key1];
                $product2Id = $foundProductsIds[$key2];

                if (array_search($product1Id, $orderArray) === array_search($product2Id, $orderArray)) {
                    return 0;
                } else {
                    return array_search($product1Id, $orderArray) > array_search($product2Id, $orderArray) ? 1 : -1;
                }

            });
        }

        return $targetArray;
    }

    protected function sortByTitle($targetArray)
    {
        usort($targetArray, function($item1, $item2) {
            if (property_exists($item1, 'post_title') && property_exists($item2, 'post_title')) {
                return strcmp($item1->post_title, $item2->post_title);
            } else {
                return 0;
            }
        });

        return $targetArray;
    }

    protected function getStandardPostField($post, $field)
    {
        if (in_array($field, array('post_title', 'post_content', 'post_excerpt')) && !empty($post->post_parent)) {
            $post = get_post($post->post_parent);
        }

        if ('post_title_variation' === $field) {
            $field = 'post_title';
        }

        return isset($post->{$field}) ? $post->{$field} : '';
    }

    protected function getStandardOrderField($post, $field)
    {
        $order = isset($post->orderId) ? get_post($post->orderId) : $post;

        $value = isset($order->{$field}) ? $order->{$field} : '';

        return $value;
    }

    protected function getCustomOrderField($post, $field)
    {

        $order = $this->getOrder($post);

        if (empty($order)) {
            return '';
        }
        $orderPost = get_post($order->get_id());


        if (isset($this->orderCfToOrderWcMethodMap[$field]) && method_exists($this, $this->orderCfToOrderWcMethodMap[$field])) {
            $result = $this->{$this->orderCfToOrderWcMethodMap[$field]}($post);
        } elseif ('billing_user_name' === $field) {
            $result = $order->get_formatted_billing_full_name();
        } elseif ('shipping_user_name' === $field) {
            $result = $order->get_formatted_shipping_full_name();
        } elseif (in_array($field, array('_billing_country_full_name', '_shipping_country_full_name'))) {
            $result = $this->getCountryFullName($orderPost, $field);
        } elseif (in_array($field, array('_billing_state_full_name', '_shipping_state_full_name'))) {
            $result = $this->getStateFullName($orderPost, $field);
        } elseif (in_array($field, get_post_custom_keys($orderPost->ID) ?: array())) {
            $result = get_post_meta($orderPost->ID, $field, true);

            if ($this->shouldAddCurrency(array('value' => $field))) {
                $result = $this->getValueWithCurrency($result);
            }
        } elseif ('shop_order_placehold' === $post->post_type) {
            $result = \Automattic\WooCommerce\Utilities\OrderUtil::get_post_or_object_meta($post, $order, $field, true);
            if ($this->shouldAddCurrency(array('value' => $field))) {
                $result = $this->getValueWithCurrency($result);
            }
        } else {
            $result = '';
        }

        return $result;
    }

    protected function getTemplateReplacements($post, $shortcodesArgs)
    {
        $replacements = new \ArrayObject();

        if (!empty($this->prodListTemplate) && 'orders' === $this->type) {
            $replacements[$this->prodListShortcode] = $this->getProdListHtml($post);
        }

        if (
            is_plugin_active('tier-pricing-table/tier-pricing-table.php')
            || is_plugin_active('tier-pricing-table-premium/tier-pricing-table.php')
        ) {
            if (!empty($this->tieredPriceTemplate) && in_array($post->post_type, array('product', 'product_variation'))) {
                $replacements[$this->tieredPriceShortcode] = $this->getTieredPriceListHtml($post);
            }
        }

        foreach ($shortcodesArgs as $shortCode => $args) {
            $replacements[$shortCode] = $this->getShortcodeFieldValue($post, $args);
        }
        return $replacements;
    }

    protected function getProductCategories($post, $field)
    {
        $terms = 'product_variation' === $post->post_type
            ? get_the_terms($post->post_parent, 'product_cat')
            : get_the_terms($post, 'product_cat');

        $termsHierarchy = array();

        while (!empty($terms)) {
            foreach ($terms as &$term) {
                if ($this->termHaveParent($terms, $term)) {
                    continue;
                } else {
                    $this->sortTermsHierarchically($terms, $termsHierarchy, $term->parent);
                }
            }
        }

        $args = isset($field['args']) ? $field['args'] : array();

        return $this->categoryObjectsToString($termsHierarchy, null, $args);
    }

    protected function sortTermsHierarchically(&$cats, &$into, $parentId = 0)
    {
        foreach ($cats as $i => $cat) {
            if ($cat->parent == $parentId) {
                $into[$cat->term_id] = $cat;
                unset($cats[$i]);
            }
        }

        foreach ($into as $topCat) {
            $topCat->childrens = array();
            $this->sortTermsHierarchically($cats, $topCat->childrens, $topCat->term_id);
        }
    }

    protected function termHaveParent($terms, $term)
    {
        foreach ($terms as $theTerm) {
            if ($term->parent === $theTerm->term_id) {
                return true;
            }
        }

        return false;
    }

    protected function getTaxonomy($post, $field)
    {
        if ('product' === $post->post_type) {
            return $this->termsObjectsToString(get_the_terms($post, $field['value']));
        }

        if ('product_variation' === $post->post_type) {
            return $this->termsObjectsToString(get_the_terms($post->post_parent, $field['value'])); 
        }
    }

    protected function getProdListHtml($post)
    {
        $prodListHtml = '';
        $orderProducts = $this->getOrderItemsProducts(array($post->ID));

        foreach ($orderProducts as $product) {
            $productHtml = $this->prodListTemplate;
            foreach ($this->prodListShortcodesArgs as $shortCode => $args) {
                $args['qty'] = $product->productInOrderQty;
                $productHtml = str_replace($shortCode, $this->getShortcodeFieldValue($product, $args), $productHtml);
            }

            $prodListHtml .= $productHtml;
        }

        return $prodListHtml;
    }

    protected function getTieredPriceListHtml($post)
    {
        $resultHtml = '';
        $product = wc_get_product( $post->ID );
        $product_id = $post->ID;

        $priceRules = PriceManager::getPriceRules($product_id);
        if (empty($priceRules) && 'product_variation' === $post->post_type) {
            $product_id = $post->post_parent;
            $priceRules = PriceManager::getPriceRules($product_id);
            $product = wc_get_product($product_id);
        }

        $pricingType = PriceManager::getPricingType($product_id);
        if (
            empty($priceRules)
            || 'fixed' !== $pricingType
        ) {
            return $resultHtml;
        }

        $realPrice   = $product->get_price();
        $minimum      = PriceManager::getProductQtyMin($product_id, 'view');

        if (1 >= array_keys($priceRules)[0] - $minimum) {
            $currentRangeAmount = $minimum;
        } else {
            $currentRangeAmount = $minimum . ' - ' . (array_keys($priceRules)[0] - 1);
        }
        $currentRangePrice = $realPrice;

        $resultHtml .= $this->getTieredPriceRangeHtml($currentRangeAmount, $currentRangePrice);

        $iterator = new \ArrayIterator($priceRules);

        while ($iterator->valid()) {
            $currentRangePrice = $iterator->current();
            $current_quantity = $iterator->key();

            $iterator->next();

            if ($iterator->valid()) {
                $quantity = $current_quantity;
                $currentRangeAmount = $quantity;

                if (intval($iterator->key() - 1 != $current_quantity)) {
                    $currentRangeAmount = number_format_i18n($quantity) . ' - ' . number_format_i18n(intval($iterator->key() - 1));
                }
            } else {
                $currentRangeAmount = number_format_i18n($current_quantity) . '+';
            }

            $resultHtml .= $this->getTieredPriceRangeHtml($currentRangeAmount, $currentRangePrice);
        }

        return $resultHtml;
    }

    protected function getTieredPriceRangeHtml($currentRangeAmount, $currentRangePrice)
    {
        $priceRangeHtml = $this->tieredPriceTemplate;
        foreach ($this->tieredPriceShortcodesArgs as $shortCode => $args) {
            if (false !== strpos($shortCode, '[qty-range-amount')) {
                $priceRangeHtml = str_replace($shortCode, $currentRangeAmount, $priceRangeHtml);
            } elseif (false !== strpos($shortCode, '[qty-range-price')) {
                $field = array('args' => $args);
                $priceRangeHtml = str_replace($shortCode, $this->getValueWithCurrency($currentRangePrice, $field), $priceRangeHtml);
            }
        }

        return $priceRangeHtml;
    }

    protected function getOrderTotalItems($post)
    {
        $order = $this->getOrder($post);

        if (!empty($order)) {
            $totalQuantity = 0;
            foreach ($order->get_items() as $item) {
                $totalQuantity += $item->get_quantity();
            }
        } else {
            $totalQuantity = '';
        }

        return $totalQuantity;
    }

    protected function getOrderId($post)
    {
        $order = $this->getOrder($post);
        return !empty($order) ? $order->get_id() : '';
    }

    protected function getOrderProductQty($post)
    {
        if (
            ('order-products' === $this->type
                || ('orders' === $this->type && in_array($post->post_type, array('product', 'product_variation')))
            )
            && isset($post->orderId)
        ) {
            return $post->productInOrderQty;
        } else {
            return '';
        }
    }

    protected function getOrderCreateDate($post, $field)
    {
        $format = isset($field['args']['format']) ? $field['args']['format'] : get_option('date_format');

        if ('shop_order' === $post->post_type || 'shop_order_placehold' === $post->post_type) {
            $order = $this->getOrder($post);
            return !empty($order) ? wp_date($format, strtotime($order->get_date_created())) : '';
        } elseif (
            ('order-products' === $this->type
                || ('orders' === $this->type && in_array($post->post_type, array('product', 'product_variation')))
            )
            && isset($post->orderId)
        ) {
            $order = wc_get_order($post->orderId);
            return !empty($order) ? wp_date($format, strtotime($order->get_date_created())) : '';

        } else {
            return '';
        }
    }

    protected function getOrderCompleteDate($post, $field)
    {
        $format = isset($field['args']['format']) ? $field['args']['format'] : get_option('date_format');

        if ('shop_order' === $post->post_type || 'shop_order_placehold' === $post->post_type) {
            $order = $this->getOrder($post);
            return !empty($order) && !empty($order->get_date_completed())
                ? wp_date($format, strtotime($order->get_date_completed()))
                : '';


        } elseif (
            ('order-products' === $this->type
                || ('orders' === $this->type && in_array($post->post_type, array('product', 'product_variation')))
            )
            && isset($post->orderId)
        ) {
            $order = wc_get_order($post->orderId);
            return !empty($order) && !empty($order->get_date_completed())
                ? wp_date($format, strtotime($order->get_date_completed()))
                : '';

        } else {
            return '';
        }
    }

    protected function getProductDescription($post, $field)
    {

        if ('product_variation' === $post->post_type) {
            $postContent = get_post_meta($post->ID, '_variation_description', true);

            if (empty($postContent) && !empty($post->post_parent)) {
                $postContent = get_post_field('post_content', $post->post_parent);
            }
        } else {
            $postContent = get_post_field('post_content', $post->ID);
        }

        if (
            !isset($field['args']['postcontent'])
            || 'false' !== $field['args']['postcontent']
        ) {
            $postContent = apply_filters('the_content', $postContent);
        }

        return $postContent;
    }

    protected function getMainProductDescription($post, $field)
    {
        $postContent = 'product_variation' === $post->post_type && !empty($post->post_parent)
            ? get_post_field('post_content', $post->post_parent)
            : get_post_field('post_content', $post->ID);

        if (
            !isset($field['args']['postcontent'])
            || 'false' !== $field['args']['postcontent']
        ) {
            $postContent = apply_filters('the_content', $postContent);
        }

        return $postContent;
    }

    protected function getProductName($post)
    {
        return isset($post->post_title) ? $post->post_title : '';
    }

    protected function getMainProductName($post)
    {
        if (!empty($post->post_parent)) {
            $post = get_post($post->post_parent);
        }

        return isset($post->post_title) ? $post->post_title : '';
    }

    protected function getOrderTax($post)
    {

        $order = $this->getOrder($post);

        return !empty($order)
            ? $this->getValueWithCurrency($order->get_total_tax())
            : '';
    }

    protected function getOrderShippingMethod($post)
    {
        $order = $this->getOrder($post);

        if (!empty($order)) {
            $orderShippingMethods = array();
            foreach ($order->get_items('shipping') as $shippingItem) {



                $orderShippingMethods[] = $shippingItem->get_method_title();
            }

            $result = !empty($orderShippingMethods) ? implode(', ', $orderShippingMethods) : '';
        } else {
            $result = '';
        }

        return $result;
    }


    protected function getOrderCustomerProvidedNote($post)
    {
        $order = $this->getOrder($post);

        return !empty($order) ? $order->get_customer_note() : '';
    }


    protected function getItemsSubtotal($post)
    {
        if ('shop_order' !== $post->post_type && 'shop_order_placehold' !== $post->post_type) {
            $order = $this->getOrder($post);
        }

        return !empty($order)
            ? $this->getValueWithCurrency($order->get_subtotal())
            : '';
    }

    protected function getOrderShipping($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $this->getValueWithCurrency($order->get_shipping_total())
            : '';
    }

    protected function getOrderCartDiscount($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $this->getValueWithCurrency($order->get_discount_total())
            : '';
    }

    protected function getOrderShippingFirstName($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_shipping_first_name()
            : '';
    }

    protected function getOrderShippingLastName($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_shipping_last_name()
            : '';
    }

    protected function getOrderShippingCity($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_shipping_city()
            : '';
    }

    protected function getOrderShippingAddress1($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_shipping_address_1()
            : '';
    }

    protected function getOrderShippingAddress2($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_shipping_address_2()
            : '';
    }

    protected function getOrderShippingPostcode($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_shipping_postcode()
            : '';
    }

    protected function getOrderShippingPhone($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_shipping_phone()
            : '';
    }

    protected function getOrderShippingCountry($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_shipping_country()
            : '';
    }

    protected function getOrderShippingState($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_shipping_state()
            : '';
    }

    protected function getOrderShippingCompany($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_shipping_company()
            : '';
    }

    protected function getOrderShippingCountryFullName($post)
    {
        $order = $this->getOrder($post);
        if (empty($order)) {
            return '';
        }
        $countries = apply_filters('woocommerce_countries', include WC()->plugin_path() . '/i18n/countries.php');
        $countryCode = $order->get_shipping_country();
        $countryFullName = (is_array($countries) && isset($countries[$countryCode])) ? $countries[$countryCode] : $countryCode;

        return preg_replace("/\s\(.*\)/", '', $countryFullName);
    }

    protected function getOrderShippingStateFullName($post)
    {
        $order = $this->getOrder($post);
        if (empty($order)) {
            return '';
        }
        $states = apply_filters('woocommerce_countries', include WC()->plugin_path() . '/i18n/states.php');
        $countryCode = $order->get_shipping_country();
        $stateCode = $order->get_shipping_state();

        return (is_array($states) && isset($states[$countryCode][$stateCode])) ? $states[$countryCode][$stateCode] : $stateCode;
    }

    protected function getOrderBillingFirstName($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_billing_first_name()
            : '';
    }

    protected function getOrderBillingLastName($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_billing_last_name()
            : '';
    }

    protected function getOrderBillingCity($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_billing_city()
            : '';
    }

    protected function getOrderBillingAddress1($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_billing_address_1()
            : '';
    }

    protected function getOrderBillingAddress2($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_billing_address_2()
            : '';
    }

    protected function getOrderBillingPostcode($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_billing_postcode()
            : '';
    }

    protected function getOrderBillingPhone($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_billing_phone()
            : '';
    }

    protected function getOrderBillingEmail($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_billing_email()
            : '';
    }

    protected function getOrderBillingCountry($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_billing_country()
            : '';
    }

    protected function getOrderBillingState($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_billing_state()
            : '';
    }

    protected function getOrderBillingCompany($post)
    {
        $order = $this->getOrder($post);

        return !empty($order)
            ? $order->get_billing_company()
            : '';
    }

    protected function getOrderBillingCountryFullName($post)
    {
        $order = $this->getOrder($post);
        if (empty($order)) {
            return '';
        }
        $countries = apply_filters('woocommerce_countries', include WC()->plugin_path() . '/i18n/countries.php');
        $countryCode = $order->get_billing_country();
        $countryFullName = (is_array($countries) && isset($countries[$countryCode])) ? $countries[$countryCode] : $countryCode;

        return preg_replace("/\s\(.*\)/", '', $countryFullName);
    }

    protected function getOrderBillingStateFullName($post)
    {
        $order = $this->getOrder($post);
        if (empty($order)) {
            return '';
        }
        $states = apply_filters('woocommerce_countries', include WC()->plugin_path() . '/i18n/states.php');
        $countryCode = $order->get_billing_country();
        $stateCode = $order->get_billing_state();

        return (is_array($states) && isset($states[$countryCode][$stateCode])) ? $states[$countryCode][$stateCode] : $stateCode;
    }

    protected function getOrderPostField($post, $field)
    {
        $order = $this->getOrder($post);
        if (empty($order)) {
            return '';
        }

        if (isset($this->orderPostFieldToOrderWcMethodMap[$field['value']]) && method_exists($this, $this->orderPostFieldToOrderWcMethodMap[$field['value']])) {
            $result = $this->{$this->orderPostFieldToOrderWcMethodMap[$field['value']]}($order, $field);
        } else {
            $result = '';
        }

        return $result;
    }

    protected function getOrderPostTitle($order, $field)
    {
        return 'Order' . ' &ndash; ' . date_i18n('F j, Y @ h:i A', strtotime($order->get_date_created()));
    }

    protected function getOrder($post)
    {
        if ('shop_order' === $post->post_type || 'shop_order_placehold' === $post->post_type) {
            $order = wc_get_order($post->ID);
        } elseif (
            ('order-products' === $this->type
                || ('orders' === $this->type && in_array($post->post_type, array('product', 'product_variation')))
            )
            && isset($post->orderId)
        ) {
            $order = wc_get_order($post->orderId);
        } else {
            $order = null;
        }

        return $order;
    }

    protected function getYoastGlobalIdentifierValue($post, $field, $identifierType)
    {
        $result = '';

        $yoastGlobalIdentifiersValues = 'product_variation' === $post->post_type
            ? get_post_meta($post->ID, 'wpseo_variation_global_identifiers_values', true)
            : get_post_meta($post->ID, 'wpseo_global_identifier_values', true);

        if (
            !empty($yoastGlobalIdentifiersValues[$identifierType])
            && (
                is_string($yoastGlobalIdentifiersValues[$identifierType])
                || is_numeric($yoastGlobalIdentifiersValues[$identifierType])
            )
        ) {
            $result = $yoastGlobalIdentifiersValues[$identifierType];
        }

        return $result;
    }

    protected function wooBookingItemBookingDate($post, $field)
    {
        if (!property_exists($post, 'orderItem')) {
            return '';
        }

        global $wpdb;
        $orderItemId = $post->orderItem->get_id();
        $format = isset($field['args']['format']) ? $field['args']['format'] : 'F j, Y, h:i A';

        $row = $wpdb->get_row(
            $wpdb->prepare("select * from " . $wpdb->prefix . "postmeta as pm where pm.meta_key = '_booking_order_item_id' and pm.meta_value = '" . $orderItemId . "';")
        );

        if($row) {
            $data = $wpdb->get_row("select * from " . $wpdb->prefix . "postmeta as pm where pm.meta_key = '_booking_persons' and pm.post_id = '" . $row->post_id . "';");
            $booking_start = $data ? get_post_meta($row->post_id, "_booking_start", true) : "";

            if($booking_start) {
                $dt = \DateTime::createFromFormat('YmdHis', $booking_start);
                return $dt ? $dt->format($format) : '';
            }
        }

        return '';
    }

    protected function getOrderItemConsequentialNumber($post, $field)
    {
        return property_exists($post, 'orderItemConsequentialNumber')
            ? $post->orderItemConsequentialNumber
            : '';
    }

    protected function getDokanVendorName($post, $field)
    {
        if (!property_exists($post, 'post_author')) {
            return '';
        }

        $dokanVendorName = get_user_meta($post->post_author, 'dokan_store_name', true);

        return !empty($dokanVendorName) ? $dokanVendorName : '';
    }
}

<?php

namespace UkrSolution\ProductLabelsPrinting;

use DateTime;
use UkrSolution\ProductLabelsPrinting\Helpers\Variables;

class PostFilters
{
    public function __construct()
    {
        if (is_admin()) {
            add_action('restrict_manage_posts', array($this, "restrict_manage_posts"));
            add_filter('the_posts', array($this, "the_posts"));
        }

    }

    public function restrict_manage_posts()
    {
        try {
            global $wpdb, $table_prefix;

            $postType = (isset($_GET['post_type'])) ? sanitize_text_field($_GET['post_type']) : 'post';

            switch ($postType) {
                case 'shop_order':
                    $this->ordersPage();
                    break;
                default:
                    break;
            }
        } catch (\Throwable $th) {
        }
    }


    public function the_posts($posts)
    {
        global $pagenow;

        $postType = (isset($_GET['post_type'])) ? sanitize_text_field($_GET['post_type']) : 'post';

        if ($postType !== 'shop_order' || $pagenow !== 'edit.php') {
            return $posts;
        }

        try {
            $dateFrom = isset($_GET['order_date']) && !empty($_GET['order_date']) ? sanitize_text_field($_GET['order_date']) : null;
            $dateTo = isset($_GET['order_date_to']) && !empty($_GET['order_date_to']) ? sanitize_text_field($_GET['order_date_to']) : null;

            if ($posts && ($dateFrom || $dateTo)) {
                $orders = array();

                $dtFrom = $dateFrom ? new DateTime($dateFrom . " 00:00:00") : null;
                $dtTo = $dateTo ? new DateTime($dateTo . " 23:59:59") : null;

                foreach ($posts as $post) {
                    $postDt = new DateTime($post->post_date);

                    if ($dtFrom && $dtTo) {
                        if ($dtFrom < $postDt && $dtTo > $postDt) {
                            $orders[] = $post;
                        }
                    } else if ($dateFrom) {
                        $date = explode(" ", $post->post_date);

                        if ($date[0] === $dateFrom) {
                            $orders[] = $post;
                        }
                    } else if ($dateTo) {
                        $date = explode(" ", $post->post_date);

                        if ($date[0] === $dateTo) {
                            $orders[] = $post;
                        }
                    }
                }

                $posts = $orders;
                unset($orders);
            }
        } catch (\Throwable $th) {
        }

        try {
            if ($posts && isset($_GET['order_product_category']) && !empty($_GET['order_product_category'])) {
                $orders = array();
                $category = sanitize_text_field($_GET['order_product_category']);

                foreach ($posts as $post) {
                    $isOrderDisplay = false;
                    $order = \wc_get_order($post->ID);

                    foreach ($order->get_items() as $item) {
                        $terms = get_the_terms($item->get_product_id(), 'product_cat');

                        foreach ($terms as $term) {
                            if ($term->slug === $category) {
                                $isOrderDisplay = true;
                            }
                        }
                    }

                    if ($isOrderDisplay) {
                        $orders[] = $post;
                    }
                }

                $posts = $orders;
                unset($orders);
            }
        } catch (\Throwable $th) {
        }

        return $posts;
    }


    private function ordersPage()
    {
        $this->ordersPageOrderDate();
        $this->ordersPageProductCategories();
    }


    private function ordersPageOrderDate()
    {
        $value = (isset($_GET['order_date'])) ? sanitize_text_field($_GET['order_date']) : '';
        $valueTo = (isset($_GET['order_date_to'])) ? sanitize_text_field($_GET['order_date_to']) : '';

        include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/filters/orders-order-date.php';
    }


    private function ordersPageProductCategories()
    {
        $values = array();

        $taxonomy = 'product_cat';
        $orderby = 'name';
        $show_count = 0;
        $pad_counts = 0;
        $hierarchical = 1;
        $title = '';
        $empty = 0;

        $args = array(
            'taxonomy' => $taxonomy,
            'orderby' => $orderby,
            'show_count' => $show_count,
            'pad_counts' => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li' => $title,
            'hide_empty' => $empty
        );
        $allCategories = get_categories($args);

        foreach ($allCategories as $cat) {
            if ($cat->category_parent == 0) {
                $category_id = $cat->term_id;
                $values[$cat->slug] = $cat->name;

                $args2 = array(
                    'taxonomy' => $taxonomy,
                    'child_of' => 0,
                    'parent' => $category_id,
                    'orderby' => $orderby,
                    'show_count' => $show_count,
                    'pad_counts' => $pad_counts,
                    'hierarchical' => $hierarchical,
                    'title_li' => $title,
                    'hide_empty' => $empty
                );
                $subCats = get_categories($args2);

                if ($subCats) {
                    foreach ($subCats as $subCategory) {
                        $values[$subCategory->slug] = "&nbsp;&nbsp;&nbsp;" . $subCategory->name;
                    }
                }
            }
        }

        include Variables::$A4B_PLUGIN_BASE_PATH . 'templates/filters/orders-product-categories.php';
    }

}

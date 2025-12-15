<?php
/*
Plugin Name: AAA Orders and Products Menu (XHV98-ADMIN)
Description: Adds top-level Orders and Products menus with status-specific submenus and individual bubble counts. Includes a settings page to add more static menu items.
Version: 2.4
Author: webmaster
Author URI: https://magelifesaver.com
*/

// Ensure the plugin runs only in the admin area
if (!is_admin()) {
    return;
}

/**
 * Initialize the plugin by setting up menus and settings.
 */
function aaa_initialize_menus() {
    // Add Orders and Products menus
    add_action('admin_menu', 'aaa_custom_orders_top_menu');
    add_action('admin_menu', 'aaa_custom_products_top_menu');
    
    // Add settings menu under Settings
    add_action('admin_menu', 'aaa_add_settings_page');
    
    // Register settings
    add_action('admin_init', 'aaa_register_settings');
    
    // Enqueue scripts and styles for settings page
    add_action('admin_enqueue_scripts', 'aaa_enqueue_admin_scripts');
}
add_action('init', 'aaa_initialize_menus');

/**
 * Enqueue admin scripts and styles for settings page.
 */
function aaa_enqueue_admin_scripts($hook) {
    // Check if we're on the settings page
    if ($hook !== 'settings_page_aaa-menu-settings') {
        return;
    }
    // Enqueue CSS
    wp_enqueue_style('aaa-admin-css', plugin_dir_url(__FILE__) . 'css/admin-style.css');
    // Enqueue JS
    wp_enqueue_script('aaa-admin-js', plugin_dir_url(__FILE__) . 'js/admin-script.js', array('jquery'), '1.0', true);
}

/**
 * Add Orders top-level menu with submenus.
 */
function aaa_custom_orders_top_menu() {
    // Add the main Orders menu
    add_menu_page(
        __('Orders', 'woocommerce'),                // Page title
        __('Orders', 'woocommerce'),                // Menu title
        'manage_woocommerce',                       // Capability
        'edit.php?post_type=shop_order',            // Menu slug (points to Orders list)
        '',                                         // Callback function (none needed)
        'dashicons-cart',                           // Icon
        54                                          // Position above WooCommerce menu
    );

    // 1) All Orders
    add_submenu_page(
        'edit.php?post_type=shop_order',            
        __('All Orders', 'woocommerce'),            
        aaa_get_order_menu_label('All Orders', ''), 
        'manage_woocommerce',
        'edit.php?post_type=shop_order'
    );

    // 1a) Today's Orders (All statuses, from midnight today)
    add_submenu_page(
        'edit.php?post_type=shop_order',            
        __("Today's Orders", 'woocommerce'),        
        __("Today's Orders", 'woocommerce'),        
        'manage_woocommerce',
        'edit.php?post_type=shop_order&todays_all=1' // Custom query var
    );

    // 2) Pending Orders
    add_submenu_page(
        'edit.php?post_type=shop_order',
        __('Pending Orders', 'woocommerce'),
        aaa_get_order_menu_label('Pending Orders', 'wc-pending'),
        'manage_woocommerce',
        'edit.php?post_type=shop_order&post_status=wc-pending'
    );

    // 2a) Today's Pending
    add_submenu_page(
        'edit.php?post_type=shop_order',
        __("Today's Pending", 'woocommerce'),
        __("Today's Pending", 'woocommerce'),
        'manage_woocommerce',
        'edit.php?post_type=shop_order&todays_pending=1'
    );

    // 3) Processing Orders
    add_submenu_page(
        'edit.php?post_type=shop_order',
        __('Processing Orders', 'woocommerce'),
        aaa_get_order_menu_label('Processing Orders', 'wc-processing'),
        'manage_woocommerce',
        'edit.php?post_type=shop_order&post_status=wc-processing'
    );

    // 3a) Today's Processing
    add_submenu_page(
        'edit.php?post_type=shop_order',
        __("Today's Processing", 'woocommerce'),
        __("Today's Processing", 'woocommerce'),
        'manage_woocommerce',
        'edit.php?post_type=shop_order&todays_processing=1'
    );

    // 4) Completed Orders
    add_submenu_page(
        'edit.php?post_type=shop_order',
        __('Completed Orders', 'woocommerce'),
        aaa_get_order_menu_label('Completed Orders', 'wc-completed'),
        'manage_woocommerce',
        'edit.php?post_type=shop_order&post_status=wc-completed'
    );

    // 4a) Today's Completed
    add_submenu_page(
        'edit.php?post_type=shop_order',
        __("Today's Completed", 'woocommerce'),
        __("Today's Completed", 'woocommerce'),
        'manage_woocommerce',
        'edit.php?post_type=shop_order&todays_completed=1'
    );

    // 4b) Yesterday's Completed
    add_submenu_page(
        'edit.php?post_type=shop_order',
        __("Yesterday's Completed", 'woocommerce'),
        __("Yesterday's Completed", 'woocommerce'),
        'manage_woocommerce',
        'edit.php?post_type=shop_order&yesterdays_completed=1'
    );

    // Additional static menu items from settings
    $additional_orders = get_option('aaa_additional_orders_menus', array());
    if (!empty($additional_orders) && is_array($additional_orders)) {
        foreach ($additional_orders as $menu) {
            add_submenu_page(
                'edit.php?post_type=shop_order',
                __($menu['title'], 'woocommerce'),
                __($menu['title'], 'woocommerce'),
                'manage_woocommerce',
                esc_url_raw($menu['url'])
            );
        }
    }

    // Update Main Orders Bubble Count
    add_action('admin_menu', 'aaa_update_main_orders_bubble', 99);
}

/**
 * Add Products top-level menu with one submenu.
 */
function aaa_custom_products_top_menu() {
    // Top-level menu links to Published Products
    add_menu_page(
        __('Inventory', 'woocommerce'),
        __('Inventory', 'woocommerce'),
        'manage_woocommerce',
        'edit.php?post_type=product&post_status=publish', // Slug = Published Products
        '',
        'dashicons-products',
        55
    );

    // First submenu also links to Published Products
    add_submenu_page(
        'edit.php?post_type=product&post_status=publish', 
        __('Published Products', 'woocommerce'),
        __('Published Products', 'woocommerce'),
        'manage_woocommerce',
        'edit.php?post_type=product&post_status=publish'
    );

    // Additional static submenu items from your settings
    $additional_products = get_option('aaa_additional_products_menus', array());
    if (!empty($additional_products) && is_array($additional_products)) {
        foreach ($additional_products as $menu) {
            add_submenu_page(
                'edit.php?post_type=product&post_status=publish',
                __($menu['title'], 'woocommerce'),
                __($menu['title'], 'woocommerce'),
                'manage_woocommerce',
                esc_url_raw($menu['url'])
            );
        }
    }
}

/**
 * Redirect the Products top-level menu to Published Products.
 */
function aaa_redirect_to_published_products() {
    // Redirect to Published Products
    wp_redirect(admin_url('edit.php?post_type=product&post_status=publish'));
    exit;
}

/**
 * Generate Menu Label with Bubble Count for Orders.
 */
function aaa_get_order_menu_label($label, $status) {
    $count = aaa_get_order_count_by_status($status);
    $bubble = $count > 0 ? "<span class='update-plugins count-$count'><span class='update-count'>$count</span></span>" : '';
    return sprintf('%s %s', $label, $bubble);
}

/**
 * Fetch Order Count by Status (Safe Query).
 */
function aaa_get_order_count_by_status($status = '') {
    if (empty($status)) {
        $args = [
            'type'       => 'shop_order',
            'limit'      => -1,
            'return'     => 'ids'
        ];
    } else {
        $args = [
            'type'       => 'shop_order',
            'status'     => $status,
            'limit'      => -1,
            'return'     => 'ids'
        ];
    }

    $orders = wc_get_orders($args);

    return is_array($orders) ? count($orders) : 0;
}

/**
 * Update Main Orders Bubble Count.
 */
function aaa_update_main_orders_bubble() {
    global $menu;

    // Safe query for processing + on-hold orders
    $processing_count = aaa_get_order_count_by_status('wc-processing');
    $on_hold_count = aaa_get_order_count_by_status('wc-on-hold');
    $order_count = $processing_count + $on_hold_count;

    foreach ($menu as &$menu_item) {
        if ($menu_item[2] === 'edit.php?post_type=shop_order') {
            $menu_item[0] = sprintf(
                __('Orders %s', 'woocommerce'),
                $order_count > 0 ? "<span class='update-plugins count-$order_count'><span class='update-count'>$order_count</span></span>" : ''
            );
            break;
        }
    }
}

/**
 * Add Settings Page under Settings Menu.
 */
function aaa_add_settings_page() {
    add_options_page(
        __('AAA Menu Settings', 'woocommerce'),    // Page title
        __('AAA Menu Settings', 'woocommerce'),    // Menu title
        'manage_options',                           // Capability
        'aaa-menu-settings',                        // Menu slug
        'aaa_render_settings_page'                  // Callback function
    );
}

/**
 * Render the Settings Page.
 */
function aaa_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('AAA Menu Settings', 'woocommerce'); ?></h1>
        
        <h2><?php _e('Additional Orders Menu Items', 'woocommerce'); ?></h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('aaa_orders_menus_group');
            do_settings_sections('aaa-orders-settings');
            submit_button();
            ?>
        </form>

        <h2><?php _e('Additional Products Menu Items', 'woocommerce'); ?></h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('aaa_products_menus_group');
            do_settings_sections('aaa-products-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register Settings for Additional Orders and Products Menu Items.
 */
function aaa_register_settings() {
    // Orders Menu Settings
    register_setting('aaa_orders_menus_group', 'aaa_additional_orders_menus', 'aaa_sanitize_menu_items');
    
    add_settings_section(
        'aaa_orders_menus_section',
        __('Manage Additional Orders Menu Items', 'woocommerce'),
        'aaa_orders_menus_section_callback',
        'aaa-orders-settings'
    );

    add_settings_field(
        'aaa_additional_orders_menus',
        __('Orders Menu Items', 'woocommerce'),
        'aaa_orders_menus_field_callback',
        'aaa-orders-settings',
        'aaa_orders_menus_section'
    );

    // Products Menu Settings
    register_setting('aaa_products_menus_group', 'aaa_additional_products_menus', 'aaa_sanitize_menu_items');
    
    add_settings_section(
        'aaa_products_menus_section',
        __('Manage Additional Products Menu Items', 'woocommerce'),
        'aaa_products_menus_section_callback',
        'aaa-products-settings'
    );

    add_settings_field(
        'aaa_additional_products_menus',
        __('Products Menu Items', 'woocommerce'),
        'aaa_products_menus_field_callback',
        'aaa-products-settings',
        'aaa_products_menus_section'
    );
}
add_action('admin_init', 'aaa_register_settings');

/**
 * Callback for Orders Menus Section.
 */
function aaa_orders_menus_section_callback() {
    echo __('Add, edit, or remove static menu items under the Orders menu.', 'woocommerce');
}

/**
 * Callback for Products Menus Section.
 */
function aaa_products_menus_section_callback() {
    echo __('Add, edit, or remove static menu items under the Products menu.', 'woocommerce');
}

/**
 * Render Orders Menus Field.
 */
function aaa_orders_menus_field_callback() {
    $menus = get_option('aaa_additional_orders_menus', array());
    ?>
    <div id="aaa-orders-menus-wrapper">
        <?php if (!empty($menus)) : ?>
            <?php foreach ($menus as $index => $menu) : ?>
                <div class="aaa-menu-item">
                    <input type="text" name="aaa_additional_orders_menus[<?php echo $index; ?>][title]" value="<?php echo esc_attr($menu['title']); ?>" placeholder="<?php esc_attr_e('Menu Title', 'woocommerce'); ?>" required />
                    <input type="url" name="aaa_additional_orders_menus[<?php echo $index; ?>][url]" value="<?php echo esc_attr($menu['url']); ?>" placeholder="<?php esc_attr_e('Menu URL', 'woocommerce'); ?>" required />
                    <button type="button" class="button aaa-remove-menu"><?php _e('Remove', 'woocommerce'); ?></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <button type="button" class="button" id="aaa-add-orders-menu"><?php _e('Add Menu Item', 'woocommerce'); ?></button>
    <?php
}

/**
 * Render Products Menus Field.
 */
function aaa_products_menus_field_callback() {
    $menus = get_option('aaa_additional_products_menus', array());
    ?>
    <div id="aaa-products-menus-wrapper">
        <?php if (!empty($menus)) : ?>
            <?php foreach ($menus as $index => $menu) : ?>
                <div class="aaa-menu-item">
                    <input type="text" name="aaa_additional_products_menus[<?php echo $index; ?>][title]" value="<?php echo esc_attr($menu['title']); ?>" placeholder="<?php esc_attr_e('Menu Title', 'woocommerce'); ?>" required />
                    <input type="url" name="aaa_additional_products_menus[<?php echo $index; ?>][url]" value="<?php echo esc_attr($menu['url']); ?>" placeholder="<?php esc_attr_e('Menu URL', 'woocommerce'); ?>" required />
                    <button type="button" class="button aaa-remove-menu"><?php _e('Remove', 'woocommerce'); ?></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <button type="button" class="button" id="aaa-add-products-menu"><?php _e('Add Menu Item', 'woocommerce'); ?></button>
    <?php
}

/**
 * Sanitize Menu Items Input.
 */
function aaa_sanitize_menu_items($input) {
    if (!is_array($input)) {
        return array();
    }

    $sanitized = array();
    foreach ($input as $menu) {
        if (isset($menu['title']) && isset($menu['url'])) {
            $title = sanitize_text_field($menu['title']);
            $url = esc_url_raw($menu['url']);
            if (!empty($title) && !empty($url)) {
                $sanitized[] = array(
                    'title' => $title,
                    'url'   => $url
                );
            }
        }
    }
    return $sanitized;
}

/**
 * Load the Settings Page Scripts (JavaScript for adding/removing menu items).
 */
function aaa_load_settings_scripts() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Add Orders Menu Item
            $('#aaa-add-orders-menu').on('click', function(e) {
                e.preventDefault();
                var wrapper = $('#aaa-orders-menus-wrapper');
                var index = wrapper.find('.aaa-menu-item').length;
                var newItem = `
                    <div class="aaa-menu-item">
                        <input type="text" name="aaa_additional_orders_menus[` + index + `][title]" value="" placeholder="<?php echo esc_js(__('Menu Title', 'woocommerce')); ?>" required />
                        <input type="url" name="aaa_additional_orders_menus[` + index + `][url]" value="" placeholder="<?php echo esc_js(__('Menu URL', 'woocommerce')); ?>" required />
                        <button type="button" class="button aaa-remove-menu"><?php echo esc_js(__('Remove', 'woocommerce')); ?></button>
                    </div>
                `;
                wrapper.append(newItem);
            });

            // Add Products Menu Item
            $('#aaa-add-products-menu').on('click', function(e) {
                e.preventDefault();
                var wrapper = $('#aaa-products-menus-wrapper');
                var index = wrapper.find('.aaa-menu-item').length;
                var newItem = `
                    <div class="aaa-menu-item">
                        <input type="text" name="aaa_additional_products_menus[` + index + `][title]" value="" placeholder="<?php echo esc_js(__('Menu Title', 'woocommerce')); ?>" required />
                        <input type="url" name="aaa_additional_products_menus[` + index + `][url]" value="" placeholder="<?php echo esc_js(__('Menu URL', 'woocommerce')); ?>" required />
                        <button type="button" class="button aaa-remove-menu"><?php echo esc_js(__('Remove', 'woocommerce')); ?></button>
                    </div>
                `;
                wrapper.append(newItem);
            });

            // Remove Menu Item
            $(document).on('click', '.aaa-remove-menu', function(e) {
                e.preventDefault();
                $(this).parent('.aaa-menu-item').remove();
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'aaa_load_settings_scripts');

/**
 * Render the Settings Page Scripts (JavaScript).
 */
function aaa_render_settings_page_scripts() {
    // This function can be used if you prefer to move the JavaScript to an external file.
    // Currently, JavaScript is embedded directly within the PHP using aaa_load_settings_scripts.
}
add_action('admin_enqueue_scripts', 'aaa_render_settings_page_scripts');

/**
 * -- CUSTOM HOOKS FOR "TODAY'S" or "YESTERDAY'S" ORDERS --
 * We allow multiple custom query vars and handle them in pre_get_posts.
 */
add_filter('query_vars', function($vars) {
    // Add custom query vars for our today/yesterday links
    $vars[] = 'todays_all';          // All statuses (today)
    $vars[] = 'todays_pending';      // Pending (today)
    $vars[] = 'todays_processing';   // Processing (today)
    $vars[] = 'todays_completed';    // Completed (today)
    $vars[] = 'yesterdays_completed';// Completed (yesterday)
    return $vars;
});

add_action('pre_get_posts', function($query) {
    // Only modify the main query in the admin area
    if ( !is_admin() || !$query->is_main_query() ) {
        return;
    }

    // Ensure we’re on the Orders screen
    if ( isset($_GET['post_type']) && 'shop_order' === $_GET['post_type'] ) {

        // 1) Today's ALL Orders (all statuses)
        if ( isset($_GET['todays_all']) && '1' === $_GET['todays_all'] ) {
            $today = current_time('Y-m-d');
            $query->set('date_query', array(
                array(
                    'after'     => $today . ' 00:00:00',
                    'before'    => $today . ' 23:59:59',
                    'inclusive' => true,
                    'column'    => 'post_date',
                )
            ));
        }

        // 2) Today's Pending Orders
        if ( isset($_GET['todays_pending']) && '1' === $_GET['todays_pending'] ) {
            $today = current_time('Y-m-d');
            $query->set('post_status', 'wc-pending');
            $query->set('date_query', array(
                array(
                    'after'     => $today . ' 00:00:00',
                    'before'    => $today . ' 23:59:59',
                    'inclusive' => true,
                    'column'    => 'post_date',
                )
            ));
        }

        // 3) Today's Processing Orders
        if ( isset($_GET['todays_processing']) && '1' === $_GET['todays_processing'] ) {
            $today = current_time('Y-m-d');
            $query->set('post_status', 'wc-processing');
            $query->set('date_query', array(
                array(
                    'after'     => $today . ' 00:00:00',
                    'before'    => $today . ' 23:59:59',
                    'inclusive' => true,
                    'column'    => 'post_date',
                )
            ));
        }

        // 4) Today's Completed Orders
        if ( isset($_GET['todays_completed']) && '1' === $_GET['todays_completed'] ) {
            $today = current_time('Y-m-d');
            $query->set('post_status', 'wc-completed');
            $query->set('date_query', array(
                array(
                    'after'     => $today . ' 00:00:00',
                    'before'    => $today . ' 23:59:59',
                    'inclusive' => true,
                    'column'    => 'post_date',
                )
            ));
        }

        // 5) Yesterday's Completed Orders
        if ( isset($_GET['yesterdays_completed']) && '1' === $_GET['yesterdays_completed'] ) {
            $yesterday = date('Y-m-d', strtotime(current_time('Y-m-d') . ' -1 day'));
            $query->set('post_status', 'wc-completed');
            $query->set('date_query', array(
                array(
                    'after'     => $yesterday . ' 00:00:00',
                    'before'    => $yesterday . ' 23:59:59',
                    'inclusive' => true,
                    'column'    => 'post_date',
                )
            ));
        }
    }
});

/**
 * Initialize the plugin.
 */
aaa_initialize_menus();

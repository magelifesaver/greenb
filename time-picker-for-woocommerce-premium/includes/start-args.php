<?php
if (!defined('ABSPATH')) {
    exit;
}


if (!isset($settings_args_third)) {
    $categories = array();
    foreach (TPFW::get_all_categories('all', 1) as $category) {
        $categories += array(
            esc_attr($category->cat_ID) => esc_html($category->name)
        );
    }
    $settings_args_third = array(
        array(
            'name' => __('Time to finished order', 'checkout-time-picker-for-woocommerce'),
            'type' => 'title',
            'id' => 'tpfw_preperation_times',
            'desc' => __('Calculate an approximate time for an order to be ready.', 'checkout-time-picker-for-woocommerce'),
        ),
        array(
            'name' => __('Preparation Time', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_preperation_time_mode',

            'default' => 'fixed',
            'type' => 'select',
            'options' => array(
                'fixed' => __('Fixed preparation time', 'checkout-time-picker-for-woocommerce'),
                'dynamic' => __('Dynamic preparation time', 'checkout-time-picker-for-woocommerce'),
            ),
        ),
        array(
            'name' => __('Fixed preparation time per order', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_pickup_fixed',

            'default' => 0,
            'type' => 'number',
            'desc' => __('Set a fixed time in minutes for preparing an order.', 'checkout-time-picker-for-woocommerce')
        ),
        array(
            'type' => 'sectionend',
            'id' => 'tpfw_preperation_times',
        ),
        array(
            'name' => __('Dynamic Preparation Time', 'checkout-time-picker-for-woocommerce'),
            'type' => 'title',
           
            'desc_tip' => true,
            'id' => 'tpfw_ordertime_per_cats'
        ),
        array(
            'name' => __('Preparation Time per Order or Order Item', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_preptime_order_item',

            'default' => 'order',
            'type' => 'select',
            'options' => array(
                'order' => __('Per Order', 'checkout-time-picker-for-woocommerce'),
                'item' => __('Per Order Item', 'checkout-time-picker-for-woocommerce'),
            ),
        ),
        array(
            'type' => 'tpfwfixedordertime',
            'id' => 'tpfwfixedordertime'
        ),
        array(
            'type' => 'sectionend',
            'id' => 'tpfw_ordertime_per_cats'
        ),
        array(
            'name' => __('Extra Time', 'checkout-time-picker-for-woocommerce'),
            'type' => 'title',
            'id' => 'tpfw_ordertime_extra',
            'desc' => __('Add extra time depending on already existing orders with a "processing" or "on-hold" order status.', 'checkout-time-picker-for-woocommerce')
        ),
        array(
            'name' => __('Extra time per processing order/item', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_pickup_var',

            'default' => 0,
            'type' => 'number',
            'desc' => __('Set extra time in minutes per order or item already processing.', 'checkout-time-picker-for-woocommerce')
        ),
        array(
            'name' => __('Use order or items?', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_extratime_mode',

            'default' => 'orders',
            'type' => 'select',
            'options' => array(
                'orders' => __('Orders', 'checkout-time-picker-for-woocommerce'),
                'items' => __('Items', 'checkout-time-picker-for-woocommerce'),
            ),
            'desc' => __('Choose to calculate extra time per processing order or per processing item.', 'checkout-time-picker-for-woocommerce')
        ),
        array(
            'name' => __('Choose categories to include', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_processing_cats',
            'default' => array(),
            'type' => 'multiselect',
            'class' => 'wc-enhanced-select',
            'options' => $categories,
            'css' => 'max-width:200px;height:auto;',
            'desc' => __('Select product categories to include when counting the processing items', 'checkout-time-picker-for-woocommerce')
        ),
        array(
            'type' => 'sectionend',
            'id' => 'tpfw_order_time_extra',
        ),
        array(
            'name' => __('Pickup Orders', 'checkout-time-picker-for-woocommerce'),
            'type' => 'title',
            'id' => 'tpfw_time_to_delivery_pickup',
        ),
        array(
            'name' => __('Time until ready for pickup?', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_ready_for_pickup_show',
            'type' => 'select',
            'options' => array(
                'none' => __('None', 'checkout-time-picker-for-woocommerce'),
                'fixedtime' => __('By preparation time', 'checkout-time-picker-for-woocommerce'),
                'variable' => __('By preparation time & extra time', 'checkout-time-picker-for-woocommerce')
            ),

            'desc' => __('Set the time until a pickup order is ready.', 'checkout-time-picker-for-woocommerce'),
            'default' => 'none'
        ),
        array(
            'type' => 'sectionend',
            'id' => 'tpfw_time_to_delivery_pickup',
        ),



        array(
            'name' => __('Delivery Orders', 'checkout-time-picker-for-woocommerce'),
            'type' => 'title',
            'id' => 'tpfw_time_to_delivery_del',
        ),

        array(
            'name' => __('Time until delivery?', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_ready_for_delivery_show',
            'type' => 'select',
            'options' => array(
                'none' => __('None', 'checkout-time-picker-for-woocommerce'),
                'fixedtime' => __('By preparation time', 'checkout-time-picker-for-woocommerce'),
                'variable' => __('By preparation time & extra time', 'checkout-time-picker-for-woocommerce'),
                'fixed_ship' => __('By preparation time & shipping time', 'checkout-time-picker-for-woocommerce'),
                'variable_ship' => __('By preparation time & extra time & shipping time', 'checkout-time-picker-for-woocommerce')
            ),

            'desc' => __('Set the time until a delivery order can be delivered.', 'checkout-time-picker-for-woocommerce'),
            'default' => 'none'
        ),
        array(
            'name' => __('Set shipping time', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_shipping_time',
            'type' => 'select',
            'options' => array(
                'none' => __('None', 'checkout-time-picker-for-woocommerce'),
                'fixedtime' => __('Fixed shipping time', 'checkout-time-picker-for-woocommerce'),
              
            ),

            'desc' => __('Set the time until a delivery order can be delivered.', 'checkout-time-picker-for-woocommerce'),
            'default' => 'none'
        ),
        array(
            'name' => __('Fixed shipping time', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_shipping_fixed',

            'default' => 0,
            'type' => 'number',
            'desc' => __('Set a fixed time in minutes for shipping an order.', 'checkout-time-picker-for-woocommerce')
        ),
        array(
            'type' => 'sectionend',
            'id' => 'tpfw_time_to_delivery_del',
        ),

    );
}
if (!isset($settings_arg_timepicker)) {
    $categories = array();
    foreach (TPFW::get_all_categories('all', 1) as $category) {
        $categories += array(
            esc_attr($category->cat_ID) => esc_html($category->name)
        );
    }
    $settings_args_timepicker = array(
        array(
            'name' => __('Time Picker Settings', 'checkout-time-picker-for-woocommerce'),
            'type' => 'title',
            'id' => 'tpfw_timepicker_settings',
           
        ),
        array(
            'name' => __('Selectable days', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_timepick_days_qty',

            'default' => 2,
            'type' => 'number',
            'desc' => __('Set the number of days to pick time from', 'checkout-time-picker-for-woocommerce'),
            'custom_attributes' => array(
                'min' => 1,
                'step' => 1,
                'required' => 'required'
            ),
        ),
        array(
            'name' => __('Enable maximum orders or items per time slot', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_max_slot_enable',

            'default' => 'no',
            'type' => 'select',
            'options' => array(
                'no' => __('Disable', 'checkout-time-picker-for-woocommerce'),
                'yes' => __('Orders', 'checkout-time-picker-for-woocommerce'),
                'items' => __('Items', 'checkout-time-picker-for-woocommerce'),
            ),
        ),
         array(
            'name' => __('Apply full slot time on shipping mode', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_max_slot_mode',

            'default' => 'both',
            'type' => 'select',
            'options' => array(
                'both' => __('Pickup and Delivery', 'checkout-time-picker-for-woocommerce'),
                'delivery' => __('Only delivery', 'checkout-time-picker-for-woocommerce'),
                'pickup' => __('Only pickup', 'checkout-time-picker-for-woocommerce'),
            ),
        ),
        array(
            'name' => __('Choose categories to include', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_max_slot_cats',
            'default' => array(),
            'type' => 'multiselect',
            'class' => 'wc-enhanced-select',
            'options' => $categories,
            'css' => 'max-width:200px;height:auto;',
            'desc' => __('Select product categories to include when counting the items per time slot', 'checkout-time-picker-for-woocommerce')
        ),
        array(
            'name' => __('Set maximum orders/items per time slot', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_max_slot',

            'default' => 999,
            'type' => 'number',
            'desc' => __('Set the maximum quantity of orders/items per time slot', 'checkout-time-picker-for-woocommerce'),
            'custom_attributes' => array(
                'min' => 1
            ),
        ),
        array(
            'name' => __('Preselect closest timeslot', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_preselect_time',
            'type' => 'checkbox',

            'desc' => __('Preselect the closest available time slot (Only when date is today)', 'checkout-time-picker-for-woocommerce'),
            'default' => 'no'
        ),
        array(
            'name' => __('Time Picker Placement', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_time_picker_placement',
            'default' => 'before_details',
            'type' => 'select',
            'options' => array(

                'before_details' => __('Before Customer Details', 'checkout-time-picker-for-woocommerce'),
                'first_in_details' => __('First at Customer Details', 'checkout-time-picker-for-woocommerce'),
                'after_order_notes' => __('After Order Notes', 'checkout-time-picker-for-woocommerce'),
                'before_payment' => __('Before Payment', 'checkout-time-picker-for-woocommerce'),
                'after_place_order' => __('After Place Order', 'checkout-time-picker-for-woocommerce'),
            ),

            'desc' => __('Select where on the checkout page to place the time picker. (This only applies to the old checkout page)', 'checkout-time-picker-for-woocommerce')
        ),
        array(
            'name' => __('Show time ranges?', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_timepicker_ranges',

            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Display time as ranges instead of a specific time', 'checkout-time-picker-for-woocommerce'),
        ),
        array(
            'name' => __('Date format', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_timepicker_dateformat',

            'default' => 'default',
            'type' => 'select',
            'options' => array(
                'default' => __('From general WordPress settings', 'checkout-time-picker-for-woocommerce'),
                'custom' => __('Custom format', 'checkout-time-picker-for-woocommerce'),

            ),
            'desc' => __('Choose the date format of the date picker.', 'checkout-time-picker-for-woocommerce')

        ),
        array(
            'name' => __('Custom date format', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_timepicker_dateformat_custom',

            'default' => get_option('date_format'),
            'type' => 'text',
            'desc' => __(' <a href="https://wordpress.org/support/article/formatting-date-and-time/"> Date formatting documentation</a>', 'checkout-time-picker-for-woocommerce')
        ),


        array(
            'name' => __('When start showing "As soon as possible"', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_time_before_asap',

            'default' => 60,
            'type' => 'number',
            'custom_attributes' => array(
                'min' => 0,
                'step' => 1,

            ),
            'desc' => __('How many minutes before first timeslot to show "As soon as possible" as first timepicker choice.', 'checkout-time-picker-for-woocommerce'),
        ),

        array(
            'type' => 'sectionend',
            'id' => 'tpfw_timepicker_settings',
        ),
        array(
            'name' => __('Time Picker for Pickup Orders', 'checkout-time-picker-for-woocommerce'),
            'type' => 'title',
            'id' => 'tpfw_pickuptimes',
            'desc' => __('Let the customer choose a time for when to pick up their order.', 'checkout-time-picker-for-woocommerce'),
            'class' => 'tpfw_'
        ),
        array(
            'name' => __('Enable', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_pickuptimes_enable',

            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('This enables the time picker on the checkout page. To open slot times it is also needed to add a schedule for the time picker at the "Availability Schedule" settings tab.', 'checkout-time-picker-for-woocommerce'),
        ),
        array(
            'name' => __('Postpone', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_pickup_postpone',

            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Postpone times avalible for selection using the configuration in the "Order Time Managment" Tab.', 'checkout-time-picker-for-woocommerce'),
        ),
        array(
            'name' => __('When to start count processing orders/items?', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_pickup_start_count_processing',

            'default' => 1440,
            'type' => 'number',
            'desc' => __('If time slots are postponed with extra time, choose how many minutes before its time slot a processing order will be included in the postponement. Processing orders without any time slot will always be included.', 'checkout-time-picker-for-woocommerce'),
        ),
        array(
            'name' => __('Step', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_pickuptime_step',

            'default' => 15,
            'type' => 'number',
            'custom_attributes' => array(
                'min' => 1,
                'step' => 1,
                'required' => 'required'
            ),
            'desc' => __('Set the time step in minutes from which customers can choose from', 'checkout-time-picker-for-woocommerce'),
        ),
        array(
            'name' => __('As soon as possible?', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_show_asap_pickup',

            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Show "As soon as possible" as first choice when time slots are available', 'checkout-time-picker-for-woocommerce'),
        ),




        array(
            'type' => 'sectionend',
            'id' => 'tpfw_pickuptimes',
        ),
        array(
            'name' => __('Time Picker for Delivery Orders', 'checkout-time-picker-for-woocommerce'),
            'type' => 'title',
            'id' => 'tpfw_deliverytimes',
            'desc' => __('Let the customer choose a time for when to expect their order to be delivered', 'checkout-time-picker-for-woocommerce'),
        ),
        array(
            'name' => __('Enable', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_deliverytime_enable',

            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('This enables the time picker on the checkout page. To open slot times it is also needed to add a schedule for the time picker at the "Availability Schedule" settings tab.', 'checkout-time-picker-for-woocommerce'),
        ),
        array(
            'name' => __('Postpone', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_delivery_postpone',

            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Postpone times avalible for selection using the configuration in the "Order Time Managment" Tab.', 'checkout-time-picker-for-woocommerce'),
        ),
        array(
            'name' => __('When to start count processing orders/items?', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_delivery_start_count_processing',

            'default' => 1440,
            'type' => 'number',
            'desc' => __('If time slots are postponed with extra time, choose how many minutes before its time slot a processing order will be included in the postponement. Processing orders without any time slot will always be included.', 'checkout-time-picker-for-woocommerce'),
        ),
        array(
            'name' => __('Step', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_deliverytime_step',

            'default' => 15,
            'type' => 'number',
            'custom_attributes' => array(
                'min' => 1,
                'step' => 1,
                'required' => 'required'
            ),
            'desc' => __('Set the time step in minutes from which customers can choose from', 'checkout-time-picker-for-woocommerce'),
        ),
        array(
            'name' => __('As soon as possible?', 'checkout-time-picker-for-woocommerce'),
            'id' => 'tpfw_show_asap_delivery',

            'default' => 'no',
            'type' => 'checkbox',
            'desc' => __('Show "As soon as possible" as first choice when time slots are available', 'checkout-time-picker-for-woocommerce'),
        ),
        array(
            'type' => 'sectionend',
            'id' => 'tpfw_deliverytimes',
        ),
    );
}


<?php
/**
 * Jobs Meta Box
 *
 * Renders the interface for configuring a vector sync job and handles
 * saving the settings.  The meta box is displayed on the edit screen
 * for the vector_sync_job post type.  All configuration options are
 * stored in the vector sync jobs table via the Vector_Sync_Jobs_DB
 * class.  Each job defines a single sync target (post type) with its
 * own vector space, meta fields, statuses and schedule.
 */
class Vector_Sync_Jobs_Meta_Box {
    /**
     * Register hooks to add the meta box and save its data.
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
        add_action( 'save_post_vector_sync_job', array( __CLASS__, 'save_job' ), 10, 2 );
    }

    /**
     * Add our custom meta box to the vector sync job post type.
     */
    public static function add_meta_box() {
        add_meta_box(
            'vector_sync_job_settings',
            __( 'Vector Sync Job Settings', 'vector-sync' ),
            array( __CLASS__, 'render_meta_box' ),
            'vector_sync_job',
            'normal',
            'high'
        );
    }

    /**
     * Render the meta box fields.  Uses data manager to list available
     * post types, meta keys and statuses.  Pre-populates fields with
     * existing job settings.  A small inline script handles dynamic
     * updates when the post type changes.
     *
     * @param WP_Post $post Current post object.
     */
    public static function render_meta_box( $post ) {
        // Retrieve existing settings from DB.
        $settings = Vector_Sync_Jobs_DB::get_job_settings( $post->ID );
        $service  = $settings['service'] ?? '';
        $vector_space = $settings['vector_space'] ?? '';
        $new_name = $settings['new_vector_space_name'] ?? '';
        $post_type = $settings['post_type'] ?? '';
        $meta_fields = $settings['meta_fields'] ?? array();
        $statuses    = $settings['statuses'] ?? array();
        $start_date  = $settings['start_date'] ?? '';
        $order_start = $settings['order_start_date'] ?? '';
        $schedule_time = $settings['schedule_time'] ?? '';
        $recurrence   = $settings['recurrence'] ?? 'hourly';

        wp_nonce_field( 'vector_sync_job_save', 'vector_sync_job_nonce' );

        $data_manager = new Vector_Sync_Data_Manager();
        $api_client   = new Vector_Sync_Api_Client();
        // Fetch vector spaces for both services to use later when JS updates.
        $spaces = array(
            'pinecone' => array(),
            'openai'   => array(),
        );
        foreach ( array( 'pinecone', 'openai' ) as $svc ) {
            $resp = $api_client->list_vector_spaces( $svc );
            if ( ! is_wp_error( $resp ) ) {
                $spaces[ $svc ] = $resp;
            }
        }
        // Gather meta keys and statuses for each post type.  We'll embed
        // these arrays as JSON for the dynamic script.  Also compute
        // counts for informational purposes.
        $all_meta   = array();
        $all_status = array();
        $all_counts = array();
        $post_types = $data_manager->get_all_post_types();
        foreach ( $post_types as $slug => $obj ) {
            $meta = $data_manager->get_meta_keys( $slug );
            $status_options = $data_manager->get_statuses( $slug );
            $count = $data_manager->get_count_for_type( $slug );
            $all_meta[ $slug ]   = $meta;
            $all_status[ $slug ] = $status_options;
            $all_counts[ $slug ] = $count;
        }
        // Output the form fields.
        echo '<table class="form-table" role="presentation">';
        // Service selection.
        echo '<tr><th><label for="vector_sync_job_service">' . esc_html__( 'Platform', 'vector-sync' ) . '</label></th><td>';
        foreach ( array( 'pinecone' => 'Pinecone', 'openai' => 'OpenAI' ) as $val => $label ) {
            echo '<label><input type="radio" name="vector_sync_job_service" value="' . esc_attr( $val ) . '"' . checked( $service, $val, false ) . '/> ' . esc_html( $label ) . '</label> ';
        }
        echo '<p class="description">' . esc_html__( 'Choose which vector database to use.', 'vector-sync' ) . '</p></td></tr>';
        // Vector space selector.
        echo '<tr><th><label for="vector_sync_job_vector_space">' . esc_html__( 'Vector Space', 'vector-sync' ) . '</label></th><td>';
        echo '<select name="vector_sync_job_vector_space" id="vector_sync_job_vector_space">';
        echo '<option value="">' . esc_html__( '-- Select --', 'vector-sync' ) . '</option>';
        // Populate options for the current service selection; fallback to first service if none selected.
        $service_for_spaces = $service ?: 'pinecone';
        foreach ( $spaces[ $service_for_spaces ] as $sp ) {
            $val   = $sp['id'];
            $label = $sp['name'];
            echo '<option value="' . esc_attr( $val ) . '"' . selected( $vector_space, $val, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Select the existing vector space.  List updates when platform changes.', 'vector-sync' ) . '</p></td></tr>';
        // New space name.
        echo '<tr><th><label for="vector_sync_job_new_name">' . esc_html__( 'New Vector Space Name', 'vector-sync' ) . '</label></th><td>';
        echo '<input type="text" name="vector_sync_job_new_name" value="' . esc_attr( $new_name ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Optionally create a new vector space.  Leave blank to use existing.', 'vector-sync' ) . '</p></td></tr>';
        // Post type select.
        echo '<tr><th><label for="vector_sync_job_post_type">' . esc_html__( 'Content Type', 'vector-sync' ) . '</label></th><td>';
        echo '<select name="vector_sync_job_post_type" id="vector_sync_job_post_type">';
        echo '<option value="">' . esc_html__( '-- Select --', 'vector-sync' ) . '</option>';
        foreach ( $post_types as $slug => $obj ) {
            $name  = $obj->labels->name;
            $count = $all_counts[ $slug ];
            echo '<option value="' . esc_attr( $slug ) . '"' . selected( $post_type, $slug, false ) . '>' . esc_html( sprintf( '%s (%d)', $name, $count ) ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Select the type of content to sync.', 'vector-sync' ) . '</p></td></tr>';
        // Meta fields and statuses containers.  We'll output all meta keys and statuses but hide them via JS.
        echo '<tr><th>' . esc_html__( 'Meta Fields & Statuses', 'vector-sync' ) . '</th><td>';
        echo '<div id="vector_sync_job_meta_status_container">';
        foreach ( $post_types as $slug => $obj ) {
            // Meta keys list.
            $meta_list = $all_meta[ $slug ];
            $status_list = $all_status[ $slug ];
            echo '<div class="vector-sync-type-fields" data-type="' . esc_attr( $slug ) . '" style="display:none;">';
            // Meta fields.
            echo '<strong>' . esc_html__( 'Meta Fields', 'vector-sync' ) . '</strong><br />';
            if ( ! empty( $meta_list ) ) {
                foreach ( $meta_list as $key ) {
                    $checked = isset( $meta_fields[ $slug ] ) && in_array( $key, $meta_fields[ $slug ], true );
                    echo '<label><input type="checkbox" name="vector_sync_job_meta_fields[' . esc_attr( $slug ) . '][]" value="' . esc_attr( $key ) . '"' . checked( $checked, true, false ) . '/> ' . esc_html( $key ) . '</label><br />';
                }
            } else {
                echo '<p>' . esc_html__( 'No meta fields found.', 'vector-sync' ) . '</p>';
            }
            // Statuses.
            if ( ! empty( $status_list ) ) {
                echo '<strong>' . esc_html__( 'Statuses', 'vector-sync' ) . '</strong><br />';
                foreach ( $status_list as $status_slug => $status_label ) {
                    $sel = isset( $statuses[ $slug ] ) ? (array) $statuses[ $slug ] : array();
                    $checked = in_array( $status_slug, $sel, true );
                    echo '<label><input type="checkbox" name="vector_sync_job_statuses[' . esc_attr( $slug ) . '][]" value="' . esc_attr( $status_slug ) . '"' . checked( $checked, true, false ) . '/> ' . esc_html( $status_label ) . '</label><br />';
                }
            }
            echo '</div>';
        }
        echo '</div>';
        echo '<p class="description">' . esc_html__( 'Select meta fields and statuses to include.', 'vector-sync' ) . '</p>';
        echo '</td></tr>';
        // Start date.
        echo '<tr><th><label for="vector_sync_job_start_date">' . esc_html__( 'Start Date', 'vector-sync' ) . '</label></th><td>';
        echo '<input type="date" name="vector_sync_job_start_date" value="' . esc_attr( $start_date ) . '" />';
        echo '<p class="description">' . esc_html__( 'Only sync items modified after this date (post types) or created after this date (orders).', 'vector-sync' ) . '</p></td></tr>';
        // Order start date (for orders only).
        echo '<tr class="vector-sync-order-only" style="display:none;"><th><label for="vector_sync_job_order_start_date">' . esc_html__( 'Order Start Date', 'vector-sync' ) . '</label></th><td>';
        echo '<input type="date" name="vector_sync_job_order_start_date" value="' . esc_attr( $order_start ) . '" />';
        echo '<p class="description">' . esc_html__( 'Filter orders by creation date.  Leave blank to include all.', 'vector-sync' ) . '</p></td></tr>';
        // Schedule time and recurrence.
        echo '<tr><th><label for="vector_sync_job_schedule_time">' . esc_html__( 'Initial Import Time', 'vector-sync' ) . '</label></th><td>';
        echo '<input type="time" name="vector_sync_job_schedule_time" value="' . esc_attr( $schedule_time ) . '" />';
        echo '<p class="description">' . esc_html__( 'Select a time of day for the first import (24h format).', 'vector-sync' ) . '</p></td></tr>';
        echo '<tr><th><label for="vector_sync_job_recurrence">' . esc_html__( 'Recurrence', 'vector-sync' ) . '</label></th><td>';
        echo '<select name="vector_sync_job_recurrence">';
        foreach ( array( 'hourly' => __( 'Hourly' ), 'twicedaily' => __( 'Twice Daily' ), 'daily' => __( 'Daily' ) ) as $val => $label ) {
            echo '<option value="' . esc_attr( $val ) . '"' . selected( $recurrence, $val, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'How often to run recurring updates.', 'vector-sync' ) . '</p></td></tr>';
        echo '</table>';
        // Inline script for dynamic UI.  Show meta/status fields based on post type, update vector spaces when platform changes, and toggle order date field.
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            function updateTypeFields() {
                var type = $('#vector_sync_job_post_type').val();
                $('.vector-sync-type-fields').hide();
                $('.vector-sync-type-fields[data-type="' + type + '"]').show();
                // Show order date when type is order or refund
                if (type === 'shop_order' || type === 'shop_order_refund') {
                    $('.vector-sync-order-only').show();
                } else {
                    $('.vector-sync-order-only').hide();
                }
            }
            function updateVectorSpaces() {
                var service = $('input[name="vector_sync_job_service"]:checked').val();
                var spaces = <?php echo wp_json_encode( $spaces ); ?>;
                var select = $('#vector_sync_job_vector_space');
                var current = select.val();
                select.empty().append($('<option/>').val('').text('-- Select --'));
                if (spaces[service]) {
                    $.each(spaces[service], function(i, sp){
                        var opt = $('<option/>').val(sp.id).text(sp.name);
                        if (current && current === sp.id) opt.prop('selected', true);
                        select.append(opt);
                    });
                }
            }
            $('#vector_sync_job_post_type').on('change', updateTypeFields);
            $('input[name="vector_sync_job_service"]').on('change', updateVectorSpaces);
            // Initial display
            updateTypeFields();
            updateVectorSpaces();
        });
        </script>
        <?php
    }

    /**
     * Handle saving job settings when the post is saved.  Validates the
     * nonce, sanitises inputs and persists settings to the jobs table.
     * It also triggers scheduling of the job.
     *
     * @param int     $post_id The post ID being saved.
     * @param WP_Post $post    The post object.
     */
    public static function save_job( $post_id, $post ) {
        // Verify nonce and user capability.
        if ( ! isset( $_POST['vector_sync_job_nonce'] ) || ! wp_verify_nonce( $_POST['vector_sync_job_nonce'], 'vector_sync_job_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        // Gather input values.
        $service      = isset( $_POST['vector_sync_job_service'] ) ? sanitize_key( $_POST['vector_sync_job_service'] ) : '';
        $vector_space = isset( $_POST['vector_sync_job_vector_space'] ) ? sanitize_text_field( $_POST['vector_sync_job_vector_space'] ) : '';
        $new_name     = isset( $_POST['vector_sync_job_new_name'] ) ? sanitize_text_field( $_POST['vector_sync_job_new_name'] ) : '';
        $post_type    = isset( $_POST['vector_sync_job_post_type'] ) ? sanitize_key( $_POST['vector_sync_job_post_type'] ) : '';
        $meta_fields  = array();
        if ( isset( $_POST['vector_sync_job_meta_fields'] ) && is_array( $_POST['vector_sync_job_meta_fields'] ) ) {
            foreach ( $_POST['vector_sync_job_meta_fields'] as $type => $keys ) {
                $meta_fields[ sanitize_key( $type ) ] = array_map( 'sanitize_text_field', (array) $keys );
            }
        }
        $statuses = array();
        if ( isset( $_POST['vector_sync_job_statuses'] ) && is_array( $_POST['vector_sync_job_statuses'] ) ) {
            foreach ( $_POST['vector_sync_job_statuses'] as $type => $vals ) {
                $statuses[ sanitize_key( $type ) ] = array_map( 'sanitize_text_field', (array) $vals );
            }
        }
        $start_date  = isset( $_POST['vector_sync_job_start_date'] ) ? sanitize_text_field( $_POST['vector_sync_job_start_date'] ) : '';
        $order_start = isset( $_POST['vector_sync_job_order_start_date'] ) ? sanitize_text_field( $_POST['vector_sync_job_order_start_date'] ) : '';
        $schedule_time = isset( $_POST['vector_sync_job_schedule_time'] ) ? sanitize_text_field( $_POST['vector_sync_job_schedule_time'] ) : '';
        $recurrence   = isset( $_POST['vector_sync_job_recurrence'] ) ? sanitize_text_field( $_POST['vector_sync_job_recurrence'] ) : 'hourly';
        // Build settings array.
        $settings = array(
            'service'              => $service,
            'vector_space'         => $vector_space,
            'new_vector_space_name'=> $new_name,
            'post_type'            => $post_type,
            'meta_fields'          => $meta_fields,
            'statuses'             => $statuses,
            'start_date'           => $start_date,
            'order_start_date'     => $order_start,
            'schedule_time'        => $schedule_time,
            'recurrence'           => $recurrence,
        );
        // Persist settings in jobs table.
        Vector_Sync_Jobs_DB::save_job_settings( $post_id, $settings );
        // Schedule the job.  Uses scheduler class to handle cron events.
        Vector_Sync_Jobs_Scheduler::schedule_job( $post_id, $settings );
    }
}
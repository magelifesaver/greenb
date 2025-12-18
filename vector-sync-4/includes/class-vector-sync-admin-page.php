<?php
/**
 * Admin Page
 *
 * Responsible for rendering the settings page in the WordPress admin.  It
 * registers a top‑level menu item and defines tabs for API configuration and
 * vector space management.  Settings are stored in a single option,
 * `vector_sync_settings`, which is an array containing all configuration
 * values.  Sanitisation and escaping are applied for security.
 */
class Vector_Sync_Admin_Page {
    /**
     * Constructor hooks into WordPress to set up the settings page and
     * register settings.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        // AJAX endpoint for verifying API connectivity.  The callback is a
        // static method defined below.  Only users with manage_options can
        // trigger this action.
        add_action( 'wp_ajax_vector_sync_verify_api', array( $this, 'ajax_verify_api' ) );
    }

    /**
     * Add a menu item under Settings.  Users need the `manage_options`
     * capability to access this page.
     */
    public function add_menu() {
        // Register a top‑level menu item for Vector Sync.  This menu
        // contains submenus for the settings page and the jobs list.  The
        // top‑level page itself renders the settings page.  Users need the
        // manage_options capability to access these pages.
        add_menu_page(
            __( 'Vector Sync', 'vector-sync' ),
            __( 'Vector Sync', 'vector-sync' ),
            'manage_options',
            'vector-sync',
            array( $this, 'render_settings_page' ),
            'dashicons-schedule',
            56
        );
        // Settings submenu.  We reuse the same slug as the top‑level
        // page so clicking on "Vector Sync" will direct to the settings.
        add_submenu_page(
            'vector-sync',
            __( 'Settings', 'vector-sync' ),
            __( 'Settings', 'vector-sync' ),
            'manage_options',
            'vector-sync',
            array( $this, 'render_settings_page' )
        );
        // Jobs submenu.  This page redirects to the custom post type list for
        // vector sync jobs.  We use a separate slug to avoid conflicts.
        add_submenu_page(
            'vector-sync',
            __( 'Jobs', 'vector-sync' ),
            __( 'Jobs', 'vector-sync' ),
            'manage_options',
            'vector-sync-jobs',
            array( $this, 'render_jobs_page' )
        );
    }

    /**
     * Render the settings page.  This is the callback for the top‑level
     * menu and the Settings submenu.  It simply proxies to render_page().
     */
    public function render_settings_page() {
        $this->render_page();
    }

    /**
     * Render the jobs page.  We redirect to the list table view of our
     * custom post type.  Using a separate method allows us to hook
     * redirection logic into the submenu callback.
     */
    public function render_jobs_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        // Redirect to the edit screen for the vector_sync_job post type.
        wp_safe_redirect( admin_url( 'edit.php?post_type=vector_sync_job' ) );
        exit;
    }

    /**
     * Register our settings.  We register a single option array and rely on
     * sanitisation callbacks to validate individual fields.
     */
    public function register_settings() {
        register_setting( 'vector_sync_settings_group', 'vector_sync_settings', array( $this, 'sanitize_settings' ) );
    }

    /**
     * Sanitise all options before saving.  We recursively sanitise strings and
     * restrict allowed values for enumerated fields.
     *
     * @param array $input Raw input from the form.
     * @return array Sanitised input.
     */
    public function sanitize_settings( $input ) {
        /**
         * Sanitize all settings and persist them to our custom table.  The settings
         * structure is divided into API credentials and per‑service vectors.  We
         * merge incoming data with existing settings so that saving one tab
         * doesn’t wipe out values from another.  After saving, cron jobs are
         * rescheduled to reflect the new configuration.  The return value from
         * this callback is ignored because we intercept option retrieval via
         * the pre_option filter; nevertheless returning the old settings
         * prevents WordPress from overwriting the option.
         *
         * @param array $input Raw POSTed data from the settings form.
         * @return array Previous settings (unused by our plugin).
         */
        $old     = Vector_Sync_DB::get_settings();
        $settings = $old;

        // API keys may come from top‑level fields for backward compatibility or
        // under the 'api' key.  We normalise to a nested structure.
        // Ensure the array exists.
        if ( ! isset( $settings['api'] ) || ! is_array( $settings['api'] ) ) {
            $settings['api'] = array();
        }
        // Pinecone API key.
        if ( isset( $input['pinecone_api_key'] ) ) {
            $settings['api']['pinecone_api_key'] = sanitize_text_field( $input['pinecone_api_key'] );
        } elseif ( isset( $input['api']['pinecone_api_key'] ) ) {
            $settings['api']['pinecone_api_key'] = sanitize_text_field( $input['api']['pinecone_api_key'] );
        }
        // Pinecone environment; provide default if empty.
        if ( isset( $input['pinecone_environment'] ) ) {
            $env = sanitize_text_field( $input['pinecone_environment'] );
            $settings['api']['pinecone_environment'] = $env ? $env : 'us-west4-gcp';
        } elseif ( isset( $input['api']['pinecone_environment'] ) ) {
            $env = sanitize_text_field( $input['api']['pinecone_environment'] );
            $settings['api']['pinecone_environment'] = $env ? $env : 'us-west4-gcp';
        }
        // OpenAI API key.
        if ( isset( $input['openai_api_key'] ) ) {
            $settings['api']['openai_api_key'] = sanitize_text_field( $input['openai_api_key'] );
        } elseif ( isset( $input['api']['openai_api_key'] ) ) {
            $settings['api']['openai_api_key'] = sanitize_text_field( $input['api']['openai_api_key'] );
        }

        // OpenAI optional organisation ID.
        if ( isset( $input['openai_org_id'] ) ) {
            $settings['api']['openai_org_id'] = sanitize_text_field( $input['openai_org_id'] );
        } elseif ( isset( $input['api']['openai_org_id'] ) ) {
            $settings['api']['openai_org_id'] = sanitize_text_field( $input['api']['openai_org_id'] );
        }

        // OpenAI optional project ID.
        if ( isset( $input['openai_project_id'] ) ) {
            $settings['api']['openai_project_id'] = sanitize_text_field( $input['openai_project_id'] );
        } elseif ( isset( $input['api']['openai_project_id'] ) ) {
            $settings['api']['openai_project_id'] = sanitize_text_field( $input['api']['openai_project_id'] );
        }

        // Sanitise per‑service vector space settings.  Supported services.
        $services = array( 'pinecone', 'openai' );
        foreach ( $services as $service ) {
            if ( ! isset( $settings[ $service ] ) || ! is_array( $settings[ $service ] ) ) {
                $settings[ $service ] = array();
            }
            if ( isset( $input[ $service ] ) && is_array( $input[ $service ] ) ) {
                $svc_input = $input[ $service ];
                // Vector space identifier.
                if ( isset( $svc_input['vector_space'] ) ) {
                    $settings[ $service ]['vector_space'] = sanitize_text_field( $svc_input['vector_space'] );
                }
                // New vector space name.
                if ( isset( $svc_input['new_vector_space_name'] ) ) {
                    $settings[ $service ]['new_vector_space_name'] = sanitize_text_field( $svc_input['new_vector_space_name'] );
                }
                // Post types.
                if ( isset( $svc_input['post_types'] ) ) {
                    $settings[ $service ]['post_types'] = array_map( 'sanitize_text_field', (array) $svc_input['post_types'] );
                }
                // Meta fields per post type.
                if ( isset( $svc_input['meta_fields'] ) && is_array( $svc_input['meta_fields'] ) ) {
                    $fields = array();
                    foreach ( $svc_input['meta_fields'] as $type => $vals ) {
                        $fields[ sanitize_key( $type ) ] = array_map( 'sanitize_text_field', (array) $vals );
                    }
                    $settings[ $service ]['meta_fields'] = $fields;
                }
                // Statuses per post type.  Users can limit which statuses are
                // indexed.  If none are selected the data manager will fall
                // back to default statuses.
                if ( isset( $svc_input['statuses'] ) && is_array( $svc_input['statuses'] ) ) {
                    $stati = array();
                    foreach ( $svc_input['statuses'] as $type => $vals ) {
                        $stati[ sanitize_key( $type ) ] = array_map( 'sanitize_text_field', (array) $vals );
                    }
                    $settings[ $service ]['statuses'] = $stati;
                }
                // Start date.
                if ( isset( $svc_input['start_date'] ) ) {
                    $settings[ $service ]['start_date'] = sanitize_text_field( $svc_input['start_date'] );
                }
                // Schedule time.
                if ( isset( $svc_input['schedule_time'] ) ) {
                    $settings[ $service ]['schedule_time'] = sanitize_text_field( $svc_input['schedule_time'] );
                }
                // Recurrence.
                if ( isset( $svc_input['recurrence'] ) ) {
                    $settings[ $service ]['recurrence'] = sanitize_text_field( $svc_input['recurrence'] );
                }
            }
        }

        // Persist to the custom table.
        Vector_Sync_DB::update_settings( $settings );
        // Reschedule cron events based on the new configuration.
        Vector_Sync_Scheduler::settings_updated( $old, $settings );
        // Return old settings to prevent updating the wp_options row.  The
        // pre_option filter will supply our custom value when get_option() is
        // called.
        return $old;
    }

    /**
     * Render the settings page.  Determines the current tab from the query
     * string and outputs the appropriate form.  Tabs are rendered using
     * WordPress’s nav‑tab wrapper【43403193683026†L59-L92】.
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        // Render only the API tab since vector spaces are configured per job.
        $tabs = array( 'api' => __( 'API', 'vector-sync' ) );
        $current_tab = 'api';
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Vector Sync Settings', 'vector-sync' ) . '</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $tabs as $tab => $name ) {
            $class = 'nav-tab nav-tab-active';
            $url   = add_query_arg( array( 'page' => 'vector-sync', 'tab' => $tab ), admin_url( 'options-general.php' ) );
            echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
        }
        echo '</h2>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'vector_sync_settings_group' );
        $settings = Vector_Sync_DB::get_settings();
        $this->render_api_tab( $settings );
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Render the API settings tab.  Users enter their Pinecone and OpenAI API
     * keys and environment details here.
     *
     * @param array $settings Current settings.
     */
    private function render_api_tab( array $settings ) {
        // Pull API keys from nested structure.  Provide sensible defaults
        // where appropriate.  The environment list is limited to commonly used
        // regions; adjust as new regions become available【445401752421766†L95-L129】.
        $api    = $settings['api'] ?? array();
        $pc_key = $api['pinecone_api_key'] ?? '';
        $pc_env = $api['pinecone_environment'] ?? 'us-west4-gcp';
        $oa_key = $api['openai_api_key'] ?? '';
        $oa_org = $api['openai_org_id'] ?? '';
        $oa_project = $api['openai_project_id'] ?? '';
        echo '<table class="form-table" role="presentation">';
        // Pinecone API key.
        echo '<tr><th scope="row">' . esc_html__( 'Pinecone API Key', 'vector-sync' ) . '</th><td>';
        echo '<input type="text" name="vector_sync_settings[pinecone_api_key]" value="' . esc_attr( $pc_key ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Enter your Pinecone API key.', 'vector-sync' ) . '</p></td></tr>';
        // Pinecone environment as a select to avoid confusion.  If the user
        // provides a custom environment it will be retained when saving.  The
        // list includes a few of Pinecone’s major regions as of 2025【445401752421766†L95-L129】.
        $env_options = array(
            'us-west4-gcp'  => 'us-west4-gcp',
            'us-east4-gcp'  => 'us-east4-gcp',
            'us-east1-gcp'  => 'us-east1-gcp',
            'us-central1-gcp' => 'us-central1-gcp',
            'eu-west1-gcp'  => 'eu-west1-gcp',
            'eu-central1-gcp' => 'eu-central1-gcp',
            'us-east-1-aws' => 'us-east-1-aws',
            'us-west-2-aws' => 'us-west-2-aws',
        );
        echo '<tr><th scope="row">' . esc_html__( 'Pinecone Environment', 'vector-sync' ) . '</th><td>';
        echo '<select name="vector_sync_settings[pinecone_environment]">';
        // Add the current value if it isn’t in the predefined list.
        if ( $pc_env && ! isset( $env_options[ $pc_env ] ) ) {
            $env_options = array_merge( array( $pc_env => $pc_env ), $env_options );
        }
        foreach ( $env_options as $val => $label ) {
            echo '<option value="' . esc_attr( $val ) . '" ' . selected( $pc_env, $val, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Choose your Pinecone project region.  Leaving the default is fine for most users.', 'vector-sync' ) . '</p></td></tr>';
        // OpenAI API key.
        echo '<tr><th scope="row">' . esc_html__( 'OpenAI API Key', 'vector-sync' ) . '</th><td>';
        echo '<input type="text" name="vector_sync_settings[openai_api_key]" value="' . esc_attr( $oa_key ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Enter your OpenAI API key.', 'vector-sync' ) . '</p></td></tr>';

        // Optional OpenAI Organization ID.  When using legacy user API keys or multiple
        // organizations, specifying this header ensures the API targets the correct
        // organisation【799506338848902†L138-L146】.
        echo '<tr><th scope="row">' . esc_html__( 'OpenAI Organization ID', 'vector-sync' ) . '</th><td>';
        echo '<input type="text" name="vector_sync_settings[openai_org_id]" value="' . esc_attr( $oa_org ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Optional. Set your organization ID for API requests if you belong to multiple organisations.', 'vector-sync' ) . '</p></td></tr>';

        // Optional OpenAI Project ID.  Specifying this ensures that API requests
        // operate against the correct project【799506338848902†L148-L156】.  Leaving it blank uses the default project.
        echo '<tr><th scope="row">' . esc_html__( 'OpenAI Project ID', 'vector-sync' ) . '</th><td>';
        echo '<input type="text" name="vector_sync_settings[openai_project_id]" value="' . esc_attr( $oa_project ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Optional. Set your project ID for API requests when using legacy user API keys.', 'vector-sync' ) . '</p></td></tr>';
        echo '</table>';
    }

    /**
     * Render the vector spaces tab.  Lists available vector spaces and allows
     * creation of a new one.  Also displays post types and meta field
     * selection.  Because listing vector spaces requires API calls this
     * method queries the API client at render time.
     *
     * @param array $settings Current settings.
     */
    /**
     * Render a vector space configuration tab for a given service.  This
     * encapsulates both Pinecone and OpenAI configuration.  The form names
     * reflect the nested settings array: vector_sync_settings[service][...].
     *
     * @param string $service Service identifier (pinecone or openai).
     * @param array  $settings Current settings from the DB.
     */
    private function render_service_tab( $service, array $settings ) {
        // Fetch service‑specific settings or initialise defaults.
        $svc_settings = $settings[ $service ] ?? array();
        $selected     = $svc_settings['vector_space'] ?? '';
        $manager      = new Vector_Sync_Data_Manager();
        $api          = new Vector_Sync_Api_Client();
        $spaces       = array();
        $error_msg    = '';
        // List available vector spaces using the API client.  Missing API keys
        // result in user‑friendly errors shown above the select element.
        $response     = $api->list_vector_spaces( $service );
        if ( is_wp_error( $response ) ) {
            $error_msg = $response->get_error_message();
        } else {
            $spaces = $response;
        }
        // If a vector space is already selected but not returned in the API list
        // (for instance when the OpenAI list API is bugged), add it to the
        // dropdown so the user can continue using it.  The name defaults to
        // the ID when unknown.
        if ( $selected && ! empty( $selected ) ) {
            $found = false;
            foreach ( $spaces as $sp ) {
                if ( isset( $sp['id'] ) && $sp['id'] === $selected ) {
                    $found = true;
                    break;
                }
            }
            if ( ! $found ) {
                $spaces[] = array( 'id' => $selected, 'name' => $selected );
            }
        }
        // Build field name prefix.
        $prefix = 'vector_sync_settings[' . esc_attr( $service ) . ']';
        echo '<table class="form-table" role="presentation">';
        // Vector space selector.
        echo '<tr><th scope="row">' . esc_html__( 'Select Vector Space', 'vector-sync' ) . '</th><td>';
        if ( $error_msg ) {
            echo '<p class="description" style="color:red;">' . esc_html( $error_msg ) . '</p>';
        }
        echo '<select name="' . $prefix . '[vector_space]">';
        echo '<option value="">' . esc_html__( '-- Select --', 'vector-sync' ) . '</option>';
        foreach ( $spaces as $space ) {
            $val   = $space['id'];
            $label = $space['name'];
            $selected_attr = selected( $selected, $val, false );
            echo '<option value="' . esc_attr( $val ) . '" ' . $selected_attr . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Choose an existing vector space to sync into.', 'vector-sync' ) . '</p>';
        echo '</td></tr>';
        // New vector space name.  Creating a space is optional and will
        // trigger an API call on save.  Leaving this blank will use an
        // existing space selected above.
        echo '<tr><th scope="row">' . esc_html__( 'New Vector Space Name', 'vector-sync' ) . '</th><td>';
        echo '<input type="text" name="' . $prefix . '[new_vector_space_name]" value="" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Optionally enter a name to create a new vector space when saving.  Leave blank to use the selected space.', 'vector-sync' ) . '</p>';
        echo '</td></tr>';
        // Post type selection.  Include public post types, WooCommerce orders
        // and the pseudo‑type for users.
        $post_types = $manager->get_all_post_types();
        echo '<tr><th scope="row">' . esc_html__( 'Content Types to Sync', 'vector-sync' ) . '</th><td>';
        foreach ( $post_types as $slug => $obj ) {
            $checked = in_array( $slug, $svc_settings['post_types'] ?? array(), true );
            // Determine statuses and start date for this type to compute a count.
            $type_statuses = array();
            if ( isset( $svc_settings['statuses'][ $slug ] ) ) {
                $type_statuses = (array) $svc_settings['statuses'][ $slug ];
            }
            $start_date = $svc_settings['start_date'] ?? '';
            $count      = $manager->get_count_for_type( $slug, $start_date, $type_statuses );
            $label      = sprintf( '%s (%d)', $obj->labels->name, $count );
            echo '<label><input type="checkbox" name="' . $prefix . '[post_types][]" value="' . esc_attr( $slug ) . '" ' . checked( $checked, true, false ) . '/> ' . esc_html( $label ) . '</label><br />';
        }
        echo '<p class="description">' . esc_html__( 'Select which content types to include in the vector store.', 'vector-sync' ) . '</p>';
        echo '</td></tr>';
        // Meta field and status selection per content type.
        if ( ! empty( $svc_settings['post_types'] ) ) {
            foreach ( (array) $svc_settings['post_types'] as $type ) {
                // Meta fields
                $meta_keys = $manager->get_meta_keys( $type );
                echo '<tr><th scope="row">' . sprintf( esc_html__( 'Meta Fields for %s', 'vector-sync' ), esc_html( $type ) ) . '</th><td>';
                foreach ( $meta_keys as $key ) {
                    $checked = isset( $svc_settings['meta_fields'][ $type ] ) && in_array( $key, $svc_settings['meta_fields'][ $type ], true );
                    echo '<label><input type="checkbox" name="' . $prefix . '[meta_fields][' . esc_attr( $type ) . '][]" value="' . esc_attr( $key ) . '" ' . checked( $checked, true, false ) . '/> ' . esc_html( $key ) . '</label><br />';
                }
                echo '<p class="description">' . esc_html__( 'Check which meta fields to include.', 'vector-sync' ) . '</p>';
                echo '</td></tr>';
                // Statuses
                $status_options = $manager->get_statuses( $type );
                if ( ! empty( $status_options ) ) {
                    echo '<tr><th scope="row">' . sprintf( esc_html__( 'Statuses for %s', 'vector-sync' ), esc_html( $type ) ) . '</th><td>';
                    foreach ( $status_options as $status_slug => $status_label ) {
                        $selected_statuses = isset( $svc_settings['statuses'][ $type ] ) ? (array) $svc_settings['statuses'][ $type ] : array();
                        $checked = in_array( $status_slug, $selected_statuses, true );
                        echo '<label><input type="checkbox" name="' . $prefix . '[statuses][' . esc_attr( $type ) . '][]" value="' . esc_attr( $status_slug ) . '" ' . checked( $checked, true, false ) . '/> ' . esc_html( $status_label ) . '</label><br />';
                    }
                    echo '<p class="description">' . esc_html__( 'Limit syncing to selected statuses. Leave none to include defaults.', 'vector-sync' ) . '</p>';
                    echo '</td></tr>';
                }
            }
        }
        // Date filter.
        $start_date = $svc_settings['start_date'] ?? '';
        echo '<tr><th scope="row">' . esc_html__( 'Start Date', 'vector-sync' ) . '</th><td>';
        echo '<input type="date" name="' . $prefix . '[start_date]" value="' . esc_attr( $start_date ) . '" />';
        echo '<p class="description">' . esc_html__( 'Only sync items modified after this date. Leave empty for all.', 'vector-sync' ) . '</p>';
        echo '</td></tr>';
        // Schedule time and recurrence.
        $time       = $svc_settings['schedule_time'] ?? '';
        $recurrence = $svc_settings['recurrence'] ?? 'hourly';
        echo '<tr><th scope="row">' . esc_html__( 'Initial Import Time', 'vector-sync' ) . '</th><td>';
        echo '<input type="time" name="' . $prefix . '[schedule_time]" value="' . esc_attr( $time ) . '" />';
        echo '<p class="description">' . esc_html__( 'Select a time of day for the first import (24h format).', 'vector-sync' ) . '</p>';
        echo '</td></tr>';
        echo '<tr><th scope="row">' . esc_html__( 'Recurrence', 'vector-sync' ) . '</th><td>';
        echo '<select name="' . $prefix . '[recurrence]">';
        foreach ( array( 'hourly' => __( 'Hourly' ), 'twicedaily' => __( 'Twice Daily' ), 'daily' => __( 'Daily' ) ) as $val => $label ) {
            echo '<option value="' . esc_attr( $val ) . '" ' . selected( $recurrence, $val, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'How often to run recurring updates.', 'vector-sync' ) . '</p>';
        echo '</td></tr>';
        // API verification button.  This triggers an AJAX call to check the
        // credentials for the current service and display a notice.
        echo '<tr><th scope="row">' . esc_html__( 'Verify API', 'vector-sync' ) . '</th><td>';
        echo '<button type="button" class="button" id="' . esc_attr( $service ) . '-verify-api">' . esc_html__( 'Verify Credentials', 'vector-sync' ) . '</button>';
        echo '<span class="vector-sync-verify-status"></span>';
        echo '<p class="description">' . esc_html__( 'Click to test your API key and environment.', 'vector-sync' ) . '</p>';
        echo '</td></tr>';
        echo '</table>';
        // Output inline JavaScript to handle verification.  The script uses
        // WordPress’s AJAX endpoint and displays the result next to the button.
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            $('#<?php echo esc_js( $service ); ?>-verify-api').on('click', function(){
                var btn = $(this);
                var status = btn.closest('tr').find('.vector-sync-verify-status');
                status.text('<?php echo esc_js( __( 'Verifying...', 'vector-sync' ) ); ?>');
                $.post(ajaxurl, {
                    action: 'vector_sync_verify_api',
                    service: '<?php echo esc_js( $service ); ?>',
                    _wpnonce: '<?php echo wp_create_nonce( 'vector_sync_verify_api' ); ?>'
                }, function(response){
                    if (response.success) {
                        status.css('color','green').text('<?php echo esc_js( __( 'Connection successful!', 'vector-sync' ) ); ?>');
                    } else {
                        var msg = response.data ? response.data : '<?php echo esc_js( __( 'Connection failed.', 'vector-sync' ) ); ?>';
                        status.css('color','red').text(msg);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX callback to verify API connectivity.  It expects a service
     * parameter (pinecone or openai) and checks whether listing vector spaces
     * succeeds.  The result is returned as a JSON response.  A nonce is
     * validated for security.
     */
    public function ajax_verify_api() {
        check_ajax_referer( 'vector_sync_verify_api' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'vector-sync' ) );
        }
        $service = isset( $_POST['service'] ) ? sanitize_key( $_POST['service'] ) : '';
        if ( ! in_array( $service, array( 'pinecone', 'openai' ), true ) ) {
            wp_send_json_error( __( 'Invalid service.', 'vector-sync' ) );
        }
        $api  = new Vector_Sync_Api_Client();
        $resp = $api->list_vector_spaces( $service );
        if ( is_wp_error( $resp ) ) {
            wp_send_json_error( $resp->get_error_message() );
        }
        wp_send_json_success();
    }
}
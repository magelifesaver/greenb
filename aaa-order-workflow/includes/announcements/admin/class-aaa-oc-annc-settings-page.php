<?php
/**
 * File Path: /aaa-order-workflow/includes/announcements/admin/class-aaa-oc-annc-settings-page.php
 *
 * Purpose:
 * Admin UI to create/manage Workflow Board announcements + view per-user acknowledgements.
 * - Create/Edit/Delete announcements (title, content, start/end, active)
 * - Acknowledgements panel:
 *     - Select announcement -> list users (seen/accepted timestamps)
 *     - Reset a single user (forces popup to show again)
 *     - Reset all for an announcement
 *     - Export CSV
 *
 * Tables:
 * - {prefix}aaa_oc_announcements
 * - {prefix}aaa_oc_announcement_user
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_OC_Announcements_Settings_Page {

    protected $screen_slug = 'aaa-oc-announcements';

    public function __construct() {
	add_action( 'admin_menu', [ $this, 'register_menu' ], 99 );
	add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_media' ] );
    }

    public function maybe_enqueue_media( $hook ) {
	    if ( isset( $_GET['page'] ) && $_GET['page'] === $this->screen_slug ) {
	        // Loads the WP media modal so the "Add Media" button works
	        wp_enqueue_media();
	    }
	}
/** Add submenu: Workflow Announcements */
public function register_menu() {
	add_submenu_page(
		'aaa-oc-workflow-board',                 // parent: Workflow
		__( 'Workflow Announcements', 'aaa-order-workflow' ),
		__( 'WF Announcements', 'aaa-order-workflow' ),
		'manage_woocommerce',
		$this->screen_slug,
		[ $this, 'render_page' ]
	);
}

    protected function table_names() {
        global $wpdb;
        return [
            'ann' => $wpdb->prefix . 'aaa_oc_announcements',
            'usr' => $wpdb->prefix . 'aaa_oc_announcement_user',
        ];
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'aaa-order-workflow' ) );
        }

        // Handle CRUD + Ack actions (CSV may exit early).
        $message     = $this->maybe_handle_actions();
        $ack_message = $this->maybe_handle_ack_actions();

        $editing_id = isset( $_GET['annc_id'] ) ? absint( $_GET['annc_id'] ) : 0;
        $current    = $editing_id ? $this->get_announcement( $editing_id ) : null;

        echo '<div class="wrap">';
        echo '<h1 style="margin-bottom:12px;">' . esc_html__( 'Workflow Announcements', 'aaa-order-workflow' ) . '</h1>';

        if ( $message ) {
            echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
        }
        if ( $ack_message ) {
            echo '<div class="notice notice-success"><p>' . esc_html( $ack_message ) . '</p></div>';
        }
        if ( isset( $_GET['deleted'] ) ) {
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Announcement deleted.', 'aaa-order-workflow' ) . '</p></div>';
        }

        // Toolbar
        $add_url = admin_url( 'admin.php?page=' . $this->screen_slug );
        echo '<p><a href="' . esc_url( $add_url ) . '" class="button button-primary">' . esc_html__( 'Add New Announcement', 'aaa-order-workflow' ) . '</a></p>';

        // Form (Add/Edit)
        $this->render_form( $current );

        // List existing announcements
        $this->render_list();

        // Acknowledgements panel
        $this->render_ack_panel();

        echo '</div>';
    }

    /** ---------------------------
     *  CRUD: Create/Edit form
     *  --------------------------- */
    protected function render_form( $row ) {
        $is_edit  = ! empty( $row );
        $title    = $is_edit ? $row->title : '';
        $content  = $is_edit ? $row->content : '';
        $start_at = $is_edit ? $this->to_input_datetime( $row->start_at ) : '';
        $end_at   = $is_edit ? $this->to_input_datetime( $row->end_at ) : '';
        $active   = $is_edit ? (int) $row->is_active : 1;
        $action   = $is_edit ? 'update' : 'create';
        $heading  = $is_edit ? __( 'Edit Announcement', 'aaa-order-workflow' ) : __( 'New Announcement', 'aaa-order-workflow' );

        echo '<h2 style="margin-top:24px;">' . esc_html( $heading ) . '</h2>';
        echo '<form method="post" action="">';
        wp_nonce_field( 'aaa_oc_annc_save', 'aaa_oc_annc_nonce' );
        echo '<input type="hidden" name="annc_action" value="' . esc_attr( $action ) . '">';
        if ( $is_edit ) {
            echo '<input type="hidden" name="annc_id" value="' . esc_attr( (int) $row->id ) . '">';
        }

        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row"><label for="annc_title">' . esc_html__( 'Title', 'aaa-order-workflow' ) . '</label></th><td>';
        echo '<input type="text" class="regular-text" id="annc_title" name="annc_title" value="' . esc_attr( $title ) . '" required>';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="annc_content">' . esc_html__( 'Content', 'aaa-order-workflow' ) . '</label></th><td>';
        wp_editor(
            $content,
            'annc_content',
            [
                'textarea_name' => 'annc_content',
                'textarea_rows' => 8,
                'media_buttons' => true,
            ]
        );
        echo '<p class="description">' . esc_html__( 'This content appears in the popup shown on the Workflow Board.', 'aaa-order-workflow' ) . '</p>';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="annc_start">' . esc_html__( 'Start Date/Time', 'aaa-order-workflow' ) . '</label></th><td>';
        echo '<input type="datetime-local" id="annc_start" name="annc_start" value="' . esc_attr( $start_at ) . '">';
        echo '<p class="description">' . esc_html__( 'Announcement becomes eligible at this time (leave blank to start immediately).', 'aaa-order-workflow' ) . '</p>';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="annc_end">' . esc_html__( 'End Date/Time', 'aaa-order-workflow' ) . '</label></th><td>';
        echo '<input type="datetime-local" id="annc_end" name="annc_end" value="' . esc_attr( $end_at ) . '">';
        echo '<p class="description">' . esc_html__( 'Stop showing after this time (leave blank for no end).', 'aaa-order-workflow' ) . '</p>';
        echo '</td></tr>';

        echo '<tr><th scope="row">' . esc_html__( 'Active', 'aaa-order-workflow' ) . '</th><td>';
        echo '<label><input type="checkbox" name="annc_active" value="1" ' . checked( 1, $active, false ) . '> ' . esc_html__( 'Enabled', 'aaa-order-workflow' ) . '</label>';
        echo '</td></tr>';

        echo '</tbody></table>';

        submit_button( $is_edit ? __( 'Update Announcement', 'aaa-order-workflow' ) : __( 'Create Announcement', 'aaa-order-workflow' ) );
        echo '</form>';
    }

    /** ---------------------------
     *  CRUD: List
     *  --------------------------- */
    protected function render_list() {
        global $wpdb;
        $t = $this->table_names();

        $rows = $wpdb->get_results( "SELECT id, title, start_at, end_at, is_active, created_at, updated_at FROM {$t['ann']} ORDER BY id DESC" );

        echo '<h2 style="margin-top:28px;">' . esc_html__( 'Existing Announcements', 'aaa-order-workflow' ) . '</h2>';
        if ( empty( $rows ) ) {
            echo '<p>' . esc_html__( 'No announcements yet.', 'aaa-order-workflow' ) . '</p>';
            return;
        }

        $base = admin_url( 'admin.php?page=' . $this->screen_slug );

        echo '<table class="widefat striped" style="max-width:1100px;"><thead><tr>';
        echo '<th>' . esc_html__( 'ID', 'aaa-order-workflow' ) . '</th>';
        echo '<th>' . esc_html__( 'Title', 'aaa-order-workflow' ) . '</th>';
        echo '<th>' . esc_html__( 'Start', 'aaa-order-workflow' ) . '</th>';
        echo '<th>' . esc_html__( 'End', 'aaa-order-workflow' ) . '</th>';
        echo '<th>' . esc_html__( 'Active', 'aaa-order-workflow' ) . '</th>';
        echo '<th>' . esc_html__( 'Updated', 'aaa-order-workflow' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'aaa-order-workflow' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $rows as $r ) {
            $edit_url   = add_query_arg( [ 'annc_id' => (int) $r->id ], $base );
            $delete_url = wp_nonce_url( add_query_arg( [ 'annc_action' => 'delete', 'annc_id' => (int) $r->id ], $base ), 'aaa_oc_annc_delete_' . (int) $r->id );
            $view_ack   = add_query_arg( [ 'ack_annc_id' => (int) $r->id ], $base );

            echo '<tr>';
            echo '<td>' . (int) $r->id . '</td>';
            echo '<td>' . esc_html( $r->title ) . '</td>';
            echo '<td>' . esc_html( $this->fmt_dt( $r->start_at ) ) . '</td>';
            echo '<td>' . esc_html( $this->fmt_dt( $r->end_at ) ) . '</td>';
            echo '<td>' . ( $r->is_active ? '<span style="color:#0a0;font-weight:600;">' . esc_html__( 'Yes', 'aaa-order-workflow' ) . '</span>' : '<span style="color:#900;font-weight:600;">' . esc_html__( 'No', 'aaa-order-workflow' ) . '</span>' ) . '</td>';
            echo '<td>' . esc_html( $this->fmt_dt( $r->updated_at ) ) . '</td>';
            echo '<td><a class="button" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'aaa-order-workflow' ) . '</a> ';
            echo '<a class="button" href="' . esc_url( $view_ack ) . '">' . esc_html__( 'View Acks', 'aaa-order-workflow' ) . '</a> ';
            echo '<a class="button button-link-delete" href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'' . esc_js( __( 'Delete this announcement?', 'aaa-order-workflow' ) ) . '\');">' . esc_html__( 'Delete', 'aaa-order-workflow' ) . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /** ---------------------------
     *  Acknowledgements panel
     *  --------------------------- */
    protected function render_ack_panel() {
        $ann_list = $this->get_announcements_list(); // id => title
        echo '<h2 style="margin-top:32px;">' . esc_html__( 'User Acknowledgements', 'aaa-order-workflow' ) . '</h2>';

        if ( empty( $ann_list ) ) {
            echo '<p>' . esc_html__( 'No announcements found.', 'aaa-order-workflow' ) . '</p>';
            return;
        }

        $selected = isset( $_GET['ack_annc_id'] ) ? absint( $_GET['ack_annc_id'] ) : 0;
        $base     = admin_url( 'admin.php?page=' . $this->screen_slug );

        echo '<form method="get" action="" style="margin-bottom:12px;">';
        echo '<input type="hidden" name="page" value="' . esc_attr( $this->screen_slug ) . '">';
        echo '<label for="ack_annc_id" style="margin-right:8px;">' . esc_html__( 'Announcement', 'aaa-order-workflow' ) . '</label>';
        echo '<select name="ack_annc_id" id="ack_annc_id">';
        echo '<option value="0">' . esc_html__( 'Select…', 'aaa-order-workflow' ) . '</option>';
        foreach ( $ann_list as $id => $title ) {
            echo '<option value="' . (int) $id . '" ' . selected( $selected, $id, false ) . '>' . esc_html( "#{$id} — {$title}" ) . '</option>';
        }
        echo '</select> ';
        submit_button( __( 'View', 'aaa-order-workflow' ), 'secondary', '', false );
        if ( $selected ) {
            $export_url = wp_nonce_url( add_query_arg( [
                'page'        => $this->screen_slug,
                'ack_action'  => 'export_csv',
                'ack_annc_id' => (int) $selected,
            ], $base ), 'aaa_oc_annc_ack_export_' . (int) $selected );
            echo ' <a class="button" href="' . esc_url( $export_url ) . '">' . esc_html__( 'Export CSV', 'aaa-order-workflow' ) . '</a>';

            $reset_all_url = wp_nonce_url( add_query_arg( [
                'page'        => $this->screen_slug,
                'ack_action'  => 'reset_all',
                'ack_annc_id' => (int) $selected,
            ], $base ), 'aaa_oc_annc_ack_reset_all_' . (int) $selected );
            echo ' <a class="button button-link-delete" href="' . esc_url( $reset_all_url ) . '" onclick="return confirm(\'' . esc_js( __( 'Reset acceptance for ALL users for this announcement?', 'aaa-order-workflow' ) ) . '\');">' . esc_html__( 'Reset All', 'aaa-order-workflow' ) . '</a>';
        }
        echo '</form>';

        if ( $selected ) {
            $this->render_ack_table( $selected );
        }
    }

    protected function render_ack_table( $annc_id ) {
        global $wpdb;
        $t    = $this->table_names();
        $base = admin_url( 'admin.php?page=' . $this->screen_slug . '&ack_annc_id=' . (int) $annc_id );

        // Join users for nice display
        $rows = $wpdb->get_results( $wpdb->prepare("
            SELECT u.id as ack_id, u.user_id, u.seen_at, u.accepted, u.accepted_at,
                   wu.user_login, wu.user_email, umd.meta_value AS display_name
            FROM {$t['usr']} u
            LEFT JOIN {$wpdb->users} wu ON wu.ID = u.user_id
            LEFT JOIN {$wpdb->usermeta} umd ON umd.user_id = u.user_id AND umd.meta_key = 'display_name'
            WHERE u.announcement_id = %d
            ORDER BY u.accepted DESC, u.accepted_at DESC, u.seen_at DESC
        ", $annc_id ) );

        echo '<table class="widefat striped" style="max-width:1100px;"><thead><tr>';
        echo '<th>' . esc_html__( 'User', 'aaa-order-workflow' ) . '</th>';
        echo '<th>' . esc_html__( 'Email', 'aaa-order-workflow' ) . '</th>';
        echo '<th>' . esc_html__( 'Seen At', 'aaa-order-workflow' ) . '</th>';
        echo '<th>' . esc_html__( 'Accepted', 'aaa-order-workflow' ) . '</th>';
        echo '<th>' . esc_html__( 'Accepted At', 'aaa-order-workflow' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'aaa-order-workflow' ) . '</th>';
        echo '</tr></thead><tbody>';

        if ( empty( $rows ) ) {
            echo '<tr><td colspan="6">' . esc_html__( 'No user activity yet.', 'aaa-order-workflow' ) . '</td></tr>';
        } else {
            foreach ( $rows as $r ) {
                $user_link = $r->user_id ? get_edit_user_link( $r->user_id ) : '';
                $name      = $r->display_name ? $r->display_name : $r->user_login;
                $accepted  = $r->accepted ? '<span style="color:#0a0;font-weight:600;">' . esc_html__( 'Yes', 'aaa-order-workflow' ) . '</span>' : '<span style="color:#900;font-weight:600;">' . esc_html__( 'No', 'aaa-order-workflow' ) . '</span>';

                $reset_url = wp_nonce_url( add_query_arg( [
                    'page'        => $this->screen_slug,
                    'ack_action'  => 'reset_one',
                    'ack_annc_id' => (int) $annc_id,
                    'user_id'     => (int) $r->user_id,
                ], $base ), 'aaa_oc_annc_ack_reset_one_' . (int) $annc_id . '_' . (int) $r->user_id );

                echo '<tr>';
                echo '<td>' . ( $user_link ? '<a href="' . esc_url( $user_link ) . '">' . esc_html( $name ) . '</a>' : esc_html( $name ) ) . ' (ID ' . (int) $r->user_id . ')</td>';
                echo '<td>' . esc_html( $r->user_email ) . '</td>';
                echo '<td>' . esc_html( $this->fmt_dt( $r->seen_at ) ) . '</td>';
                echo '<td>' . $accepted . '</td>';
                echo '<td>' . esc_html( $this->fmt_dt( $r->accepted_at ) ) . '</td>';
                echo '<td><a class="button" href="' . esc_url( $reset_url ) . '" onclick="return confirm(\'' . esc_js( __( 'Reset acceptance for this user?', 'aaa-order-workflow' ) ) . '\');">' . esc_html__( 'Reset', 'aaa-order-workflow' ) . '</a></td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
    }

    /** ---------------------------
     *  Actions (CRUD)
     *  --------------------------- */
    protected function maybe_handle_actions() {
        if ( isset( $_GET['annc_action'] ) && $_GET['annc_action'] === 'delete' && isset( $_GET['annc_id'] ) ) {
            return $this->handle_delete( absint( $_GET['annc_id'] ) );
        }
        if ( empty( $_POST['annc_action'] ) ) {
            return '';
        }
        check_admin_referer( 'aaa_oc_annc_save', 'aaa_oc_annc_nonce' );

    $action  = sanitize_text_field( wp_unslash( $_POST['annc_action'] ) );
    // ✅ Fix: unslash the title before sanitizing so quotes don’t accumulate backslashes
    $title   = isset( $_POST['annc_title'] )   ? sanitize_text_field( wp_unslash( $_POST['annc_title'] ) ) : '';
    $content = isset( $_POST['annc_content'] ) ? wp_kses_post( wp_unslash( $_POST['annc_content'] ) ) : '';

    // Also unslash date inputs (harmless but consistent)
    $start_in = isset( $_POST['annc_start'] ) ? sanitize_text_field( wp_unslash( $_POST['annc_start'] ) ) : '';
    $end_in   = isset( $_POST['annc_end'] )   ? sanitize_text_field( wp_unslash( $_POST['annc_end'] ) )   : '';
    $start    = $this->from_input_datetime( $start_in );
    $end      = $this->from_input_datetime( $end_in );

    $active  = isset( $_POST['annc_active'] ) ? 1 : 0;

    if ( $action === 'create' ) {
        $this->create_announcement( $title, $content, $start, $end, $active );
        return __( 'Announcement created.', 'aaa-order-workflow' );
    } elseif ( $action === 'update' && isset( $_POST['annc_id'] ) ) {
        $this->update_announcement( absint( $_POST['annc_id'] ), $title, $content, $start, $end, $active );
        return __( 'Announcement updated.', 'aaa-order-workflow' );
    }

    return '';
}

    protected function handle_delete( $id ) {
        if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'aaa_oc_annc_delete_' . (int) $id ) ) {
            wp_die( esc_html__( 'Invalid request.', 'aaa-order-workflow' ) );
        }
        global $wpdb;
        $t = $this->table_names();
        $wpdb->delete( $t['ann'], [ 'id' => $id ], [ '%d' ] );
        $wpdb->delete( $t['usr'], [ 'announcement_id' => $id ], [ '%d' ] );

        if ( function_exists( 'aaa_oc_log' ) ) {
            aaa_oc_log( '[ANN] Deleted announcement id=' . (int) $id );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=' . $this->screen_slug . '&deleted=1' ) );
        exit;
    }

    protected function create_announcement( $title, $content, $start, $end, $active ) {
        global $wpdb;
        $t = $this->table_names();
        $wpdb->insert(
            $t['ann'],
            [
                'title'      => $title,
                'content'    => $content,
                'start_at'   => $start,
                'end_at'     => $end,
                'is_active'  => $active,
                'created_by' => get_current_user_id(),
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ]
        );
        if ( function_exists( 'aaa_oc_log' ) ) {
            aaa_oc_log( '[ANN] Created id=' . (int) $wpdb->insert_id . ' title="' . $title . '"' );
        }
    }

    protected function update_announcement( $id, $title, $content, $start, $end, $active ) {
        global $wpdb;
        $t = $this->table_names();
        $wpdb->update(
            $t['ann'],
            [
                'title'     => $title,
                'content'   => $content,
                'start_at'  => $start,
                'end_at'    => $end,
                'is_active' => $active,
                'updated_at'=> current_time( 'mysql' ),
            ],
            [ 'id' => $id ],
            [ '%s', '%s', '%s', '%s', '%d', '%s' ],
            [ '%d' ]
        );
        if ( function_exists( 'aaa_oc_log' ) ) {
            aaa_oc_log( '[ANN] Updated id=' . (int) $id . ' title="' . $title . '"' );
        }
    }

    protected function get_announcement( $id ) {
        global $wpdb;
        $t = $this->table_names();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t['ann']} WHERE id = %d", $id ) );
    }

    /** ---------------------------
     *  Ack actions (reset/export)
     *  --------------------------- */
    protected function maybe_handle_ack_actions() {
        if ( empty( $_GET['ack_action'] ) ) {
            return '';
        }
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return '';
        }

        $action   = sanitize_text_field( $_GET['ack_action'] );
        $annc_id  = isset( $_GET['ack_annc_id'] ) ? absint( $_GET['ack_annc_id'] ) : 0;
        $base     = admin_url( 'admin.php?page=' . $this->screen_slug . '&ack_annc_id=' . (int) $annc_id );

        if ( $action === 'reset_one' ) {
            $user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
            if ( $annc_id && $user_id && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'aaa_oc_annc_ack_reset_one_' . (int) $annc_id . '_' . (int) $user_id ) ) {
                global $wpdb;
                $t = $this->table_names();
                $wpdb->update(
                    $t['usr'],
                    [ 'accepted' => 0, 'accepted_at' => null ],
                    [ 'announcement_id' => $annc_id, 'user_id' => $user_id ],
                    [ '%d', '%s' ],
                    [ '%d', '%d' ]
                );
                if ( function_exists( 'aaa_oc_log' ) ) {
                    aaa_oc_log( sprintf( '[ANN][ACK] Reset user=%d annc=%d', (int) $user_id, (int) $annc_id ) );
                }
                wp_safe_redirect( $base . '&ack_reset_one=1' );
                exit;
            }
        }

        if ( $action === 'reset_all' ) {
            if ( $annc_id && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'aaa_oc_annc_ack_reset_all_' . (int) $annc_id ) ) {
                global $wpdb;
                $t = $this->table_names();
                $wpdb->update(
                    $t['usr'],
                    [ 'accepted' => 0, 'accepted_at' => null ],
                    [ 'announcement_id' => $annc_id ],
                    [ '%d', '%s' ],
                    [ '%d' ]
                );
                if ( function_exists( 'aaa_oc_log' ) ) {
                    aaa_oc_log( sprintf( '[ANN][ACK] Reset ALL annc=%d', (int) $annc_id ) );
                }
                wp_safe_redirect( $base . '&ack_reset_all=1' );
                exit;
            }
        }

        if ( $action === 'export_csv' ) {
            if ( $annc_id && wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'aaa_oc_annc_ack_export_' . (int) $annc_id ) ) {
                $this->export_csv( $annc_id );
                exit;
            }
        }

        return '';
    }

    protected function export_csv( $annc_id ) {
        global $wpdb;
        $t = $this->table_names();

        $rows = $wpdb->get_results( $wpdb->prepare("
            SELECT u.user_id, wu.user_login, wu.user_email,
                   umd.meta_value AS display_name,
                   u.seen_at, u.accepted, u.accepted_at
            FROM {$t['usr']} u
            LEFT JOIN {$wpdb->users} wu ON wu.ID = u.user_id
            LEFT JOIN {$wpdb->usermeta} umd ON umd.user_id = u.user_id AND umd.meta_key = 'display_name'
            WHERE u.announcement_id = %d
            ORDER BY u.accepted DESC, u.accepted_at DESC, u.seen_at DESC
        ", $annc_id ), ARRAY_A );

        $filename = 'announcement_ack_' . (int) $annc_id . '_' . date('Ymd_His') . '.csv';
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, [ 'User ID', 'Display Name', 'Username', 'Email', 'Seen At', 'Accepted', 'Accepted At' ] );
        foreach ( $rows as $r ) {
            fputcsv( $out, [
                $r['user_id'],
                $r['display_name'],
                $r['user_login'],
                $r['user_email'],
                $r['seen_at'],
                $r['accepted'] ? 'Yes' : 'No',
                $r['accepted_at'],
            ] );
        }
        fclose( $out );
    }

    /** ---------------------------
     *  Helpers
     *  --------------------------- */
    protected function get_announcements_list() {
        global $wpdb;
        $t    = $this->table_names();
        $rows = $wpdb->get_results( "SELECT id, title FROM {$t['ann']} ORDER BY id DESC" );
        $out  = [];
        foreach ( (array) $rows as $r ) {
            $out[ (int) $r->id ] = $r->title;
        }
        return $out;
    }

    protected function to_input_datetime( $mysql_dt ) {
        if ( empty( $mysql_dt ) || $mysql_dt === '0000-00-00 00:00:00' ) {
            return '';
        }
        $ts = strtotime( $mysql_dt );
        if ( ! $ts ) {
            return '';
        }
        return date( 'Y-m-d\TH:i', $ts );
    }

    protected function from_input_datetime( $input ) {
        $input = trim( (string) $input );
        if ( $input === '' ) {
            return null;
        }
        $input = str_replace( 'T', ' ', $input );
        if ( preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $input ) ) {
            $input .= ':00';
        }
        return $input;
    }

    protected function fmt_dt( $mysql_dt ) {
        if ( empty( $mysql_dt ) ) {
            return '—';
        }
        $ts = strtotime( $mysql_dt );
        return $ts ? date_i18n( 'M j, Y g:i a', $ts ) : esc_html( $mysql_dt );
    }
}

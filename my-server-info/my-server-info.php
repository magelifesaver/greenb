<?php
/*
Plugin Name: My Server Info
Description: Displays server and site information (PHP Version, MySQL Version, WordPress Memory Limit, WP memory, PHP Execution Time, post_max_size, CPU usage, Disk usage, Uptime) and allows adding selected parameters to the admin bar.
Version: 1.5.1
Author: Anton Simonov
Tags: php version, MySQL Version, WordPress Memory Limit, WP memory, PHP Execution Time, post_max_size, CPU usage, Disk usage, uptime, admin bar
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_enqueue_scripts', 'myserverinfo_admin_styles' );
function myserverinfo_admin_styles( $hook ) {
    if ( $hook !== 'toplevel_page_my-server-info' ) {
        return;
    }
    wp_enqueue_style( 'myserverinfo-admin-styles', plugin_dir_url( __FILE__ ) . 'assets/admin-styles.css', array(), '1.5.1' );
}

add_action( 'admin_menu', 'myserverinfo_add_admin_menu' );
function myserverinfo_add_admin_menu() {
    add_menu_page( 'My Server Info', 'My Server Info', 'manage_options', 'my-server-info', 'myserverinfo_display', 'dashicons-admin-tools', 80 );
}

function myserverinfo_get_public_ip() {
    $response = wp_remote_get( 'https://api.ipify.org?format=json' );
    if ( is_wp_error( $response ) ) {
        return 'Unavailable';
    }
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    return isset( $data['ip'] ) ? esc_html( $data['ip'] ) : 'Unavailable';
}

function myserverinfo_get_cpu_usage() {
    if (stristr(PHP_OS, 'WIN')) {
        return 'Unavailable';
    } else {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load && is_array($load)) {
                $num_cores = 1;
                if (function_exists('shell_exec')) {
                    $cores = shell_exec('nproc');
                    if ($cores) {
                        $num_cores = intval(trim($cores));
                        if ($num_cores < 1) {
                            $num_cores = 1;
                        }
                    }
                }
                $cpu_usage = round( ($load[0] / $num_cores) * 100 );
                if ($cpu_usage > 100) {
                    $cpu_usage = 100;
                }
                return $cpu_usage;
            }
        }
        return 'Unavailable';
    }
}

function myserverinfo_get_disk_usage() {
    $root_directory = ABSPATH;
    $total_space = @disk_total_space( $root_directory );
    $free_space = @disk_free_space( $root_directory );

    if ( $total_space === false || $free_space === false ) {
        return 'Unavailable';
    }

    $used_space = $total_space - $free_space;
    $disk_usage_percent = ($total_space > 0) ? round( ($used_space / $total_space) * 100 ) : 'Unavailable';

    if ( is_numeric($disk_usage_percent) && $disk_usage_percent > 100 ) {
        $disk_usage_percent = 100;
    }

    return is_numeric($disk_usage_percent) ? $disk_usage_percent : 'Unavailable';
}

function myserverinfo_get_uptime() {
    if (stristr(PHP_OS, 'WIN')) {
        return 'Unavailable';
    }
    $uptime_file = '/proc/uptime';
    if (file_exists($uptime_file) && is_readable($uptime_file)) {
        $uptime_contents = file_get_contents($uptime_file);
        if ($uptime_contents !== false) {
            $uptime_parts = explode(" ", trim($uptime_contents));
            if (isset($uptime_parts[0])) {
                $uptime_seconds = (int) $uptime_parts[0];
                $days = floor($uptime_seconds / 86400);
                $hours = floor(($uptime_seconds % 86400) / 3600);
                $minutes = floor(($uptime_seconds % 3600) / 60);
                $seconds = $uptime_seconds % 60;
                $uptime_str = ($days > 0 ? $days . " days, " : "") . sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                return $uptime_str;
            }
        }
    }
    return 'Unavailable';
}

add_action('admin_post_myserverinfo_save_preferences', 'myserverinfo_save_preferences');
function myserverinfo_save_preferences() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized user' );
    }

    check_admin_referer( 'myserverinfo_save_preferences_nonce', 'myserverinfo_nonce' );

    $user_id = get_current_user_id();

    $add_mem = isset( $_POST['add_mem'] ) ? 1 : 0;
    $add_cpu = isset( $_POST['add_cpu'] ) ? 1 : 0;
    $add_disk = isset( $_POST['add_disk'] ) ? 1 : 0;

    update_user_meta( $user_id, 'myserverinfo_add_mem', $add_mem );
    update_user_meta( $user_id, 'myserverinfo_add_cpu', $add_cpu );
    update_user_meta( $user_id, 'myserverinfo_add_disk', $add_disk );

    $redirect_url = add_query_arg( array(
        'page' => 'my-server-info',
        'myserverinfo_status' => 'success'
    ), admin_url( 'admin.php' ) );

    wp_redirect( $redirect_url );
    exit;
}

function myserverinfo_display() {
    global $wpdb;

    if ( isset( $_GET['myserverinfo_status'] ) && $_GET['myserverinfo_status'] === 'success' ) {
        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if ( wp_verify_nonce( $nonce, 'myserverinfo_save_preferences_nonce' ) ) {
            echo '<div class="notice notice-success is-dismissible">
                    <p>Settings saved successfully.</p>
                  </div>';
        }
    }

    $user_id = get_current_user_id();
    $add_mem = get_user_meta( $user_id, 'myserverinfo_add_mem', true );
    $add_cpu = get_user_meta( $user_id, 'myserverinfo_add_cpu', true );
    $add_disk = get_user_meta( $user_id, 'myserverinfo_add_disk', true );

    $add_mem = $add_mem ? 1 : 0;
    $add_cpu = $add_cpu ? 1 : 0;
    $add_disk = $add_disk ? 1 : 0;

    $php_version = phpversion();
    $mysql_version = $wpdb->db_version();
    $memory_limit = WP_MEMORY_LIMIT;
    $execution_time = ini_get( 'max_execution_time' );
    $max_input_vars = ini_get( 'max_input_vars' );
    $post_max_size = ini_get( 'post_max_size' );
    $upload_max_filesize = ini_get( 'upload_max_filesize' );

    $site_ip = myserverinfo_get_public_ip();
    $site_timezone = wp_timezone_string();
    $current_time = current_time( 'Y-m-d H:i:s' );
    $formatted_timezone = new DateTimeZone( $site_timezone );
    $timezone_offset = $formatted_timezone->getOffset( new DateTime( 'now', $formatted_timezone ) ) / 3600;
    $timezone_display = 'UTC' . ( $timezone_offset >= 0 ? '+' : '' ) . $timezone_offset;

    $recommended_values = array(
        'php_version'         => '7.4 or higher',
        'mysql_version'       => '5.7 or higher',
        'memory_limit'        => '256M or higher',
        'execution_time'      => '300 seconds or higher',
        'max_input_vars'      => '3000 or higher',
        'post_max_size'       => '64M or higher',
        'upload_max_filesize' => '64M or higher',
    );

    $memory_usage = memory_get_usage();
    $memory_limit_bytes = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
    if ( $memory_limit_bytes > 0 ) {
        $memory_usage_percent = round( ( $memory_usage / $memory_limit_bytes ) * 100 );
        if ( $memory_usage_percent > 100 ) {
            $memory_usage_percent = 100;
        }
    } else {
        $memory_usage_percent = 'Unavailable';
    }

    $cpu_usage_percent = myserverinfo_get_cpu_usage();
    $disk_usage_percent = myserverinfo_get_disk_usage();
    $server_uptime = myserverinfo_get_uptime();

    echo '<div class="myserverinfo-wrap">';
    echo '<h1>My Server Info</h1>';

    echo '<form method="post" action="' . esc_url( admin_url('admin-post.php') ) . '">';
    echo '<input type="hidden" name="action" value="myserverinfo_save_preferences">';
    wp_nonce_field( 'myserverinfo_save_preferences_nonce', 'myserverinfo_nonce' );

    echo '<h2>Resource Usage</h2>';
    echo '<div class="resource-usage-container">';
        
        echo '<div class="resource-usage-item">';
        if ( $memory_usage_percent !== 'Unavailable' ) {
            echo '<div class="resource-usage-title">Memory Usage</div>';
            echo '<div role="progressbar" aria-valuenow="' . esc_attr( $memory_usage_percent ) . '" aria-valuemin="0" aria-valuemax="100" style="--value: ' . esc_attr( $memory_usage_percent ) . ';"></div>';
            echo '<p class="resource-usage-text">Used Memory: ' . esc_html( $memory_usage_percent ) . '% of ' . esc_html( $memory_limit ) . '</p>';
            echo '<p><label><input type="checkbox" name="add_mem" value="1" ' . checked( 1, $add_mem, false ) . '> Add to admin bar</label></p>';
        } else {
            echo '<p>Memory usage information is unavailable.</p>';
        }
        echo '</div>';

        echo '<div class="resource-usage-item">';
        if ( $cpu_usage_percent !== 'Unavailable' ) {
            echo '<div class="resource-usage-title">CPU Usage (1 min)</div>';
            echo '<div role="progressbar" aria-valuenow="' . esc_attr( $cpu_usage_percent ) . '" aria-valuemin="0" aria-valuemax="100" style="--value: ' . esc_attr( $cpu_usage_percent ) . ';"></div>';
            echo '<p class="resource-usage-text">Used CPU: ' . esc_html( $cpu_usage_percent ) . '%</p>';
            echo '<p><label><input type="checkbox" name="add_cpu" value="1" ' . checked( 1, $add_cpu, false ) . '> Add to admin bar</label></p>';
        } else {
            echo '<p>CPU usage information is unavailable.</p>';
        }
        echo '</div>';

        echo '<div class="resource-usage-item">';
        if ( $disk_usage_percent !== 'Unavailable' ) {
            echo '<div class="resource-usage-title">Disk Usage</div>';
            echo '<div role="progressbar" aria-valuenow="' . esc_attr( $disk_usage_percent ) . '" aria-valuemin="0" aria-valuemax="100" style="--value: ' . esc_attr( $disk_usage_percent ) . ';"></div>';
            echo '<p class="resource-usage-text">Used Disk: ' . esc_html( $disk_usage_percent ) . '%</p>';
            echo '<p><label><input type="checkbox" name="add_disk" value="1" ' . checked( 1, $add_disk, false ) . '> Add to admin bar</label></p>';
        } else {
            echo '<p>Disk usage information is unavailable.</p>';
        }
        echo '</div>';

    echo '</div>';

    echo '<p class="submit"><input type="submit" class="button-primary" value="Save Settings"></p>';

    echo '</form>';

    echo '<h2>Server Information</h2>';
    echo '<table class="myserverinfo-table">';
    echo '<thead>
            <tr>
                <th>Parameter</th>
                <th>Current Value</th>
                <th>Recommended Value</th>
            </tr>
          </thead>';
    echo '<tbody>';
    echo '<tr>
            <td><strong>PHP Version</strong></td>
            <td>' . esc_html( $php_version ) . '</td>
            <td>' . esc_html( $recommended_values['php_version'] ) . '</td>
          </tr>';
    echo '<tr>
            <td><strong>MySQL Version</strong></td>
            <td>' . esc_html( $mysql_version ) . '</td>
            <td>' . esc_html( $recommended_values['mysql_version'] ) . '</td>
          </tr>';
    echo '<tr>
            <td><strong>WP Memory Limit</strong></td>
            <td>' . esc_html( $memory_limit ) . '</td>
            <td>' . esc_html( $recommended_values['memory_limit'] ) . '</td>
          </tr>';
    echo '<tr>
            <td><strong>PHP Execution Time</strong></td>
            <td>' . esc_html( $execution_time ) . ' seconds</td>
            <td>' . esc_html( $recommended_values['execution_time'] ) . '</td>
          </tr>';
    echo '<tr>
            <td><strong>PHP Max Input Vars</strong></td>
            <td>' . esc_html( $max_input_vars ) . '</td>
            <td>' . esc_html( $recommended_values['max_input_vars'] ) . '</td>
          </tr>';
    echo '<tr>
            <td><strong>PHP post_max_size</strong></td>
            <td>' . esc_html( $post_max_size ) . '</td>
            <td>' . esc_html( $recommended_values['post_max_size'] ) . '</td>
          </tr>';
    echo '<tr>
            <td><strong>PHP upload_max_filesize</strong></td>
            <td>' . esc_html( $upload_max_filesize ) . '</td>
            <td>' . esc_html( $recommended_values['upload_max_filesize'] ) . '</td>
          </tr>';
    echo '<tr>
            <td><strong>Server Uptime</strong></td>
            <td>' . esc_html( $server_uptime ) . '</td>
            <td>N/A</td>
          </tr>';
    echo '</tbody>';
    echo '</table>';

    echo '<h2>Site Data</h2>';
    echo '<table class="myserverinfo-table">';
    echo '<thead>
            <tr>
                <th>Parameter</th>
                <th>Value</th>
            </tr>
          </thead>';
    echo '<tbody>';
    echo '<tr>
            <td><strong>Site IP Address</strong></td>
            <td>' . esc_html( $site_ip ) . '</td>
          </tr>';
    echo '<tr>
            <td><strong>Site Time & Timezone</strong></td>
            <td>' . esc_html( $current_time ) . ' (' . esc_html( $timezone_display ) . ')</td>
          </tr>';
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

add_action( 'admin_bar_menu', 'myserverinfo_add_admin_bar_items', 100 );
function myserverinfo_add_admin_bar_items( $wp_admin_bar ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $user_id = get_current_user_id();

    $add_mem = get_user_meta( $user_id, 'myserverinfo_add_mem', true );
    $add_cpu = get_user_meta( $user_id, 'myserverinfo_add_cpu', true );
    $add_disk = get_user_meta( $user_id, 'myserverinfo_add_disk', true );

    if ( $add_mem ) {
        $memory_limit = WP_MEMORY_LIMIT;
        $memory_usage = memory_get_usage();
        $memory_limit_bytes = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
        if ( $memory_limit_bytes > 0 ) {
            $memory_usage_percent = round( ( $memory_usage / $memory_limit_bytes ) * 100 );
            if ( $memory_usage_percent > 100 ) {
                $memory_usage_percent = 100;
            }
            $memory_display = 'MEM: ' . $memory_usage_percent . '%';
        } else {
            $memory_display = 'MEM: Unavailable';
        }

        $wp_admin_bar->add_node( array(
            'id'    => 'myserverinfo_mem',
            'title' => $memory_display,
            'meta'  => array(
                'class' => 'myserverinfo-adminbar-item',
                'title' => 'Memory Usage',
            ),
        ));
    }

    if ( $add_cpu ) {
        $cpu_usage_percent = myserverinfo_get_cpu_usage();
        if ( $cpu_usage_percent !== 'Unavailable' ) {
            $cpu_display = 'AVG CPU: ' . $cpu_usage_percent . '%';
        } else {
            $cpu_display = 'AVG CPU: Unavailable';
        }

        $wp_admin_bar->add_node( array(
            'id'    => 'myserverinfo_cpu',
            'title' => $cpu_display,
            'meta'  => array(
                'class' => 'myserverinfo-adminbar-item',
                'title' => 'CPU Usage',
            ),
        ));
    }

    if ( $add_disk ) {
        $disk_usage_percent = myserverinfo_get_disk_usage();
        if ( $disk_usage_percent !== 'Unavailable' ) {
            $disk_display = 'Disk: ' . $disk_usage_percent . '%';
        } else {
            $disk_display = 'Disk: Unavailable';
        }

        $wp_admin_bar->add_node( array(
            'id'    => 'myserverinfo_disk',
            'title' => $disk_display,
            'meta'  => array(
                'class' => 'myserverinfo-adminbar-item',
                'title' => 'Disk Usage',
            ),
        ));
    }
}
require_once plugin_dir_path(__FILE__) . 'includes/class-myserverinfo-deactivation-survey.php';
MyServerInfo_Deactivation_Survey::init( __FILE__ );
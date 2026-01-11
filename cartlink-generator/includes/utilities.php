<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function gvnclg_set_pseudo_transient( $key, $value, $expire_in_seconds = 3600 ) {
    if ( defined( 'GUAVEN_CARTLINK_GENERATOR_USE_REAL_TRANSIENTS' ) ) {
        set_transient( $key, $value, $expire_in_seconds );
    } else {
        global $wp_filesystem;

        // Ensure WP_Filesystem is loaded
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Force direct method for file handling
        add_filter( 'filesystem_method', function() {
            return 'direct';
        } );

        // Initialize WP_Filesystem
        if ( ! WP_Filesystem() ) {
            return false;
        }

        $dir = GUAVEN_CARTLINK_GENERATOR_DATA_DIR;

        if ( ! $wp_filesystem->is_dir( $dir ) ) {
            if ( ! $wp_filesystem->mkdir( $dir ) ) {
                return false;
            }
        }

        $file_path = trailingslashit( $dir ) . sanitize_file_name( $key ) . '.json';
        $expire_file_path = trailingslashit( $dir ) . sanitize_file_name( $key ) . '_expire.json';

        // Save value file
        $file_contents = wp_json_encode( $value );
        if ( ! $wp_filesystem->put_contents( $file_path, $file_contents, FS_CHMOD_FILE ) ) {
            return false;
        }

        // Save expiration file
        $expire_timestamp = time() + $expire_in_seconds;
        if ( ! $wp_filesystem->put_contents( $expire_file_path, wp_json_encode( $expire_timestamp ), FS_CHMOD_FILE ) ) {
            return false;
        }

        return true;
    }
}


function gvnclg_get_pseudo_transient( $key ) {
    if ( defined( 'GUAVEN_CARTLINK_GENERATOR_USE_REAL_TRANSIENTS' ) ) {
        $value = get_transient( $key );
        return $value !== false ? $value : false;
    } else {
        global $wp_filesystem;

        // Initialize WP_Filesystem
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        add_filter( 'filesystem_method', function() {
            return 'direct';
        } );

        if ( ! WP_Filesystem() ) {
            return false;
        }

        $dir = GUAVEN_CARTLINK_GENERATOR_DATA_DIR;
        $file_path = trailingslashit( $dir ) . sanitize_file_name( $key ) . '.json';
        $expire_file_path = trailingslashit( $dir ) . sanitize_file_name( $key ) . '_expire.json';

        // Check expiration file
        if ( $wp_filesystem->exists( $expire_file_path ) ) {
            $expire_contents = $wp_filesystem->get_contents( $expire_file_path );
            $expire_timestamp = json_decode( $expire_contents, true );

            if ( time() > $expire_timestamp ) {
                return false; // Expired
            }
        }

        // Read value file
        if ( $wp_filesystem->exists( $file_path ) ) {
            $file_contents = $wp_filesystem->get_contents( $file_path );
            return json_decode( $file_contents, true );
        }

        return false;
    }
}

function gvnclg_delete_old_autogenerate_files() {
    global $wp_filesystem;

    // Ensure WP_Filesystem is loaded
    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    // Force direct method for file handling
    add_filter( 'filesystem_method', function() {
        return 'direct';
    } );

    // Initialize WP_Filesystem
    if ( ! WP_Filesystem() ) {
        echo esc_html( 'Failed to initialize WP_Filesystem.','cartlink-generator' );
        return false;
    }

    $dir = GUAVEN_CARTLINK_GENERATOR_DATA_DIR;

    if ( ! $wp_filesystem->is_dir( $dir ) ) {
        echo esc_html( 'GUAVEN_CARTLINK_GENERATOR_DATA_DIR does not exist or is not a directory.' ,'cartlink-generator');
        return false;
    }

    $files = $wp_filesystem->dirlist( $dir );
    if ( ! $files ) {
        echo esc_html( 'Failed to list files in GUAVEN_CARTLINK_GENERATOR_DATA_DIR.','cartlink-generator' ).PHP_EOL;
        return false;
    }

    $now = time();
    $files_deleted = 0;

    foreach ( $files as $file_name => $file_info ) {
        if ( strpos( $file_name, '_expire.json' ) === false ) {
            continue;
        }

        $expire_file_path = trailingslashit( $dir ) . $file_name;
        $value_file_path = str_replace( '_expire.json', '.json', $expire_file_path );

        // Read expiration timestamp
        $expire_contents = $wp_filesystem->get_contents( $expire_file_path );
        if ( $expire_contents !== false ) {
            $expire_timestamp = json_decode( $expire_contents, true );

            // Delete files if expired
            if ( $expire_timestamp < $now ) {
                if ( $wp_filesystem->delete( $expire_file_path ) && $wp_filesystem->delete( $value_file_path ) ) {
                    $files_deleted++;
                } else {
                    echo esc_html( 'Failed to delete files: ' . $expire_file_path . ' or ' . $value_file_path );
                }
            }
        } else {
            echo  esc_html('Failed to read expiration file: ' . $expire_file_path) .PHP_EOL;
        }
    }

    return true;
}

// Schedule directory cleanup daily
add_action( 'wp_scheduled_delete', 'gvnclg_delete_old_autogenerate_files',99999 );
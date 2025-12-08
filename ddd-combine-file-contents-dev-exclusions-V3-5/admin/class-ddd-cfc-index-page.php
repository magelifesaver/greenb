<?php
/**
 * File Path: admin/class-ddd-cfc-index-page.php
 * Purpose: Live Search Index admin submenu (index tools + plugin include options).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'DDD_CFC_Index_Page' ) ) :

class DDD_CFC_Index_Page {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
    }

    public static function register_page() {
        add_submenu_page(
            'ddd-cfc-settings',
            __( 'Live Search Index', 'ddd-cfc' ),
            __( 'Live Search Index', 'ddd-cfc' ),
            'manage_options',
            'cfc-live-search-index',
            array( __CLASS__, 'render' )
        );
    }

    /**
     * Return an array of all plugin folder slugs => names.
     *
     * @return array
     */
    protected static function get_all_plugin_slugs() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins   = get_plugins();
        $all_slugs = array();
        foreach ( $plugins as $file => $data ) {
            $slug = dirname( $file );
            if ( '.' === $slug ) {
                $slug = $file;
            }
            $all_slugs[ $slug ] = isset( $data['Name'] ) ? $data['Name'] : $slug;
        }
        ksort( $all_slugs, SORT_NATURAL | SORT_FLAG_CASE );
        return $all_slugs;
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $all_slugs = self::get_all_plugin_slugs();
        $excluded  = DDD_CFC_Exclusions::get_list();
        $mu_flag   = get_option( 'cfc_include_mu_plugins', 'no' );

        if ( isset( $_POST['ddd_cfc_index_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ddd_cfc_index_nonce'] ) ), 'ddd_cfc_index_save' )
        ) {
            // Tree source option.
            $source = isset( $_POST['cfc_tree_source'] ) ? sanitize_text_field( wp_unslash( $_POST['cfc_tree_source'] ) ) : 'realtime';
            if ( ! in_array( $source, array( 'realtime', 'indexed' ), true ) ) {
                $source = 'realtime';
            }
            update_option( 'cfc_tree_source', $source );

            // MU-plugins include flag.
            $mu_flag = ! empty( $_POST['cfc_include_mu_plugins'] ) ? 'yes' : 'no';
            update_option( 'cfc_include_mu_plugins', $mu_flag );

            // Plugin inclusion list -> convert to exclusions for the indexer.
            $included = isset( $_POST['ddd_cfc_included_plugins'] )
                ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['ddd_cfc_included_plugins'] ) )
                : array();

            $included = array_values( array_intersect( $included, array_keys( $all_slugs ) ) );
            $excluded = array_values( array_diff( array_keys( $all_slugs ), $included ) );

            DDD_CFC_Exclusions::set_list( $excluded );

            echo '<div class="updated"><p>' . esc_html__( 'Settings saved. Please Reindex to apply changes.', 'ddd-cfc' ) . '</p></div>';
        }

        $current_source = get_option( 'cfc_tree_source', 'realtime' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Live Search Index', 'ddd-cfc' ); ?></h1>

            <p id="cfc-ls-index-msg"></p>
            <p>
                <button id="cfc-ls-reindex" class="button button-primary"><?php esc_html_e( 'Rebuild Index', 'ddd-cfc' ); ?></button>
                <button id="cfc-ls-sync" class="button"><?php esc_html_e( 'Sync Index', 'ddd-cfc' ); ?></button>
                <button id="cfc-ls-clear" class="button"><?php esc_html_e( 'Clear Index', 'ddd-cfc' ); ?></button>
            </p>

            <form method="post">
                <?php wp_nonce_field( 'ddd_cfc_index_save', 'ddd_cfc_index_nonce' ); ?>

                <h2><?php esc_html_e( 'Tree Source', 'ddd-cfc' ); ?></h2>
                <p><?php esc_html_e( 'Choose how to load the directory tree:', 'ddd-cfc' ); ?></p>
                <select id="cfc-tree-source" name="cfc_tree_source">
                    <option value="realtime" <?php selected( $current_source, 'realtime' ); ?>>
                        <?php esc_html_e( 'Real Time (scan filesystem)', 'ddd-cfc' ); ?>
                    </option>
                    <option value="indexed" <?php selected( $current_source, 'indexed' ); ?>>
                        <?php esc_html_e( 'Indexed (use database)', 'ddd-cfc' ); ?>
                    </option>
                </select>

                <h2><?php esc_html_e( 'MU-Plugins', 'ddd-cfc' ); ?></h2>
                <p>
                    <label>
                        <input type="checkbox" name="cfc_include_mu_plugins" value="1" <?php checked( $mu_flag, 'yes' ); ?> />
                        <?php esc_html_e( 'Include files from wp-content/mu-plugins when building the tree/index.', 'ddd-cfc' ); ?>
                    </label>
                </p>

                <h2><?php esc_html_e( 'Plugin Inclusion', 'ddd-cfc' ); ?></h2>
                <p><?php esc_html_e( 'Check the plugins whose files should be INCLUDED in the combined tree and index. Unchecked plugins are excluded.', 'ddd-cfc' ); ?></p>

                <div style="max-height:320px;overflow:auto;border:1px solid #ccd0d4;padding:8px;background:#fff;">
                    <?php foreach ( $all_slugs as $slug => $name ) : ?>
                        <?php $checked = ! in_array( $slug, $excluded, true ); ?>
                        <label style="display:block;margin-bottom:4px;">
                            <input
                                type="checkbox"
                                name="ddd_cfc_included_plugins[]"
                                value="<?php echo esc_attr( $slug ); ?>"
                                <?php checked( $checked ); ?>
                            />
                            <strong><?php echo esc_html( $name ); ?></strong>
                            <code style="opacity:0.75;"><?php echo esc_html( $slug ); ?></code>
                        </label>
                    <?php endforeach; ?>
                </div>

                <p>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e( 'Save Settings', 'ddd-cfc' ); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}

endif;

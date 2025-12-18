<?php
/**
 * Administrative interface for AAA Geo Business Mapper. This class registers
 * options for the API keys, adds a submenu page under Tools, and renders
 * the settings screen containing the map and controls. All WordPress‑related
 * hooks live here to keep separation from AJAX and asset modules.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_GBM_Admin {

    /**
     * When true, additional debug messages are logged.
     */
    const DEBUG_THIS_FILE = true;

    /**
     * Option names used to store the API keys. These constants allow
     * reuse across modules without risk of typos.
     */
    const OPT_BROWSER_KEY = 'aaa_gbm_browser_api_key';
    const OPT_SERVER_KEY  = 'aaa_gbm_server_api_key';

    /**
     * Register WordPress hooks. Called during plugins_loaded.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    /**
     * Add the submenu page under Tools. Only administrators can access it.
     */
    public static function menu() {
        add_submenu_page(
            'tools.php',
            'AAA Business Mapper',
            'AAA Business Mapper',
            'manage_options',
            'aaa-gbm',
            array( __CLASS__, 'render' )
        );
    }

    /**
     * Register our two API keys with the settings API. Both are simple text
     * fields; sanitisation occurs via sanitize_text_field.
     */
    public static function register_settings() {
        register_setting( 'aaa_gbm_settings', self::OPT_BROWSER_KEY, array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'aaa_gbm_settings', self::OPT_SERVER_KEY,  array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
    }

    /**
     * Render the plugin settings page. This outputs the map wrapper,
     * controls panel and status areas. The actual functionality is
     * implemented client‑side via JavaScript.
     */
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $browser_key = get_option( self::OPT_BROWSER_KEY, '' );
        $server_key  = get_option( self::OPT_SERVER_KEY, '' );

        if ( self::DEBUG_THIS_FILE ) {
            AAA_GBM_Logger::log( 'Render admin page', array( 'has_browser_key' => (bool) $browser_key, 'has_server_key' => (bool) $server_key ) );
        }
        ?>
        <div class="wrap">
            <h1>AAA Business Mapper</h1>

            <form method="post" action="options.php" class="aaa-gbm-settings">
                <?php settings_fields( 'aaa_gbm_settings' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr( self::OPT_BROWSER_KEY ); ?>">Browser Maps JS API Key</label></th>
                        <td><input type="text" class="regular-text" id="<?php echo esc_attr( self::OPT_BROWSER_KEY ); ?>" name="<?php echo esc_attr( self::OPT_BROWSER_KEY ); ?>" value="<?php echo esc_attr( $browser_key ); ?>" autocomplete="off" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr( self::OPT_SERVER_KEY ); ?>">Server Places API Key</label></th>
                        <td><input type="text" class="regular-text" id="<?php echo esc_attr( self::OPT_SERVER_KEY ); ?>" name="<?php echo esc_attr( self::OPT_SERVER_KEY ); ?>" value="<?php echo esc_attr( $server_key ); ?>" autocomplete="off" /></td>
                    </tr>
                </table>
                <?php submit_button( 'Save Keys' ); ?>
            </form>

            <?php if ( empty( $browser_key ) || empty( $server_key ) ) : ?>
                <div class="notice notice-warning"><p><strong>Keys missing.</strong> Map (browser) and Places (server) keys are required.</p></div>
            <?php endif; ?>

            <div id="aaa-gbm-app" class="aaa-gbm-app">
                <div class="aaa-gbm-panel">
                    <h2>Search Layers</h2>
                    <div class="aaa-gbm-row">
                        <label>Layer Name</label>
                        <input id="aaa-gbm-layer-name" type="text" placeholder="Gyms" />
                    </div>
                    <div class="aaa-gbm-row">
                        <label>Mode</label>
                        <select id="aaa-gbm-layer-mode">
                            <option value="nearby">Nearby (Types)</option>
                            <option value="text">Text Search</option>
                            <option value="multi-text">Multi Text (one per line)</option>
                        </select>
                    </div>
                    <div class="aaa-gbm-row">
                        <label>Types (comma separated)</label>
                        <input id="aaa-gbm-layer-types" type="text" placeholder="gym,fitness_center" />
                        <p class="aaa-gbm-note">Use valid Place types for Nearby mode. Leave blank for multi/text modes.</p>
                    </div>
                    <div class="aaa-gbm-row">
                        <label>Text Query</label>
                        <input id="aaa-gbm-layer-text" type="text" placeholder="boxing" />
                        <p class="aaa-gbm-note">For single Text search, enter one phrase. For Multi Text, leave this blank and use the textarea below.</p>
                    </div>
                    <div class="aaa-gbm-row">
                        <label>Multi Text Queries (one per line)</label>
                        <textarea id="aaa-gbm-layer-multi" placeholder="boxing\nkarate\norange theory"></textarea>
                    </div>
                    <div class="aaa-gbm-row">
                        <label>Pin Color</label>
                        <input id="aaa-gbm-layer-color" type="color" value="#1e73be" />
                    </div>
                    <div class="aaa-gbm-row">
                        <label>Weight (for scoring)</label>
                        <input id="aaa-gbm-layer-weight" type="number" min="0" step="1" value="1" />
                    </div>
                    <button class="button button-primary" id="aaa-gbm-add-layer">Add Layer</button>
                    <hr />
                    <h2>Scan Settings</h2>
                    <div class="aaa-gbm-row">
                        <label>Grid Spacing (m)</label>
                        <input id="aaa-gbm-grid-spacing" type="number" min="300" step="100" value="3000" />
                        <p class="aaa-gbm-note">Smaller spacing yields more tiles (slower/more results)</p>
                    </div>
                    <div class="aaa-gbm-row">
                        <label>Per‑Query Radius (m)</label>
                        <input id="aaa-gbm-query-radius" type="number" min="200" step="100" value="2500" />
                    </div>
                    <div class="aaa-gbm-row">
                        <label><input type="checkbox" id="aaa-gbm-adaptive" checked /> Adaptive refine</label>
                        <p class="aaa-gbm-note">Subdivide tiles that return near the max results</p>
                    </div>
                    <button class="button" id="aaa-gbm-run-scan">Run Scan (Selected Layer)</button>
                    <button class="button" id="aaa-gbm-reset">Reset Map</button>
                    <hr />
                    <h2>Cluster / Best Spot</h2>
                    <div class="aaa-gbm-row">
                        <label>Score Radius (m)</label>
                        <input id="aaa-gbm-score-radius" type="number" min="200" step="100" value="1600" />
                    </div>
                    <div class="aaa-gbm-row">
                        <label>Candidate Grid (m)</label>
                        <input id="aaa-gbm-score-grid" type="number" min="300" step="100" value="800" />
                    </div>
                    <button class="button" id="aaa-gbm-score">Find Best Spots</button>
                    <button class="button" id="aaa-gbm-toggle-heat">Toggle Heatmap</button>
                    <div id="aaa-gbm-status" class="aaa-gbm-status"></div>
                    <div id="aaa-gbm-layer-list" class="aaa-gbm-layer-list"></div>
                    <div id="aaa-gbm-best-list" class="aaa-gbm-best-list"></div>
                </div>
                <div class="aaa-gbm-mapwrap">
                    <div id="aaa-gbm-map" class="aaa-gbm-map"></div>
                </div>
            </div>
        </div>
        <?php
    }
}
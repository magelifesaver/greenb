<?php
/**
 * File Path: /wp-content/plugins/aaa-geo-business-mapper/includes/admin/class-aaa-gbm-admin.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_GBM_Admin {

    const DEBUG_THIS_FILE = true;

    const OPT_BROWSER_KEY = 'aaa_gbm_browser_api_key';
    const OPT_SERVER_KEY  = 'aaa_gbm_server_api_key';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

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

    public static function register_settings() {
        register_setting( 'aaa_gbm_settings', self::OPT_BROWSER_KEY, array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'aaa_gbm_settings', self::OPT_SERVER_KEY,  array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ) );
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $browser_key = get_option( self::OPT_BROWSER_KEY, '' );
        $server_key  = get_option( self::OPT_SERVER_KEY, '' );

        if ( self::DEBUG_THIS_FILE ) {
            AAA_GBM_Logger::log( 'Render admin page', array(
                'has_browser_key' => (bool) $browser_key,
                'has_server_key'  => (bool) $server_key,
            ) );
        }
        ?>
        <div class="wrap">
            <h1>AAA Business Mapper</h1>

            <form method="post" action="options.php" class="aaa-gbm-settings">
                <?php settings_fields( 'aaa_gbm_settings' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr( self::OPT_BROWSER_KEY ); ?>">Browser Maps JS API Key</label></th>
                        <td><input type="text" class="regular-text" id="<?php echo esc_attr( self::OPT_BROWSER_KEY ); ?>" name="<?php echo esc_attr( self::OPT_BROWSER_KEY ); ?>" value="<?php echo esc_attr( $browser_key ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr( self::OPT_SERVER_KEY ); ?>">Server Places API Key</label></th>
                        <td><input type="text" class="regular-text" id="<?php echo esc_attr( self::OPT_SERVER_KEY ); ?>" name="<?php echo esc_attr( self::OPT_SERVER_KEY ); ?>" value="<?php echo esc_attr( $server_key ); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button( 'Save Keys' ); ?>
            </form>

            <?php if ( empty( $browser_key ) || empty( $server_key ) ) : ?>
                <div class="notice notice-warning"><p><strong>Keys missing.</strong> Browser key loads the map. Server key calls Places API.</p></div>
            <?php endif; ?>

            <div id="aaa-gbm-app" class="aaa-gbm-app">
                <div class="aaa-gbm-panel">
                    <h2>Search Layers</h2>

                    <div class="aaa-gbm-row">
                        <label>Preset</label>
                        <select id="aaa-gbm-layer-preset">
                            <option value="">(none)</option>
                            <option value="fitness_types">Fitness (Types: gym/fitness_center/yoga_studio/...)</option>
                            <option value="fitness_text">Fitness (Text: boxing/kickboxing/martial arts/...)</option>
                            <option value="recovery_comp">Recovery Competitors (spa/massage/wellness_center)</option>
                        </select>
                        <div class="aaa-gbm-note">Presets are editable; they just pre-fill fields.</div>
                    </div>

                    <div class="aaa-gbm-row">
                        <label>Layer Name</label>
                        <input id="aaa-gbm-layer-name" type="text" placeholder="Gyms / Fitness" />
                    </div>

                    <div class="aaa-gbm-row">
                        <label>Mode</label>
                        <select id="aaa-gbm-layer-mode">
                            <option value="nearby">Nearby (Types)</option>
                            <option value="text">Text Search (Multi-Query)</option>
                        </select>
                    </div>

                    <div class="aaa-gbm-row">
                        <label>Types (comma)</label>
                        <input id="aaa-gbm-layer-types" type="text" placeholder="gym,fitness_center,yoga_studio" />
                        <div class="aaa-gbm-note">Must be valid Places “type” values (Table A). Use text mode for “boxing”, “karate”, etc.</div>
                    </div>

                    <div class="aaa-gbm-row">
                        <label>Text Queries (one per line)</label>
                        <textarea id="aaa-gbm-layer-queries" rows="5" placeholder="boxing gym&#10;martial arts&#10;personal trainer"></textarea>
                        <div class="aaa-gbm-note">All queries in this layer share the same pin color and weight.</div>
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
                        <label>Grid Spacing (meters)</label>
                        <input id="aaa-gbm-grid-spacing" type="number" min="300" step="100" value="2500" />
                        <div class="aaa-gbm-note">Smaller spacing = more calls + more coverage.</div>
                    </div>

                    <div class="aaa-gbm-row">
                        <label>Per-Query Radius (meters)</label>
                        <input id="aaa-gbm-query-radius" type="number" min="200" step="100" value="1800" />
                        <div class="aaa-gbm-note">Smaller radius often reduces “scattered” results and increases unique coverage via more tiles.</div>
                    </div>

                    <div class="aaa-gbm-row">
                        <label><input type="checkbox" id="aaa-gbm-adaptive" checked /> Adaptive refine (auto-subdivide “busy” tiles)</label>
                    </div>

                    <div class="aaa-gbm-row">
                        <label>Refine Trigger (results ≥)</label>
                        <input id="aaa-gbm-refine-trigger" type="number" min="5" step="1" value="18" />
                    </div>

                    <div class="aaa-gbm-row">
                        <label>Max Refine Depth</label>
                        <input id="aaa-gbm-refine-depth" type="number" min="0" step="1" value="2" />
                    </div>

                    <button class="button" id="aaa-gbm-run-scan">Run Scan (Selected Layer)</button>
                    <button class="button" id="aaa-gbm-reset">Reset Map</button>

                    <hr />

                    <h2>Cluster / Best Spot</h2>
                    <div class="aaa-gbm-row">
                        <label>Score Radius (meters)</label>
                        <input id="aaa-gbm-score-radius" type="number" min="200" step="100" value="1600" />
                    </div>

                    <div class="aaa-gbm-row">
                        <label>Candidate Grid (meters)</label>
                        <input id="aaa-gbm-score-grid" type="number" min="200" step="100" value="800" />
                    </div>

                    <button class="button" id="aaa-gbm-score">Find Best Spots</button>

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

<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;
?>
<div class="dt-pro-wrap">
    <div class="dt-pro-card">
        <h3><?php esc_html_e( 'Quick Actions', 'ddd-dev-tools' ); ?></h3>
        <p><?php esc_html_e( 'Use these only when troubleshooting.', 'ddd-dev-tools' ); ?></p>
        <p>
            <button type="button" class="button" id="dt-flush-cache" data-what="cache"><?php esc_html_e( 'Flush Object Cache', 'ddd-dev-tools' ); ?></button>
            <button type="button" class="button" id="dt-flush-rewrite" data-what="rewrite"><?php esc_html_e( 'Flush Rewrite Rules', 'ddd-dev-tools' ); ?></button>
            <span id="dt-flush-result" style="margin-left:10px;"></span>
        </p>
    </div>

    <div class="dt-pro-card">
        <h3><?php esc_html_e( 'File + Code Search', 'ddd-dev-tools' ); ?></h3>
        <p>
            <label for="dt-search-term"><strong><?php esc_html_e( 'Search term', 'ddd-dev-tools' ); ?></strong></label><br />
            <input type="text" id="dt-search-term" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. wc_get_order OR order_status_changed', 'ddd-dev-tools' ); ?>" />
            <button type="button" class="button button-primary" id="dt-search-btn"><?php esc_html_e( 'Search', 'ddd-dev-tools' ); ?></button>
            <button type="button" class="button" id="dt-clear-btn"><?php esc_html_e( 'Clear', 'ddd-dev-tools' ); ?></button>
        </p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php esc_html_e( 'Search mode', 'ddd-dev-tools' ); ?></th>
                <td>
                    <label><input type="radio" name="dt-mode" value="filename" checked /> <?php esc_html_e( 'Filename', 'ddd-dev-tools' ); ?></label>
                    <label style="margin-left:12px;"><input type="radio" name="dt-mode" value="content" /> <?php esc_html_e( 'File contents', 'ddd-dev-tools' ); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Scope', 'ddd-dev-tools' ); ?></th>
                <td>
                    <select id="dt-scope">
                        <option value="plugin"><?php esc_html_e( 'Target plugin only', 'ddd-dev-tools' ); ?></option>
                        <option value="plugins_active"><?php esc_html_e( 'Active plugins', 'ddd-dev-tools' ); ?></option>
                        <option value="plugins_inactive"><?php esc_html_e( 'Inactive plugins', 'ddd-dev-tools' ); ?></option>
                        <option value="plugins_all"><?php esc_html_e( 'Entire plugins folder', 'ddd-dev-tools' ); ?></option>
                        <option value="mu_plugins"><?php esc_html_e( 'MU-plugins folder', 'ddd-dev-tools' ); ?></option>
                        <option value="themes_active"><?php esc_html_e( 'Active theme(s)', 'ddd-dev-tools' ); ?></option>
                        <option value="wp_content"><?php esc_html_e( 'Entire wp-content', 'ddd-dev-tools' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr id="dt-plugin-row">
                <th scope="row"><?php esc_html_e( 'Target plugin', 'ddd-dev-tools' ); ?></th>
                <td>
                    <select id="dt-search-plugin" style="min-width:340px;">
                        <optgroup label="<?php esc_attr_e( 'Active Plugins', 'ddd-dev-tools' ); ?>">
                            <?php foreach ( (array) ( $plugins['active'] ?? [] ) as $p ) : ?>
                                <option value="<?php echo esc_attr( $p['file'] ); ?>"><?php echo esc_html( $p['name'] . ' — ' . $p['file'] ); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="<?php esc_attr_e( 'Inactive Plugins', 'ddd-dev-tools' ); ?>">
                            <?php foreach ( (array) ( $plugins['inactive'] ?? [] ) as $p ) : ?>
                                <option value="<?php echo esc_attr( $p['file'] ); ?>"><?php echo esc_html( $p['name'] . ' — ' . $p['file'] ); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </td>
            </tr>
            <tr id="dt-mu-row" style="display:none;">
                <th scope="row"><?php esc_html_e( 'MU-plugin', 'ddd-dev-tools' ); ?></th>
                <td>
                    <select id="dt-mu-plugin" style="min-width:340px;">
                        <option value=""><?php esc_html_e( '(Entire mu-plugins folder)', 'ddd-dev-tools' ); ?></option>
                        <?php foreach ( (array) $mu_plugins as $mp ) : ?>
                            <option value="<?php echo esc_attr( $mp ); ?>"><?php echo esc_html( $mp ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Engine', 'ddd-dev-tools' ); ?></th>
                <td><select id="dt-engine"><option value="php">PHP</option><option value="grep">grep</option><option value="rg">rg</option></select> <span id="dt-engine-status" style="margin-left:10px;"></span></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Options', 'ddd-dev-tools' ); ?></th>
                <td class="dt-pro-options">
                    <label><input type="checkbox" id="dt-ignore-case" checked /> <?php esc_html_e( 'Ignore case', 'ddd-dev-tools' ); ?></label>
                    <label><input type="checkbox" id="dt-whole-word" /> <?php esc_html_e( 'Whole word', 'ddd-dev-tools' ); ?></label>
                    <label><input type="checkbox" id="dt-regex" /> <?php esc_html_e( 'Regex', 'ddd-dev-tools' ); ?></label>
                    <label><input type="checkbox" id="dt-files-only" /> <?php esc_html_e( 'Files only', 'ddd-dev-tools' ); ?></label>
                </td>
            </tr>
            <tr><th scope="row"><?php esc_html_e( 'Extensions', 'ddd-dev-tools' ); ?></th><td><input type="text" id="dt-extensions" class="regular-text" placeholder="php,js,css" /></td></tr>
            <tr><th scope="row"><?php esc_html_e( 'Exclude directories', 'ddd-dev-tools' ); ?></th><td><input type="text" id="dt-exclude-dirs" class="regular-text" placeholder="vendor,node_modules,uploads" /></td></tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Limits', 'ddd-dev-tools' ); ?></th>
                <td>
                    <label><?php esc_html_e( 'Max results', 'ddd-dev-tools' ); ?> <input type="number" id="dt-max-results" value="200" min="1" max="2000" /></label>
                    <label style="margin-left:12px;"><?php esc_html_e( 'Max file KB', 'ddd-dev-tools' ); ?> <input type="number" id="dt-max-file-kb" value="1024" min="1" max="20000" /></label>
                    <label style="margin-left:12px;"><?php esc_html_e( 'Max ms', 'ddd-dev-tools' ); ?> <input type="number" id="dt-max-ms" value="8000" min="1000" max="20000" /></label>
                </td>
            </tr>
        </table>

        <div class="dt-pro-cli"><div class="dt-pro-cli-label"><?php esc_html_e( 'CLI preview (approximate):', 'ddd-dev-tools' ); ?></div><code id="dt-cli-preview"></code></div>
        <div id="dt-search-results"></div>
    </div>

    <div id="dt-modal" class="dt-modal" style="display:none" aria-hidden="true">
        <div class="dt-modal-inner" role="dialog" aria-modal="true">
            <button type="button" class="dt-modal-close" id="dt-modal-close" aria-label="<?php esc_attr_e( 'Close', 'ddd-dev-tools' ); ?>">&times;</button>
            <div class="dt-modal-title" id="dt-modal-title"></div>
            <div class="dt-modal-body" id="dt-modal-body"></div>
        </div>
    </div>
</div>

<?php
/**
 * ============================================================================
 * File Path: /aaa-workflow-ai-reports/admin/tabs/settings-openai.php
 * Description: Tab 1 — OpenAI API key settings, debug toggle, verification,
 *              preferred model selector, and advanced configuration for
 *              prompt templates, temperature, and token limits.
 * Dependencies: admin_post_aaa_wf_ai_save_openai, wp_ajax_aaa_wf_ai_verify_openai_key,
 *               options-helpers.php, ajax.php
 * File Version: 1.4.0
 * Updated: 2025-12-28
 * ============================================================================
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// -----------------------------------------------------------------------------
// Retrieve values from custom options table
// -----------------------------------------------------------------------------
$key             = aaa_wf_ai_get_option( 'aaa_wf_ai_openai_key', '' );
$debug           = (bool) aaa_wf_ai_get_option( 'aaa_wf_ai_debug_enabled', false );
$model_saved     = aaa_wf_ai_get_option( 'aaa_wf_ai_default_model', 'gpt-4o-mini' );
$models_list     = (array) aaa_wf_ai_get_option( 'aaa_wf_ai_models_list', [] );
$verified_at     = aaa_wf_ai_get_option( 'aaa_wf_ai_verified_at', '' );
$prompt_template = aaa_wf_ai_get_option( 'aaa_wf_ai_prompt_template', '' );
$temperature     = (float) aaa_wf_ai_get_option( 'aaa_wf_ai_temperature', 0.3 );
$max_tokens      = (int) aaa_wf_ai_get_option( 'aaa_wf_ai_max_tokens', 800 );

// -----------------------------------------------------------------------------
// Show success message
// -----------------------------------------------------------------------------
if ( isset( $_GET['updated'] ) ) {
    echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
}
?>

<h2>OpenAI Integration</h2>
<p>Enter your OpenAI API key, enable debugging if needed, verify your configuration, choose the preferred model, and configure advanced AI settings.</p>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="aaa-wf-ai-openai-form">
    <input type="hidden" name="action" value="aaa_wf_ai_save_openai">
    <?php wp_nonce_field( 'aaa_wf_ai_save_openai', 'aaa_wf_ai_nonce_field' ); ?>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="aaa_wf_ai_openai_key">OpenAI API Key</label></th>
                <td>
                    <input type="password" id="aaa_wf_ai_openai_key" name="aaa_wf_ai_openai_key"
                           value="<?php echo esc_attr( $key ); ?>" size="60" placeholder="sk-..." />
                    <label style="margin-left:10px;">
                        <input type="checkbox" id="aaa-wf-ai-show-key"> Show Key
                    </label>
                    <p class="description">Your secret API key is stored securely in the custom options table.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Preferred Model</th>
                <td>
                    <?php
                    // Limit visible models to supported ones
                    $supported_models = array_filter( $models_list, function( $id ) {
                        return preg_match( '/^(gpt\-4o|gpt\-3\.5)/', $id );
                    } );
                    ?>
                    <?php if ( ! empty( $supported_models ) ) : ?>
                        <select name="aaa_wf_ai_default_model" id="aaa_wf_ai_default_model">
                            <?php foreach ( $supported_models as $model_id ) : ?>
                                <option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $model_id, $model_saved ); ?>>
                                    <?php echo esc_html( $model_id ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else : ?>
                        <select disabled><option>No verified models. Please verify API key first.</option></select>
                    <?php endif; ?>
                    <p class="description">Select which OpenAI model should power AI reports.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Custom Prompt Template</th>
                <td>
                    <textarea name="aaa_wf_ai_prompt_template" id="aaa_wf_ai_prompt_template" rows="6" cols="60" style="width:100%;max-width:600px;"><?php echo esc_textarea( $prompt_template ); ?></textarea>
                    <p class="description">Define how the AI should analyse your sales data. Use this to adjust the tone or metrics of interest. Leave blank to use the default template.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Temperature</th>
                <td>
                    <input type="number" step="0.1" min="0" max="1" name="aaa_wf_ai_temperature" id="aaa_wf_ai_temperature"
                           value="<?php echo esc_attr( $temperature ); ?>" />
                    <p class="description">Controls the creativity of the AI response (0 = deterministic, 1 = very creative).</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Max Tokens</th>
                <td>
                    <input type="number" step="10" min="100" max="4000" name="aaa_wf_ai_max_tokens" id="aaa_wf_ai_max_tokens"
                           value="<?php echo esc_attr( $max_tokens ); ?>" />
                    <p class="description">Maximum number of tokens to allocate for the AI reply.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Enable Debug Logging</th>
                <td>
                    <label>
                        <input type="checkbox" name="aaa_wf_ai_debug_enabled" value="1" <?php checked( $debug, true ); ?>>
                        Log plugin activity to <code>/wp-content/debug.log</code>
                    </label>
                </td>
            </tr>
            <?php if ( $verified_at ) : ?>
            <tr>
                <th scope="row">Last Verified</th>
                <td>
                    <p><strong><?php echo esc_html( date_i18n( 'F j, Y g:i a', strtotime( $verified_at ) ) ); ?></strong></p>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php submit_button( 'Save Settings', 'primary', 'submit', false ); ?>
    <button type="button" id="aaa-wf-ai-verify-key" class="button button-secondary">Verify API Key</button>
    <span id="aaa-wf-ai-verify-result" style="margin-left:10px;"></span>
</form>

<script>
jQuery(document).ready(function($){
    // Toggle visibility of API key
    $('#aaa-wf-ai-show-key').on('change', function(){
        const input = $('#aaa_wf_ai_openai_key');
        input.attr('type', this.checked ? 'text' : 'password');
    });
    // Verify API key
    $('#aaa-wf-ai-verify-key').on('click', function(e){
        e.preventDefault();
        const button = $(this);
        const result = $('#aaa-wf-ai-verify-result');
        result.text('Verifying...');
        button.prop('disabled', true);
        // Include current selected model so backend won't override
        const selectedModel = $('#aaa_wf_ai_default_model').val();
        $.post(AAA_WFAI.ajaxUrl, {
            action: 'aaa_wf_ai_verify_openai_key',
            nonce: AAA_WFAI.nonce,
            aaa_wf_ai_default_model: selectedModel // send the current selection
        }, function(resp){
            button.prop('disabled', false);
            if(resp.success){
                result.css({'color':'green','font-weight':'bold'}).text('✅ ' + resp.data.message);
                if(resp.data.models){
                    // Rebuild dropdown dynamically
                    const select = $('#aaa_wf_ai_default_model');
                    select.empty();
                    $.each(resp.data.models, function(i, id){
                        if(/^gpt\-4o|gpt\-3\.5/.test(id)){
                            select.append('<option value="'+id+'">'+id+'</option>');
                        }
                    });
                }
            } else {
                result.css({'color':'red','font-weight':'bold'}).text('❌ ' + (resp.data?.message || 'Verification failed.'));
            }
        });
    });
});
</script>

<?php aaa_wf_ai_debug( 'Rendered OpenAI settings tab with model selector + advanced options + verification.', basename( __FILE__ ) ); ?>
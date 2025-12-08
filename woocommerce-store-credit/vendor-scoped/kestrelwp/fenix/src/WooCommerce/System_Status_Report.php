<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers\Booleans;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce\Contracts\WooCommerce_Extension;
use WP_REST_Request;
use WP_REST_Response;
/**
 * WooCommerce system status report handler.
 *
 * @since 1.3.0
 */
abstract class System_Status_Report
{
    use Is_Handler;
    /**
     * System status report constructor.
     *
     * @since 1.3.0
     *
     * @param WooCommerce_Extension $plugin
     */
    protected function __construct(WooCommerce_Extension $plugin)
    {
        static::$plugin = $plugin;
        static::add_action('woocommerce_system_status_report', [$this, 'output_tabular_data'], 9);
        static::add_filter('woocommerce_rest_prepare_system_status', [$this, 'append_endpoint_data'], 10, 3);
    }
    /**
     * Gets the report ID.
     *
     * @return string
     */
    protected static function get_report_id(): string
    {
        return static::plugin()->id();
    }
    /**
     * Gets the report title.
     *
     * @since 1.3.0
     *
     * @return string
     */
    protected static function get_report_title(): string
    {
        return sprintf('%s %s', static::plugin()->vendor(), static::plugin()->name());
    }
    /**
     * Gets the system status report data.
     *
     * @since 1.3.0
     *
     * @param array<string, mixed> $context_args the context of the system status report
     * @return array<string, array{
     *      id?: string,
     *      label?: string,
     *      help?: string|null,
     *      html?: string,
     *      value?: scalar,
     * }>
     */
    abstract protected static function get_report_data(array $context_args = []): array;
    /**
     * Returns the filtered system status report data for the plugin in context.
     *
     * @since 1.3.0
     *
     * @param array<string, mixed> $context_args
     * @return array<string, array{
     *     id?: string,
     *     label?: string,
     *     help?: string|null,
     *     html?: string,
     *     value?: scalar,
     * }>
     */
    private function get_filtered_report_data(array $context_args): array
    {
        /**
         * Filters the system status report data for the plugin in context.
         *
         * @since 1.3.0
         *
         * @param array<string, string> $data
         * @param array<string, mixed> $context_args
         * @param System_Status_Report $handler
         */
        return (array) apply_filters(static::plugin()->hook('system_status_report_data'), static::get_report_data($context_args), $context_args, $this);
    }
    /**
     * Filters the WooCommerce REST API System Status response to include the plugin's data.
     *
     * @since  1.3.0
     *
     * @param mixed|WP_REST_Response $response REST API response object
     * @param array<string, string>|mixed $system_status system status data
     * @param mixed|WP_REST_Request $request REST API request object
     * @return mixed|WP_REST_Response
     */
    protected function append_endpoint_data($response, $system_status, $request)
    {
        if (!$response instanceof WP_REST_Response || !$request instanceof WP_REST_Request || !is_array($system_status) || empty($response->data) || !is_array($response->data)) {
            return $response;
        }
        $response_data = [];
        $report_data = $this->get_filtered_report_data(['context' => 'rest', 'request' => $request]);
        foreach ($report_data as $data) {
            // @phpstan-ignore-next-line
            if (!isset($data['id'])) {
                continue;
            }
            $response_data[$data['id']] = $data['value'] ?? null;
            // account for falsey values
        }
        if (empty($response_data)) {
            return $response;
        }
        $response->data[static::get_report_id()] = $response_data;
        return $response;
    }
    /**
     * Outputs the system status report data in the WooCommerce system status page.
     *
     * @since 1.3.0
     *
     * @return void
     */
    protected function output_tabular_data(): void
    {
        $data = $this->get_filtered_report_data(['context' => 'admin']);
        if (empty($data)) {
            return;
        }
        $plugin_name = static::plugin()->name();
        $report_title = static::get_report_title();
        /* translators: Placeholder: %s - Plugin name */
        $help_tip = sprintf(__('This section shows troubleshooting information about %s.', static::plugin()->textdomain()), $plugin_name);
        ?>
		<table id="<?php 
        echo esc_attr(static::get_report_id());
        ?>" class="wc_status_table widefat" cellspacing="0">

			<thead>
				<tr>
					<th colspan="3" data-export-label="<?php 
        echo esc_attr($report_title);
        ?>">
						<h2><?php 
        echo esc_html($report_title);
        ?> <?php 
        echo wc_help_tip($help_tip);
        // phpcs:ignore
        ?></h2>
					</th>
				</tr>
			</thead>

			<tbody>
				<?php 
        foreach ($data as $export_key => $value) {
            if (!empty($value['label']) && !empty($value['html'])) {
                $export_key = is_numeric($export_key) ? $value['label'] : $export_key;
                ?>
						<tr data-export-label="<?php 
                echo esc_html($export_key);
                ?>">
							<td><?php 
                echo esc_html($value['label']);
                ?>:</td>
							<td class="help"><?php 
                echo !empty($value['help']) ? wc_help_tip($value['help'], \true) : '&nbsp;';
                ?></td>
							<td><?php 
                echo wp_kses_post($value['html']);
                ?></td>
						</tr>
						<?php 
            }
        }
        ?>
			</tbody>

		</table>
		<?php 
    }
    /**
     * Returns the HTML markup for the boolean flag icon from a given value.
     *
     * @since 1.3.0
     *
     * @param mixed $value a value to check (should be a string, int, or bool)
     * @param string $additional_message additional text to display inside the markup (default none)
     * @return string
     */
    protected static function get_boolean_flag_markup($value, string $additional_message = ''): string
    {
        $true = is_scalar($value) && Booleans::from_value($value)->to_boolean();
        // phpcs:ignore
        return sprintf('<mark class="%1$s"><span class="dashicons dashicons-%2$s"></span>%3$s</mark>', $true ? 'yes' : 'error', $true ? 'yes' : 'no-alt', $additional_message ? ' ' . $additional_message : '');
    }
}

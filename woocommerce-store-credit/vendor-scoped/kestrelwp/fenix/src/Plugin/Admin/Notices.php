<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers\Arrays;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Call_To_Action;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Notice;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
/**
 * Admin notices handler.
 *
 * @since 1.0.0
 */
final class Notices
{
    use Is_Handler;
    /** @var array<string, Notice> */
    private static array $notices = [];
    /**
     * Notices constructor.
     *
     * @since 1.0.0
     *
     * @param WordPress_Plugin $plugin
     */
    public function __construct(WordPress_Plugin $plugin)
    {
        self::$plugin = $plugin;
        self::add_action('admin_notices', [$this, 'print_notices'], 15);
        self::add_action('admin_footer', [$this, 'print_scripts'], 20);
        self::add_action('wp_ajax_' . self::get_dismiss_notice_action(), [__CLASS__, 'dismiss']);
    }
    /**
     * Adds a notice to display.
     *
     * @since 1.0.0
     *
     * @param Notice $notice
     * @return void
     */
    public static function add(Notice $notice): void
    {
        self::$notices[$notice->get_id()] = $notice;
    }
    /**
     * Removes a notice.
     *
     * @since 1.0.0
     *
     * @param string $notice_id
     * @return void
     */
    public static function remove(string $notice_id): void
    {
        if (isset(self::$notices[$notice_id])) {
            unset(self::$notices[$notice_id]);
        }
    }
    /**
     * Gets a notice by ID.
     *
     * @since 1.0.0
     *
     * @param string $notice_id
     * @return Notice|null
     */
    public static function get(string $notice_id): ?Notice
    {
        return self::$notices[$notice_id] ?? null;
    }
    /**
     * Dismisses a notice.
     *
     * This method is also used as an AJAX callback.
     *
     * @see Notices::print_scripts()
     *
     * @since 1.0.0
     *
     * @param string|null $notice_id
     * @param int|null $user_id defaults to the current user ID
     * @return void
     */
    public static function dismiss(?string $notice_id = null, ?int $user_id = null): void
    {
        $user_id = null === $user_id ? get_current_user_id() : $user_id;
        if ($user_id && $notice_id && !wp_doing_ajax()) {
            $notice = self::get($notice_id);
            if ($notice) {
                $notice->dismiss($user_id);
            }
        } elseif (wp_doing_ajax()) {
            // phpcs:ignore
            $notice_id = $_REQUEST['notice'] ?? '';
            $notice = null;
            if ($notice_id && is_string($notice_id)) {
                // unfortunately in AJAX context, we can't use the Notices::get() method because it's a new thread
                $notice = Notice::create(['id' => $notice_id]);
            }
            if (!$user_id) {
                wp_send_json_error('Could not dismiss a notice for an undetermined user.');
            }
            if (!$notice) {
                wp_send_json_error('Could not dismiss an undetermined notice.');
            }
            $notice->dismiss();
            wp_send_json_success(sprintf('Notice "%s" is dismissed', $notice_id));
        }
    }
    /**
     * Gets the dismissed notices for the current user.
     *
     * @since 1.0.0
     *
     * @param int|null $user_id optional, defaults to the current user ID
     * @return string[] notice IDs
     */
    public static function get_dismissed_notices(?int $user_id = null): array
    {
        $user_id = null === $user_id ? get_current_user_id() : $user_id;
        if (empty($user_id)) {
            return [];
        }
        $dismissed_notices = get_user_meta($user_id, '_' . self::plugin()->key('dismissed_notices'), \true);
        return is_array($dismissed_notices) ? $dismissed_notices : [];
    }
    /**
     * Returns the dismiss notice action.
     *
     * @since 1.0.0
     *
     * @return string
     */
    private static function get_dismiss_notice_action(): string
    {
        return self::plugin()->hook('dismiss_notice');
    }
    /**
     * Displays notices to the current user.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function print_notices(): void
    {
        foreach (self::$notices as $notice) {
            if ($notice->is_deferred() || !$notice->should_display()) {
                continue;
            }
            self::render($notice);
        }
    }
    /**
     * Outputs a notice.
     *
     * @since 1.0.0
     *
     * @param Notice $notice
     * @return void HTML output
     */
    public static function render(Notice $notice): void
    {
        ob_start();
        self::render_call_to_actions($notice);
        $notice_ctas = ob_get_clean();
        $notice_title = '<strong>' . esc_html($notice->get_title() ?: self::plugin()->name()) . '</strong> - ';
        $notice_content = $notice_title . $notice->get_content() . $notice_ctas;
        $notice_attributes = array_merge(['data-notice-id' => $notice->get_id(), 'data-plugin-id' => self::plugin()->id()], $notice->get_attributes());
        // if available, use the function introduced in WordPress 6.4
        if (function_exists('wp_admin_notice')) {
            wp_admin_notice($notice_content, ['type' => $notice->get_type(), 'id' => $notice->get_id(), 'dismissible' => $notice->is_dismissible(), 'paragraph_wrap' => \true, 'additional_classes' => [self::plugin()->handle('notice')], 'attributes' => $notice_attributes]);
        } else {
            $notice_classes = ['notice', 'notice-' . $notice->get_type(), self::plugin()->handle('notice')];
            if ($notice->is_dismissible()) {
                $notice_classes[] = 'is-dismissible';
            }
            $notice_attributes = implode(' ', array_map(static function ($key, $value) {
                return sprintf('%s="%s"', esc_attr($key), esc_attr($value));
            }, array_keys($notice_attributes), $notice_attributes));
            ?>
			<div class="<?php 
            echo esc_attr(implode(' ', $notice_classes));
            ?>" <?php 
            echo $notice_attributes;
            // phpcs:ignore
            ?>>
				<p><?php 
            echo wp_kses_post($notice_content);
            ?></p>
			</div>
			<?php 
        }
    }
    /**
     * Renders the call to actions for a notice.
     *
     * @since 1.0.0
     *
     * @param Notice $notice
     * @return void
     */
    private static function render_call_to_actions(Notice $notice): void
    {
        if ($notice->has_call_to_actions()) {
            ?>
			<br><br>
			<span style="display: block;"><?php 
            foreach ($notice->get_call_to_actions() as $call_to_action) {
                // @phpstan-ignore-next-line type safety
                if (!$call_to_action instanceof Call_To_Action) {
                    continue;
                }
                $button_classes = ['button'];
                if ($call_to_action->is_primary()) {
                    $button_classes[] = 'button-primary';
                }
                $button_classes[] = 'call-to-action';
                $button_classes[] = self::plugin()->handle('call-to-action');
                $optional_attributes = [];
                foreach ($call_to_action->get_attributes() as $attribute => $value) {
                    // @phpstan-ignore-next-line type safety
                    if (!is_string($attribute) || !is_string($value)) {
                        continue;
                    }
                    $optional_attributes[] = esc_attr($attribute) . '="' . esc_attr($value) . '"';
                }
                ?>
				<a id="<?php 
                echo esc_attr($call_to_action->get_id());
                ?>" href="<?php 
                echo esc_url($call_to_action->get_url() ?: '#');
                ?>" class="<?php 
                echo esc_attr(implode(' ', $button_classes));
                ?>" target="<?php 
                echo esc_attr($call_to_action->get_target());
                ?>" <?php 
                echo implode(' ', $optional_attributes);
                // phpcs:ignore
                ?>>
					<?php 
                echo esc_html($call_to_action->get_label());
                ?>
				</a>
				<?php 
            }
            ?>
			</span><?php 
        }
    }
    /**
     * Gets the deferred notices data.
     *
     * @see Notices::print_scripts()
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    private function get_deferred_notices_data(): array
    {
        $deferred_notices = [];
        foreach (self::$notices as $notice) {
            if (!$notice->is_deferred() || !$notice->should_display()) {
                continue;
            }
            $notice_data = $notice->to_array();
            unset($notice_data['deferred'], $notice_data['display_condition']);
            $deferred_notices[$notice->get_id()] = $notice_data;
        }
        return $deferred_notices;
    }
    /**
     * Outputs notice scripts and deferred notices as an inline JavaScript JSON variable.
     *
     * @see Notices::remove()
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function print_scripts(): void
    {
        $deferred_notices = Arrays::array($this->get_deferred_notices_data())->to_json();
        $plugin_notice = self::plugin()->handle('notice');
        $dismiss_action = self::get_dismiss_notice_action();
        ?>
		<script type="text/javascript">
			const <?php 
        echo esc_js(self::plugin()->key('notices'));
        ?> = '<?php 
        echo esc_js($deferred_notices);
        ?>';

			document.addEventListener( 'DOMContentLoaded', () => {
				document.querySelectorAll( `.<?php 
        echo esc_js($plugin_notice);
        ?>` ).forEach( notice => {
					notice.addEventListener( 'click', ( event ) => {
						if ( event.target.classList.contains( 'notice-dismiss' ) ) {
							const notice_id = notice.dataset.noticeId;
							fetch( ajaxurl, {
								method: 'POST',
								headers: {
									'Content-Type': 'application/x-www-form-urlencoded'
								},
								body: new URLSearchParams( {
									action: '<?php 
        echo esc_js($dismiss_action);
        ?>',
									notice: notice_id
								} )
							} ).then( response => response.json() ).then( data => {
								console.log( data );
							} ).catch( error => {
								console.error( error );
							} );
						}
					} );
				} );
			} );
		</script>
		<?php 
    }
}

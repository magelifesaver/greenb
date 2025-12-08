<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle;

defined('ABSPATH') or exit;
use Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Marketing\Newsletter\Newsletter_Provider;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Call_To_Action;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Notice;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress;
/**
 * Newsletter signup handler.
 *
 * Handles newsletter subscription prompts after plugin updates.
 *
 * @since 1.8.0
 */
final class Newsletter_Signup
{
    use Is_Handler;
    /** @var string unprefixed user meta key to store whether the user has subscribed */
    private const SUBSCRIBED_META_KEY = 'newsletter_subscribed';
    /** @var string user meta key to store the version when the user has last dismissed the signup notice */
    private const DISMISSED_VERSION_META_KEY = 'newsletter_signup_dismissed';
    /** @var string notice identifier for the newsletter signup prompt */
    private const SIGNUP_NOTICE_ID = 'newsletter_signup';
    /** @var string HTML handle used in the prompt button */
    private const SIGNUP_BUTTON_HANDLE = 'newsletter-signup';
    /** @var string action identifier for newsletter signup */
    private const SIGNUP_ACTION = 'newsletter_signup';
    /** @var Newsletter_Provider|null the newsletter provider instance */
    private static ?Newsletter_Provider $provider = null;
    /** @var Notice|null the notice that will be displayed to prompt signing up to the newsletter */
    private static ?Notice $notice = null;
    /** @var bool */
    private static bool $displaying_notice = \false;
    /**
     * Initializes the newsletter signup handler.
     *
     * @since 1.8.0
     *
     * @param WordPress_Plugin $plugin the plugin instance
     */
    private function __construct(WordPress_Plugin $plugin)
    {
        self::$plugin = $plugin;
        $newsletter_config = $plugin->config()->get('marketing.newsletter', []);
        $provider_class = $newsletter_config['provider'] ?? null;
        $provider_config = $newsletter_config['config'] ?? [];
        // @phpstan-ignore-next-line
        if (is_string($provider_class) && class_exists($provider_class) && is_a($provider_class, Newsletter_Provider::class, \true)) {
            self::$provider = new $provider_class((array) $provider_config);
        }
        if (!self::$provider || !self::$provider->is_configured()) {
            return;
        }
        self::add_action('init', [$this, 'prepare_signup_notice'], \PHP_INT_MAX);
        self::add_action(self::plugin()->hook('updated'), [$this, 'display_notice_on_plugin_update'], \PHP_INT_MAX);
        self::add_action(self::plugin()->hook(self::SIGNUP_NOTICE_ID . '_dismissed'), [$this, 'record_signup_dismissal'], 10, 2);
        self::add_action('wp_ajax_' . self::plugin()->hook(self::SIGNUP_ACTION), [$this, 'handle_user_signup']);
        self::add_action('admin_print_footer_scripts', [$this, 'output_signup_script']);
    }
    /**
     * Sets the notice to use for prompting sign-up.
     *
     * @since 1.8.0
     *
     * @return void
     */
    protected function prepare_signup_notice(): void
    {
        if (!is_admin()) {
            return;
        }
        $plugin_vendor = self::plugin()->vendor();
        $plugin_name = self::plugin()->name();
        if (empty($plugin_vendor)) {
            $notice_message = sprintf(
                /* translators: Placeholder: %s - Plugin name */
                __('Want to get more out of %s? Join the our newsletter for quick tips, features, and member-only offers.', self::plugin()->textdomain()),
                '<strong>' . $plugin_name . '</strong>'
            );
        } else {
            $vendor_url = self::plugin()->vendor_url();
            $notice_message = sprintf(
                /* translators: Placeholder: %1$s - Plugin name, %2$s - Opening HTML anchor tag, %3$s - Plugin vendor, %4$s - Closing HTML anchor tag */
                __('Want to get more out of %1$s? Join the %2$s%3$s newsletter%4$s for quick tips, features, and member-only offers.', self::plugin()->textdomain()),
                '<strong>' . $plugin_name . '</strong>',
                $vendor_url ? '<a href="' . esc_url($vendor_url) . '" target="_blank">' : '',
                $plugin_vendor,
                $vendor_url ? '</a>' : ''
            );
        }
        $cta = Call_To_Action::create([
            /* translators: Context: Button label to sign up to newsletter */
            'label' => __('Sign up', self::plugin()->textdomain()),
            'id' => self::plugin()->handle(self::SIGNUP_BUTTON_HANDLE),
            'primary' => \true,
            'url' => '#',
        ]);
        $cta->add_class(self::plugin()->handle(self::SIGNUP_BUTTON_HANDLE));
        $cta->add_attribute('data-nonce', wp_create_nonce(self::plugin()->key(self::SIGNUP_ACTION)));
        self::$notice = Notice::info($notice_message)->set_id(self::SIGNUP_NOTICE_ID)->set_dismissible(\true)->add_call_to_action($cta)->without_title();
    }
    /**
     * Returns the notice with the newsletter signup prompt.
     *
     * @since 1.8.0
     *
     * @return Notice|null
     */
    private function signup_notice(): ?Notice
    {
        if (!self::$notice) {
            $this->prepare_signup_notice();
        }
        return self::$notice;
    }
    /**
     * Checks if the current user has already subscribed to the newsletter.
     *
     * @since 1.8.0
     *
     * @param int $user_id
     * @return bool
     */
    private function is_user_subscribed(int $user_id): bool
    {
        return $user_id && 'yes' === get_user_meta($user_id, '_' . self::plugin()->key(self::SUBSCRIBED_META_KEY), \true);
    }
    /**
     * Marks the current user as subscribed.
     *
     * @since 1.8.0
     *
     * @param int $user_id
     * @return void
     */
    private function mark_user_subscribed(int $user_id): void
    {
        update_user_meta($user_id, '_' . self::plugin()->key(self::SUBSCRIBED_META_KEY), 'yes');
    }
    /**
     * Gets the version at which the current user last dismissed the notice.
     *
     * @since 1.8.0
     *
     * @param int $user_id
     * @return string|null
     */
    private function get_dismissed_signup_plugin_version(int $user_id): ?string
    {
        $version = $user_id ? get_user_meta($user_id, '_' . self::plugin()->key(self::DISMISSED_VERSION_META_KEY), \true) : null;
        return Strings::is_semver($version, \true) ? $version : null;
    }
    /**
     * Records the version at which the current user dismissed the notice.
     *
     * Callback triggered when the user dismisses the newsletter signup notice.
     *
     * @see Notice::dismiss()
     *
     * @since 1.8.0
     *
     * @param Notice $notice
     * @param int $user_id
     * @return void
     */
    protected function record_signup_dismissal(Notice $notice, int $user_id): void
    {
        update_user_meta($user_id, '_' . self::plugin()->key(self::DISMISSED_VERSION_META_KEY), self::plugin()->version());
    }
    /**
     * Determines if an update is a minor or major version change (not a patch).
     *
     * @since 1.8.0
     *
     * @param string $from_version the old version
     * @param string $to_version the new version
     * @return bool true if it's a minor or major update, false if it's a patch update
     */
    private function is_minor_or_major_update(string $from_version, string $to_version): bool
    {
        $from_parts = explode('.', $from_version);
        $to_parts = explode('.', $to_version);
        if (count($from_parts) < 2 || count($to_parts) < 2) {
            return \false;
        }
        $from_major = (int) $from_parts[0];
        $from_minor = (int) $from_parts[1];
        $to_major = (int) $to_parts[0];
        $to_minor = (int) $to_parts[1];
        return $from_major !== $to_major || $from_minor !== $to_minor;
    }
    /**
     * Displays a newsletter signup notice after minor or major updates OR at the first update if the user never seen the notice before.
     *
     * @since 1.8.0
     *
     * @return void
     */
    protected function display_notice_on_plugin_update(): void
    {
        if (!is_admin()) {
            return;
        }
        $user_id = get_current_user_id();
        // bail on error or if the user signed up already
        if (!$user_id || $this->is_user_subscribed($user_id)) {
            return;
        }
        // admins only
        if (!current_user_can('manage_options')) {
            return;
        }
        $update_history = Lifecycle::get_update_history();
        // bail on first install or no updates recorded
        if (empty($update_history)) {
            return;
        }
        $recent_update = end($update_history);
        // @phpstan-ignore-next-line
        if (!is_array($recent_update) || empty($recent_update['from']) || empty($recent_update['to'])) {
            return;
        }
        $from_version = $recent_update['from'];
        $to_version = $recent_update['to'];
        $dismissed_version = $this->get_dismissed_signup_plugin_version($user_id);
        // the user has dismissed the signup prompt but this is somehow the same or an older version of the plugin, therefore skip
        if ($dismissed_version && version_compare($dismissed_version, $to_version, '>=')) {
            return;
        }
        // the user already seen and dismissed the notice and the current is a patch version, so we skip until the next minor or major version
        if ($dismissed_version && !$this->is_minor_or_major_update($from_version, $to_version)) {
            return;
        }
        $notice = $this->signup_notice();
        if (!$notice) {
            return;
        }
        self::$displaying_notice = \true;
        $notice->restore();
        $notice->dispatch();
    }
    /**
     * Enqueues admin scripts for handling newsletter signup.
     *
     * @since 1.8.0
     *
     * @return void
     */
    protected function output_signup_script(): void
    {
        if (!self::$displaying_notice) {
            return;
        }
        ?>
		<script type="text/javascript">
			( function( $ ) {
				$( '#<?php 
        echo esc_js(self::plugin()->handle(self::SIGNUP_BUTTON_HANDLE));
        ?>' ).on( 'click', function( e ) {
					e.preventDefault();

					let button = $( this );
					let nonce  = button.data( 'nonce' );

					button.prop( 'disabled', true ).text( '<?php 
        echo esc_js(__('Subscribing...', self::plugin()->textdomain()));
        ?>' );

					$.post(
						ajaxurl,
						{
							action: '<?php 
        echo esc_js(self::plugin()->hook(self::SIGNUP_ACTION));
        ?>',
							nonce:  nonce
						},
						function( response ) {
							console.log( response );
							button.text( '<?php 
        echo esc_js(__('Thank you!', self::plugin()->textdomain()));
        ?>' );
							button.closest( '.notice' ).fadeOut();
						}
					);
				} );
			} )( jQuery );
		</script>
		<?php 
    }
    /**
     * Handles AJAX newsletter signup requests.
     *
     * @since 1.8.0
     *
     * @return void
     */
    protected function handle_user_signup(): void
    {
        check_ajax_referer(self::plugin()->key(self::SIGNUP_ACTION), 'nonce');
        $user = wp_get_current_user();
        if (!user_can($user->ID, 'manage_options')) {
            wp_send_json_error(['message' => 'User does not have permission to perform this action.']);
        }
        if ($this->is_user_subscribed($user->ID)) {
            wp_send_json_success(['message' => 'User already subscribed.']);
        }
        try {
            $success = self::$provider->subscribe($user, ['plugin_name' => self::plugin()->id(), 'plugin_version' => self::plugin()->version(), 'site_name' => get_bloginfo('name'), 'site_url' => get_site_url(), 'locale' => $user->locale ?: get_bloginfo('language'), 'php_version' => \PHP_VERSION, 'wp_version' => WordPress::version(), 'wc_version' => WooCommerce::version()]);
            if ($success) {
                $this->mark_user_subscribed($user->ID);
                wp_send_json_success(['message' => 'Successfully subscribed to the newsletter!']);
            } else {
                wp_send_json_error(['message' => 'An error occurred while subscribing.']);
            }
        } catch (Exception $exception) {
            wp_send_json_error(['message' => $exception->getMessage()]);
        }
    }
}

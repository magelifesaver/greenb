<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin;

defined('ABSPATH') or exit;
use Closure;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
/**
 * WordPress privacy handler.
 *
 * @since 1.6.0
 */
class Privacy
{
    use Is_Handler;
    /** @var int */
    protected int $export_priority = 5;
    /** @var int */
    protected int $erase_priority = 10;
    /**
     * @var array<string, array<string, callable|Closure|string>>
     *
     * @phpstan-var array<string, array{
     *     exporter_friendly_name: string,
     *     callback: callable|Closure,
     * }>
     */
    protected array $exporters = [];
    /**
     * @var array<string, array<string, callable|Closure|string>>
     *
     * @phpstan-var array<string, array{
     *     eraser_friendly_name: string,
     *     callback: callable|Closure,
     * }>
     */
    protected array $erasers = [];
    /**
     * Constructor.
     *
     * @since 1.6.0
     *
     * @param WordPress_Plugin $plugin
     */
    protected function __construct(WordPress_Plugin $plugin)
    {
        static::$plugin = $plugin;
        static::add_action('admin_init', [$this, 'register_policy']);
        static::add_filter('wp_privacy_personal_data_exporters', [$this, 'register_personal_data_exporters'], $this->export_priority);
        static::add_filter('wp_privacy_personal_data_erasers', [$this, 'register_personal_data_erasers'], $this->erase_priority);
    }
    /**
     * Returns the default privacy policy statement.
     *
     * @since 1.6.0
     *
     * @return string|null
     */
    protected function policy_statement(): ?string
    {
        return wpautop(sprintf(
            /* translators: Placeholders: %1$s - opening link tag, %2$s - closing link tag */
            __('By using this plugin, you may be storing personal data or sharing data with an external service. %1$sContact the plugin developer for more information%2$s.', static::plugin()->textdomain()),
            '<a href="' . esc_url(static::plugin()->support_url()) . '" target="_blank">',
            '</a>'
        ));
    }
    /**
     * Registers a privacy policy statement in the WordPress privacy page.
     *
     * @since 1.6.0
     *
     * @return void
     */
    protected function register_policy(): void
    {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }
        $statement = $this->policy_statement();
        if (empty($statement)) {
            return;
        }
        wp_add_privacy_policy_content(static::plugin()->name(), $statement);
    }
    /**
     * Integrate this exporter implementation within the WordPress core exporters.
     *
     * @since 1.6.0
     *
     * @param array<string, callable|Closure>|mixed $exporters
     * @return array<string, callable|Closure>|mixed
     */
    protected function register_personal_data_exporters($exporters = [])
    {
        if (!is_array($exporters)) {
            return $exporters;
        }
        foreach ($this->exporters as $id => $exporter) {
            $exporters[$id] = $exporter;
        }
        return $exporters;
    }
    /**
     * Integrate this eraser implementation within the WordPress core erasers.
     *
     * @since 1.6.0
     *
     * @param array<string, callable|Closure>|mixed $erasers
     * @return array<string, callable|Closure>|mixed
     */
    protected function register_personal_data_erasers($erasers = [])
    {
        if (!is_array($erasers)) {
            return $erasers;
        }
        foreach ($this->erasers as $id => $eraser) {
            $erasers[$id] = $eraser;
        }
        return $erasers;
    }
}

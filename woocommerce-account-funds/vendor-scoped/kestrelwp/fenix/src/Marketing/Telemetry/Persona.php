<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Marketing\Telemetry;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress;
/**
 * Object representation of a persona.
 *
 * @since 1.0.0
 */
final class Persona
{
    use Creates_New_Instances;
    use Has_Plugin_Instance;
    /** @var array<string, mixed> */
    protected array $data;
    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = wp_parse_args($data, ['last_updated' => null, 'locale' => get_locale(), 'url' => get_bloginfo('url'), 'php_version' => \PHP_VERSION, 'wp_version' => WordPress::version(), 'wc_version' => WooCommerce::version()]);
    }
    /**
     * Seeds an instance.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $args
     * @return self
     */
    public static function seed(array $args = []): self
    {
        return self::create($args);
    }
    /**
     * Gets the option key to store the persona.
     *
     * @since 1.0.0
     *
     * @return string
     */
    private static function get_option_key(): string
    {
        return self::plugin()->key('persona');
    }
    /**
     * Gets the persona for the current plugin.
     *
     * @since 1.0.0
     *
     * @return self
     */
    public static function get(): self
    {
        $data = get_option(self::get_option_key(), []);
        if (!is_array($data)) {
            $data = [];
        }
        return new self($data);
    }
    /**
     * Saves the persona.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function save()
    {
        $this->data['last_updated'] = current_time('c', \true);
        update_option(self::get_option_key(), $this->data, \false);
    }
    /**
     * Deletes the persona.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function delete()
    {
        delete_option(self::get_option_key());
    }
    /**
     * Converts the persona to an associative array.
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function to_array(): array
    {
        return $this->data;
    }
}

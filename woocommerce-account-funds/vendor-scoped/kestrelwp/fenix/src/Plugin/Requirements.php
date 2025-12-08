<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Requirements\Requirement;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
/**
 * Handles the plugin requirements.
 *
 * @since 1.1.0
 */
final class Requirements
{
    use Has_Plugin_Instance;
    /** @var array<class-string<Requirement>, bool> */
    private static array $results = [];
    /**
     * Evaluates the plugin requirements.
     *
     * @since 1.1.0
     *
     * @param WordPress_Plugin $plugin
     * @param array<class-string<Requirement>, array<string, mixed>> $requirements
     * @return bool
     */
    public static function are_satisfied(WordPress_Plugin $plugin, array $requirements): bool
    {
        self::$plugin = $plugin;
        foreach ($requirements as $requirement => $args) {
            // @phpstan-ignore-next-line sanity check
            if (!is_string($requirement)) {
                _doing_it_wrong(__METHOD__, 'Invalid requirement. A requirement must be a valid class that extends ' . Requirement::class . '.', '');
                continue;
            }
            // @phpstan-ignore-next-line sanity check
            if (!is_a($requirement, Requirement::class, \true)) {
                _doing_it_wrong(__METHOD__, esc_html(sprintf('Cannot handle requirement. %1$s must be a valid class that extends %2$s.', $requirement, Requirement::class)), '');
                continue;
            }
            self::$results[$requirement] = self::check(new $requirement($plugin, (array) $args));
        }
        return empty(self::$results) || !in_array(\false, self::$results, \true);
    }
    /**
     * Checks if a requirement is satisfied.
     *
     * @since 1.1.0
     *
     * @param Requirement $requirement
     * @return bool
     */
    private static function check(Requirement $requirement): bool
    {
        if (!$requirement->is_satisfied()) {
            $requirement->fail();
            return $requirement->should_plugin_initialize_on_failure();
        }
        $requirement->success();
        return \true;
    }
    /**
     * Determines if a given requirement has passed.
     *
     * This method can be accessed later to check if a specific requirement has passed during initialization.
     *
     * @since 1.1.0
     *
     * @phpstan-param class-string<Requirement> $requirement
     *
     * @param string $requirement
     * @return bool
     */
    public static function requirement_check_passed(string $requirement): bool
    {
        return !isset(self::$results[$requirement]) || \true === self::$results[$requirement];
    }
}

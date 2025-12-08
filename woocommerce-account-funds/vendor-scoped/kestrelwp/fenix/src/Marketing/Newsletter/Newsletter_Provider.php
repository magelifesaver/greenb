<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Marketing\Newsletter;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Exceptions\Exception;
use WP_User;
/**
 * Abstract newsletter provider.
 *
 * Newsletter providers are used to subscribe a WordPress user to a newsletter from different services.
 *
 * @since 1.8.0
 */
abstract class Newsletter_Provider
{
    /** @var array<string, mixed> provider configuration */
    protected array $config = [];
    /**
     * Newsletter provider constructor.
     *
     * @since 1.8.0
     *
     * @param array<string, mixed> $config provider configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    /**
     * Checks if the provider is properly configured.
     *
     * @since 1.8.0
     *
     * @return bool
     */
    abstract public function is_configured(): bool;
    /**
     * Subscribes a user to the newsletter.
     *
     * @since 1.8.0
     *
     * @param WP_User $user the user to sign-up to the newsletter
     * @param array<string, mixed> $metadata optional metadata to include with the subscription
     * @return bool true on success, false on failure
     * @throws Exception
     */
    abstract public function subscribe(WP_User $user, array $metadata = []): bool;
}

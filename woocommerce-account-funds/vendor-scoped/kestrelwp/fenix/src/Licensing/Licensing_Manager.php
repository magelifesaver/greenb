<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Licensing;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
/**
 * Licensing manager.
 *
 * @since 1.1.0
 */
final class Licensing_Manager
{
    use Is_Singleton;
    /** @var array<string, mixed> licensing data */
    private array $object_data;
    /**
     * Licensing helper constructor.
     *
     * @since 1.1.0
     *
     * @param array<string, mixed> $object_data such as plugin data
     */
    protected function __construct(array $object_data)
    {
        $this->object_data = $object_data;
    }
    /**
     * Initializes the licensing manager.
     *
     * @since 1.1.0
     *
     * @param array<string, mixed> $object_data
     * @return self
     */
    public static function initialize(array $object_data): self
    {
        return self::instance($object_data);
    }
    /**
     * Determines if the object is managed by WooCommerce.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_woocommerce_managed(): bool
    {
        return isset($this->object_data['Woo']) && preg_match('/^\d+:[a-f0-9]{32}$/', $this->object_data['Woo']) === 1;
    }
}

<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Stores;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Contracts\Setting_Store;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
/**
 * Handles settings storage as WordPress options.
 *
 * @since 1.1.0
 *
 * @method string get_name()
 * @method bool|null get_autoload()
 * @method $this set_name( string $name )
 * @method $this set_autoload( bool|null $autoload )
 */
class Option implements Setting_Store
{
    use Creates_New_Instances;
    use Has_Accessors;
    use Has_Plugin_Instance;
    /** @var string store type */
    protected string $type = 'option';
    /** @var string option name */
    protected string $name;
    /** @var bool|null whether the option should be autoloaded -- {@see add_option()} or {@see update_option()} */
    protected ?bool $autoload;
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param string $name
     * @param bool|null $autoload
     */
    public function __construct(string $name, ?bool $autoload = null)
    {
        $this->name = $name;
        $this->autoload = $autoload;
    }
    /**
     * Gets the type of store.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function get_type(): string
    {
        return $this->type;
    }
    /**
     * Reads the option.
     *
     * @since 1.1.0
     *
     * @return array<mixed>|scalar|null
     */
    public function read()
    {
        $name = $this->get_name();
        $value = !empty($name) ? get_option($name) : null;
        return \false === $value ? null : $value;
    }
    /**
     * Saves the option.
     *
     * @since 1.1.0
     *
     * @param array<mixed>|scalar|null $value
     * @return void
     */
    public function save($value): void
    {
        $name = $this->get_name();
        if (empty($name)) {
            return;
        }
        if (null === $value) {
            $this->delete();
            return;
        }
        update_option($name, $value, $this->get_autoload());
    }
    /**
     * Deletes the option.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function delete(): bool
    {
        $name = $this->get_name();
        if (empty($name)) {
            return \false;
        }
        return delete_option($name);
    }
    /**
     * Determines if the option exists in database.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function exists(): bool
    {
        $name = $this->get_name();
        if (empty($name)) {
            return \false;
        }
        return \false !== get_option($name);
    }
}

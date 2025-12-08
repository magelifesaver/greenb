<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
defined('ABSPATH') or exit;
/**
 * Object representing a notice call to action.
 *
 * @since 1.0.0
 *
 * @method string get_id()
 * @method string get_label()
 * @method string|null get_url()
 * @method string get_target()
 * @method bool get_primary()
 * @method string[] get_classes()
 * @method array<string, string> get_attributes()
 * @method $this set_id( string $id )
 * @method $this set_label( string $label )
 * @method $this set_url( ?string $url )
 * @method $this set_target( string $target )
 * @method $this set_primary( bool $primary )
 * @method $this set_classes( array $classes )
 * @method $this set_attributes( array $attributes )
 */
class Call_To_Action
{
    use Has_Accessors;
    use Creates_New_Instances;
    /** @var string CTA ID */
    protected string $id = '';
    /** @var string CTA label */
    protected string $label = '';
    /** @var string|null whether the CTA should point to a URL */
    protected ?string $url = null;
    /** @var string set the CTA URL target */
    protected string $target = '_self';
    /** @var bool whether this is the primary CTA */
    protected bool $primary = \true;
    /** @var string[] optional additional classes to apply to the CTA */
    protected array $classes = [];
    /** @var array<string, string> optional button attributes */
    protected array $attributes = [];
    /**
     * Notice_Call_To_Action constructor.
     *
     * @since 1.0.0
     *
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        if (empty($args['id']) && !empty($args['label'])) {
            $args['id'] = md5($args['label']);
        }
        $this->set_properties($args);
    }
    /**
     * Determines if the CTA is the primary CTA.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_primary(): bool
    {
        return $this->primary;
    }
    /**
     * Adds a CSS class to the CTA.
     *
     * @since 1.8.0
     *
     * @param string $class
     * @return $this
     */
    public function add_class(string $class): Call_To_Action
    {
        $this->classes[] = $class;
        return $this;
    }
    /**
     * Removes a CSS class from the CTA.
     *
     * @since 1.8.0
     *
     * @param string $class
     * @return $this
     */
    public function remove_class(string $class): Call_To_Action
    {
        if (!empty($this->classes)) {
            $this->classes = array_filter($this->classes, fn($c) => $c !== $class);
        }
        return $this;
    }
    /**
     * Adds an attribute to the CTA.
     *
     * @since 1.8.0
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function add_attribute(string $name, $value): Call_To_Action
    {
        if (!is_scalar($value)) {
            $value = wp_json_encode($value);
        }
        $this->attributes[$name] = (string) $value;
        return $this;
    }
    /**
     * Removes an attribute from the CTA.
     *
     * @since 1.8.0
     *
     * @param string $name
     * @return $this
     */
    public function remove_attribute(string $name): Call_To_Action
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }
        return $this;
    }
}

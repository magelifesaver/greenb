<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Dialogs;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
/**
 * Object representing a dialog button.
 *
 * @since 1.8.0
 *
 * @phpstan-consistent-constructor
 *
 * @method $this set_id( string $id )
 * @method string get_label()
 * @method $this set_label( string $label )
 * @method bool get_primary()
 * @method $this set_primary( bool $primary )
 * @method string|null get_url()
 * @method $this set_url( ?string $url )
 * @method string|null get_target()
 * @method $this set_target( ?string $target )
 * @method $this set_classes(array $classes)
 * @method $this set_attributes(array $attributes)
 */
class Modal_Button
{
    use Has_Accessors;
    use Has_Plugin_Instance;
    /** @var string */
    protected string $id;
    /** @var string */
    protected string $label;
    /** @var bool */
    protected bool $primary;
    /** @var string[] */
    protected array $classes = [];
    /** @var array<string, scalar> */
    protected array $attributes = [];
    /** @var string|null */
    protected ?string $url = null;
    /** @var string|null */
    protected ?string $target = null;
    /**
     * Constructor.
     *
     * @since 1.8.0
     *
     * @param string $id
     * @param string $label
     * @param bool $primary
     * @param array<string, mixed> $args
     */
    public function __construct(string $id, string $label, bool $primary = \false, array $args = [])
    {
        $this->set_properties(wp_parse_args($args, ['id' => $id, 'label' => $label, 'primary' => $primary]));
    }
    /**
     * Returns the button ID.
     *
     * @since 1.8.0
     *
     * @return string
     */
    public function get_id(): string
    {
        return self::plugin()->handle($this->id);
    }
    /**
     * Adds an HTML attribute to the button.
     *
     * @since 1.8.0
     *
     * @param string $key
     * @param mixed|scalar $value
     * @return $this
     */
    public function add_attribute(string $key, $value): Modal_Button
    {
        if (!is_scalar($value)) {
            return $this;
        }
        $this->attributes[$key] = $value;
        return $this;
    }
    /**
     * Removes an HTML attribute from the button.
     *
     * @since 1.8.0
     *
     * @param string $attribute
     * @return $this
     */
    public function remove_attribute(string $attribute): Modal_Button
    {
        unset($this->attributes[$attribute]);
        return $this;
    }
    /**
     * Adds a CSS class to the button.
     *
     * @since 1.8.0
     *
     * @param string $class
     * @return $this
     */
    public function add_class(string $class): Modal_Button
    {
        if (!in_array($class, $this->classes, \true)) {
            $this->classes[] = $class;
        }
        return $this;
    }
    /**
     * Removes a CSS class from the button.
     *
     * @since 1.8.0
     *
     * @param string $class
     * @return $this
     */
    public function remove_class(string $class): Modal_Button
    {
        $this->classes = array_filter($this->classes, function ($class_to_remove) use ($class) {
            return $class_to_remove !== $class;
        });
        return $this;
    }
    /**
     * Determines if the button is the primary button.
     *
     * @since 1.8.0
     *
     * @return bool
     */
    public function is_primary(): bool
    {
        return \true === $this->get_primary();
    }
    /**
     * Determines if the button is an anchor (i.e. has a URL).
     *
     * @since 1.8.0
     *
     * @return bool
     */
    public function is_anchor(): bool
    {
        return !empty($this->get_url());
    }
    /**
     * Determines if the button is intended to close the modal it is attached to.
     *
     * @since 1.8.0
     *
     * @return bool
     */
    public function closes_modal(): bool
    {
        return in_array('close-modal', $this->get_classes(), \true);
    }
    /**
     * Sets the button to close the modal it is attached to.
     *
     * @since 1.8.0
     *
     * @param bool $closes_modal
     * @return $this
     */
    public function set_to_close_modal(bool $closes_modal = \true): Modal_Button
    {
        if (!$closes_modal) {
            $this->remove_class('close-modal');
        } else {
            $this->add_class('close-modal');
        }
        return $this;
    }
    /**
     * Returns the button's CSS classes.
     *
     * @since 1.8.0
     *
     * @return string[]
     */
    public function get_classes(): array
    {
        $classes = ['button', 'button-large'];
        if ($this->is_primary()) {
            $classes[] = 'button-primary';
        }
        return array_values(array_unique(array_merge($classes, $this->classes)));
    }
    /**
     * Returns the button's HTML attributes.
     *
     * @since 1.8.0
     *
     * @return array<string, scalar>
     */
    public function get_attributes(): array
    {
        $attributes = [];
        if ($url = $this->get_url()) {
            $attributes['href'] = $url;
            if ($target = $this->get_target()) {
                $attributes['target'] = $target;
            }
        }
        return array_merge($attributes, $this->attributes);
    }
    /**
     * Returns the HTML tag to use for the button.
     *
     * @since 1.8.0
     *
     * @return string
     */
    private function tag(): string
    {
        return $this->is_anchor() ? 'a' : 'button';
    }
    /**
     * Outputs the button HTML.
     *
     * @since 1.8.0
     *
     * @return void
     */
    public function output(): void
    {
        $tag = $this->tag();
        $properties = array_merge(['id' => $this->get_id(), 'class' => implode(' ', $this->get_classes())], $this->get_attributes());
        echo '<' . esc_attr($tag);
        foreach ($properties as $key => $value) {
            // @phpstan-ignore-next-line scalar type sanity check
            echo is_string($key) && '' !== trim($key) && is_scalar($value) ? ' ' . esc_attr($key) . '="' . esc_attr((string) $value) . '"' : '';
        }
        echo '>';
        echo esc_html($this->get_label());
        echo '</' . esc_attr($tag) . '>';
    }
    /**
     * Creates a standard "Close" button to close the modal the button it is attached to.
     *
     * @since 1.8.0
     *
     * @param string $id
     * @param array<string, mixed> $args
     * @return self
     */
    public static function close(string $id, array $args = []): Modal_Button
    {
        /* translators: Context: Button label to cancel an action in a modal dialog. */
        return (new static($id, __('Close', static::plugin()->textdomain()), \false, $args))->set_classes(['close-modal']);
    }
    /**
     * Creates a standard "Cancel" button to close the modal the button it is attached to.
     *
     * @since 1.8.0
     *
     * @param string $id
     * @param array<string, mixed> $args
     * @return self
     */
    public static function cancel(string $id, array $args = []): Modal_Button
    {
        /* translators: Context: Button label to cancel an action in a modal dialog. */
        return (new static($id, __('Cancel', static::plugin()->textdomain()), \false, $args))->set_classes(['close-modal']);
    }
    /**
     * Creates a custom button.
     *
     * @since 1.8.0
     *
     * @param string $id
     * @param string $label
     * @param bool $primary
     * @param array<string, mixed> $args
     * @return self
     */
    public static function custom(string $id, string $label, bool $primary = \true, array $args = []): Modal_Button
    {
        return new static($id, $label, $primary, $args);
    }
}

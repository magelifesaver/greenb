<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Dialogs;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
/**
 * Object representing a dialog element in the admin area.
 *
 * @since 1.8.0
 *
 * @phpstan-consistent-constructor
 *
 * @method $this set_id( string $id )
 * @method string get_title()
 * @method $this set_title( string $title )
 * @method string get_content()
 * @method $this set_content( string $content )
 * @method $this set_classes(array $classes)
 * @method array<string, mixed> get_attributes()
 * @method $this set_attributes( array $attributes )
 * @method array<string, Modal_Button> get_buttons()
 */
class Modal
{
    use Has_Accessors;
    use Has_Plugin_Instance;
    /** @var string */
    protected string $id;
    /** @var string */
    protected string $title;
    /** @var string */
    protected string $content;
    /** @var string[] */
    protected array $classes = [];
    /** @var array<string, scalar> */
    protected array $attributes = [];
    /** @var Modal_Button[] */
    protected array $buttons = [];
    /** @var bool */
    private static bool $assets_already_output = \false;
    /**
     * Constructor.
     *
     * @since 1.8.0
     *
     * @param string $id
     * @param string $title
     * @param string $content
     * @param array<string, mixed> $args
     */
    public function __construct(string $id, string $title, string $content = '', array $args = [])
    {
        $this->set_properties(wp_parse_args($args, ['id' => $id, 'title' => $title, 'content' => $content]));
    }
    /**
     * Creates a new modal instance.
     *
     * @since 1.8.0
     *
     * @param string $id
     * @param string $title
     * @param string $content
     * @param array<string, mixed> $args
     * @return self
     */
    public static function make(string $id, string $title, string $content = '', array $args = []): Modal
    {
        return new static($id, $title, $content, $args);
    }
    /**
     * Returns the modal ID.
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
     * Sets the buttons for the modal.
     *
     * @since 1.8.0
     *
     * @param Modal_Button[] $buttons
     * @return $this
     */
    public function set_buttons(array $buttons): Modal
    {
        foreach ($buttons as $button) {
            // @phpstan-ignore-next-line type safety check
            if ($button instanceof Modal_Button) {
                $this->add_button($button);
            }
        }
        return $this;
    }
    /**
     * Adds a button to the modal.
     *
     * @param Modal_Button $button
     * @return $this
     */
    public function add_button(Modal_Button $button): Modal
    {
        if ($button_id = $button->get_id()) {
            $this->buttons[$button_id] = $button;
        }
        return $this;
    }
    /**
     * Adds a custom button to the modal.
     *
     * @since 1.8.0
     *
     * @param string $id
     * @param string $label
     * @param bool $primary
     * @param array<string, mixed> $args
     * @return $this
     */
    public function with_button(string $id, string $label, bool $primary = \true, array $args = []): Modal
    {
        return $this->add_button(Modal_Button::custom($id, $label, $primary, $args));
    }
    /**
     * Adds a button to close the modal.
     *
     * @since 1.8.0
     *
     * @param array<string, mixed> $args
     * @return $this
     */
    public function with_close_button(array $args = []): Modal
    {
        return $this->add_button(Modal_Button::close($this->id . '-close', $args));
    }
    /**
     * Adds a cancel button to the modal.
     *
     * @since 1.8.0
     *
     * @param array<string, mixed> $args
     * @return $this
     */
    public function with_cancel_button(array $args = []): Modal
    {
        return $this->add_button(Modal_Button::cancel($this->id . '-cancel', $args));
    }
    /**
     * Removes a button from the modal.
     *
     * @since 1.8.0
     *
     * @param Modal_Button|string $button object or ID of the button to remove
     * @return Modal
     */
    public function remove_button($button): Modal
    {
        if ($button instanceof Modal_Button) {
            $remove_button_id = $button->get_id();
        } else {
            $remove_button_id = $button;
        }
        unset($this->buttons[$remove_button_id]);
        return $this;
    }
    /**
     * Returns the main CSS class for the modal.
     *
     * @since 1.8.0
     *
     * @return string
     */
    private function get_main_class(): string
    {
        return self::plugin()->key('modal');
    }
    /**
     * Returns the CSS classes for the dialog element.
     *
     * @since 1.8.0
     *
     * @return string[]
     */
    public function get_classes(): array
    {
        $classes = [$this->get_main_class()];
        return array_values(array_unique(array_merge($classes, $this->classes)));
    }
    /**
     * Adds a CSS class to the modal.
     *
     * @since 1.8.0
     *
     * @param string $class
     * @return $this
     */
    public function add_class(string $class): Modal
    {
        if (!in_array($class, $this->classes, \true)) {
            $this->classes[] = $class;
        }
        return $this;
    }
    /**
     * Removes a CSS class from the modal.
     *
     * @since 1.8.0
     *
     * @param string $class
     * @return $this
     */
    public function remove_class(string $class): Modal
    {
        $this->classes = array_filter($this->classes, function ($class_to_remove) use ($class) {
            return $class_to_remove !== $class;
        });
        return $this;
    }
    /**
     * Outputs the dialog HTML.
     *
     * @since 1.8.0
     *
     * @return void
     */
    public function output(): void
    {
        $attributes = '';
        foreach ($this->get_attributes() as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $attributes .= sprintf(' %s="%s"', esc_attr($key), esc_attr((string) $value));
        }
        ?>
		<dialog id="<?php 
        echo esc_attr($this->get_id());
        ?>" class="<?php 
        echo esc_attr(implode(' ', $this->get_classes()));
        ?>" <?php 
        echo esc_attr($attributes);
        ?>>
			<header>
				<?php 
        $this->output_header();
        ?>
			</header>
			<article>
				<?php 
        $this->output_body();
        ?>
			</article>
			<footer>
				<?php 
        $this->output_footer();
        ?>
			</footer>
		</dialog>
		<?php 
        $this->output_assets();
    }
    /**
     * Outputs the modal header.
     *
     * @since 1.8.0
     *
     * @return void
     */
    protected function output_header(): void
    {
        ?>
		<h1><?php 
        echo esc_html($this->get_title());
        ?></h1>
		<button class="close-modal" data-target="<?php 
        echo esc_attr($this->get_id());
        ?>"><span class="dashicons dashicons-no-alt"></span></button>
		<?php 
    }
    /**
     * Outputs the modal body content.
     *
     * @since 1.8.0
     *
     * @return void
     */
    protected function output_body(): void
    {
        echo wp_kses_post(wpautop($this->get_content()));
    }
    /**
     * Outputs the modal footer with buttons.
     *
     * @since 1.8.0
     *
     * @return void
     */
    protected function output_footer(): void
    {
        foreach ($this->get_buttons() as $button) {
            // @phpstan-ignore-next-line type safety check
            if (!$button instanceof Modal_Button) {
                continue;
            }
            if ($button->closes_modal()) {
                $button->add_attribute('data-target', $this->get_id());
            }
            $button->output();
        }
    }
    /**
     * Determines if the modal assets should be output.
     *
     * @since 1.8.0
     *
     * @return bool
     */
    private function needs_assets(): bool
    {
        return self::$assets_already_output === \false;
    }
    /**
     * Outputs the modal assets.
     *
     * @since 1.8.0
     *
     * @return void
     */
    private function output_assets(): void
    {
        if (!$this->needs_assets()) {
            return;
        }
        $this->output_scripts();
        $this->output_styles();
        self::$assets_already_output = \true;
    }
    /**
     * Outputs the modal styles.
     *
     * @since 1.8.0
     *
     * @return void
     */
    private function output_scripts(): void
    {
        ?>
		<script>
			document.addEventListener( 'click', function( event ) {
				event.preventDefault();
				const openKestrelModal  = event.target.closest( '.open-modal' );
				const closeKestrelModal = event.target.closest( '.close-modal' );
				if ( closeKestrelModal ) {
					const kestrelModalId = closeKestrelModal.dataset.target;
					if ( kestrelModalId ) {
						const kestrelModal = document.getElementById( kestrelModalId );
						if ( kestrelModal ) {
							kestrelModal.close();
						}
					}
				}
				if ( openKestrelModal ) {
					const kestrelModalId = openKestrelModal.dataset.target;
					if ( kestrelModalId ) {
						const kestrelModal = document.getElementById( kestrelModalId );
						if ( kestrelModal ) {
							kestrelModal.showModal();
						}
					}
				}
			} );
		</script>
		<?php 
    }
    /**
     * Outputs the modal scripts.
     *
     * @since 1.8.0
     *
     * @return void
     */
    private function output_styles(): void
    {
        $modal_class_target = '.' . $this->get_main_class();
        ?>
		<style>
			<?php 
        echo esc_attr($modal_class_target);
        ?> {
				border: none !important;
				padding: 0 !important;
				max-width: 70vw !important;
				border-radius: 2px !important
			}
			<?php 
        echo esc_attr($modal_class_target . '::backdrop');
        ?> {
				background: #000 !important;
				opacity: 0.72 !important;
			}
			<?php 
        echo esc_attr($modal_class_target);
        ?> header {
				margin: 0 !important;
				min-width: 55vw !important;
				padding: 1em 1.5em 0.8em !important;
				border-bottom: 1px solid #ddd !important;
			}
			<?php 
        echo esc_attr($modal_class_target);
        ?> header h1 {
				font-size: 18px !important;
				font-weight: 700 !important;
				line-height: 1.5 !important;
				margin: 0 !important;
				padding: 0 !important;
			}
			<?php 
        echo esc_attr($modal_class_target);
        ?> header button.close-modal {
				cursor: pointer !important;
				color: #777 !important;
				height: 50px !important;
				width: 54px !important;
				padding: 0 !important;
				margin: 0 !important;
				position: absolute !important;
				top: 0 !important;
				right: 0 !important;
				text-align: center !important;
				border: 0 !important;
				border-left: 1px solid #ddd !important;
				background-color: transparent !important;
				transition: color .1s ease-in-out, background .1s ease-in-out !important;
			}
			<?php 
        echo esc_attr($modal_class_target);
        ?> header button.close-modal:hover {
				color: #000 !important;
				background: #ddd !important;
				border-color: #ccc !important;
			}
			<?php 
        echo esc_attr($modal_class_target);
        ?> article {
				padding: 1.5em  !important;
			}
			<?php 
        echo esc_attr($modal_class_target);
        ?> footer {
				border-top: 1px solid #dfdfdf !important;
				box-shadow: 0 -4px 4px -4px rgba(0,0,0,.1) !important;
				padding: 1em 1.5em !important;
				text-align: right !important;
			}
			<?php 
        echo esc_attr($modal_class_target);
        ?> footer button {
				margin-left: 8px !important;
			}
			@media screen and (max-width: 782px) {
				<?php 
        echo esc_attr($modal_class_target);
        ?> {
					width: 90vw !important;
				}
			}
		</style>
		<?php 
    }
    /**
     * Generates an HTML anchor for opening the current modal dialog.
     *
     * @since 1.8.0
     *
     * @param string $content
     * @param array<string, scalar> $attributes
     * @return string
     */
    public function get_opening_anchor(string $content, array $attributes = []): string
    {
        return $this->generate_opening_element('a', $content, $attributes);
    }
    /**
     * Generates an HTML button for opening the current modal dialog.
     *
     * @since 1.8.0
     *
     * @param string $label
     * @param bool $primary
     * @param bool $large
     * @param array<string, scalar> $attributes
     * @return string
     */
    public function get_opening_button(string $label, bool $primary = \true, bool $large = \true, array $attributes = []): string
    {
        if (isset($attributes['class']) && is_string($attributes['class'])) {
            $classes = $attributes['class'];
        } else {
            $classes = '';
        }
        $attributes['class'] = 'button ' . ($large ? 'button-large ' : '') . ($primary ? 'button-primary ' : '') . $classes;
        return $this->generate_opening_element('button', $label, $attributes);
    }
    /**
     * Generates an HTML element for opening the current modal dialog.
     *
     * @since 1.8.0
     *
     * @param string $tag
     * @param string $content
     * @param array<string, scalar> $attributes
     * @return string
     */
    private function generate_opening_element(string $tag, string $content, array $attributes): string
    {
        $html = '<' . esc_attr($tag);
        if ('a' === $tag) {
            $attributes['href'] = '#';
        }
        $attributes['class'] = trim('open-modal ' . ($attributes['class'] ?? ''));
        $attributes['data-target'] = $this->get_id();
        foreach ($attributes as $key => $value) {
            // @phpstan-ignore-next-line scalar type sanity check
            $html .= is_string($key) && '' !== trim($key) && is_scalar($value) ? ' ' . esc_attr($key) . '="' . esc_attr((string) $value) . '"' : '';
        }
        $html .= '>' . esc_html($content) . '</' . esc_attr($tag) . '>';
        return $html;
    }
}

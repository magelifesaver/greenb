<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce\Settings;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Setting;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
/**
 * Settings section fo WooCommerce settings pages.
 *
 * @since 1.1.0
 *
 * @method string get_id()
 * @method string get_title()
 * @method string get_description()
 * @method array<int|string, mixed>|Setting[] get_settings()
 * @method $this set_id( string $id )
 * @method $this set_title( string $title )
 * @method $this set_description( string $description )
 * @method $this set_settings( array|Setting[] $settings )
 */
class Settings_Section
{
    use Creates_New_Instances;
    use Has_Accessors;
    /** @var string */
    protected string $title = '';
    /** @var string */
    protected string $description = '';
    /** @var array<int|string, array<string, mixed>>|Setting[] */
    protected array $settings = [];
    /**
     * Constructor.
     *
     * @since 1.1.0
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->set_properties($data);
    }
    /**
     * Returns the settings definitions for the section.
     *
     * @since 1.1.0
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_settings_definitions(): array
    {
        $adapted_settings = [['title' => $this->get_title() ?: '', 'type' => 'title', 'desc' => $this->get_description() ?: '']];
        foreach ($this->settings as $setting) {
            // @phpstan-ignore-next-line
            if (is_array($setting)) {
                $adapted_settings[] = $setting;
            } elseif ($setting instanceof Setting) {
                $should_display = $setting->get_display_condition();
                if ($should_display && !call_user_func($should_display)) {
                    continue;
                }
                $adapted_settings[] = Setting_Adapter::from_object($setting)->to_array();
            }
        }
        $adapted_settings[] = ['type' => 'sectionend'];
        return $adapted_settings;
    }
}

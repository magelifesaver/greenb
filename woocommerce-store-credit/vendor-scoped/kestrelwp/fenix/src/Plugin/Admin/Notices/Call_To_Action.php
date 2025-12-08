<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Admin\Notices;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Creates_New_Instances;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Traits\Has_Accessors;
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
 * @method array<string, string> get_attributes()
 * @method $this set_id( string $id )
 * @method $this set_label( string $label )
 * @method $this set_url( ?string $url )
 * @method $this set_target( string $target )
 * @method $this set_primary( bool $primary )
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
}

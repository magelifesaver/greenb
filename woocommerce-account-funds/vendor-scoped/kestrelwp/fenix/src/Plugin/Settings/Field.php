<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Enum;
/**
 * Setting field types.
 *
 * @since 1.1.0
 */
class Field
{
    use Is_Enum;
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/checkbox */
    public const CHECKBOX = 'checkbox';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/color */
    public const COLOR = 'color';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date */
    public const DATE = 'date';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/email */
    public const EMAIL = 'email';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file */
    public const FILE = 'file';
    /** @var string custom field used by WooCommerce to handle financial amounts, in other contexts this could be interpreted as {@see Field::NUMBER} or {@see Field::TEXT} */
    public const FINANCIAL_AMOUNT = 'price';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/hidden */
    public const HIDDEN = 'hidden';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/number */
    public const NUMBER = 'number';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/password */
    public const PASSWORD = 'password';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/radio */
    public const RADIO = 'radio';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/range */
    public const RANGE = 'range';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/search */
    public const SEARCH = 'search';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/select */
    public const SELECT = 'select';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/tel */
    public const TEL = 'tel';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/text */
    public const TEXT = 'text';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/textarea */
    public const TEXTAREA = 'textarea';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/time */
    public const TIME = 'time';
    /** @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/url */
    public const URL = 'url';
}

<?php
/**
 * Woo Discount Rules: Collections
 *
 * @package   wdr-collections
 * @author    Anantharaj B <anantharaj@flycart.org>
 * @copyright 2022 Flycart
 * @license   GPL-3.0-or-later
 * @link      https://flycart.org
 */

namespace WDR_COL\App\Helpers;

defined('ABSPATH') or exit;

class Input
{
    /**
     * Sanitize callbacks
     *
     * @var array
     */
    protected static $sanitize_callbacks = [
        'text' => 'sanitize_text_field',
        'title' => 'sanitize_title',
        'email' => 'sanitize_email',
        'url' => 'sanitize_url',
        'key' => 'sanitize_key',
        'meta' => 'sanitize_meta',
        'option' => 'sanitize_option',
        'file' => 'sanitize_file_name',
        'mime' => 'sanitize_mime_type',
        'class' => 'sanitize_html_class',
    ];

    /**
     * Sanitize
     *
     * @param string|array $value
     * @param string $type
     * @return string|array
     */
    public static function sanitize($value, $type = 'text')
    {
        if (!array_key_exists($type, self::$sanitize_callbacks)) {
            throw new \UnexpectedValueException('Expected a valid type on sanitize method');
        }

        $callback = self::$sanitize_callbacks[$type];

        if (is_array($value)) {
            return self::sanitizeRecursively($value, $callback);
        }
        return call_user_func($callback, $value);
    }

    /**
     * Sanitize recursively
     *
     * @param array $array
     * @param string $callback
     * @return array
     */
    public static function sanitizeRecursively(&$array, $callback)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::sanitizeRecursively($value, $callback);
            } else {
                $value = call_user_func($callback, $value);
            }
        }
        return $array;
    }

    /**
     * HTML filter
     *
     * @param string $value
     * @param array $allowed_html
     * @return string
     */
    public static function filterHtml($value, $allowed_html = [])
    {
        return wp_kses($value, $allowed_html);
    }
}
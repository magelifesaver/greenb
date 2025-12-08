<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Http\Url;
defined('ABSPATH') or exit;
/**
 * Helper class for string operations.
 *
 * @since 1.0.0
 */
final class Strings
{
    /** @var string */
    public const CAMEL_CASE = 'camelCase';
    /** @var string */
    public const KEBAB_CASE = 'kebab-case';
    /** @var string */
    public const PASCAL_CASE = 'PascalCase';
    /** @var string */
    public const SNAKE_CASE = 'snake_case';
    /** @var string */
    private static string $string;
    /**
     * Constructor.
     *
     * @param string $string
     */
    private function __construct(string $string)
    {
        self::$string = $string;
    }
    /**
     * Initializes a string to perform operations on.
     *
     * @since 1.0.0
     *
     * @param string $string
     * @return Strings
     */
    public static function string(string $string): Strings
    {
        return new self($string);
    }
    /**
     * Returns the string in context.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function to_string(): string
    {
        return self::$string;
    }
    /**
     * Returns the boolean value of a string.
     *
     * @since 1.3.0
     *
     * @return bool
     */
    public function to_boolean(): bool
    {
        return in_array($this->to_string(), Booleans::truthy_values(), \true);
    }
    /**
     * Determines if the string is empty.
     *
     * @since 1.4.0
     *
     * @return bool
     */
    public function is_empty(): bool
    {
        return '' === trim(self::$string);
    }
    /**
     * Determines if a variable expresses a semantic version.
     *
     * Note this check is loose and will also accept "x.y" other than "x.y.z" with or without suffixes, unless strict check is specified.
     *
     * @since 1.0.0
     *
     * @param mixed $value
     * @param bool $strict
     * @return bool
     */
    public static function is_semver($value, bool $strict = \false): bool
    {
        // discard non-string vales and integer-like strings
        if (!is_string($value) || is_numeric($value) && strpos($value, '.') === \false) {
            return \false;
        }
        if ($strict) {
            return (bool) preg_match('/\b\d+\.\d+\.\d+(?:-[a-zA-Z0-9.-]+)?(?:\+[a-zA-Z0-9.-]+)?\b/', $value);
        }
        return (bool) preg_match('/\b\d+(\.\d+){0,2}(?:-[a-zA-Z0-9.-]+)?(?:\+[a-zA-Z0-9.-]+)?\b/', $value);
    }
    /**
     * Determines if a variable is a JSON string.
     *
     * @since 1.0.0
     *
     * @param mixed $value
     * @return bool
     */
    public static function is_json($value): bool
    {
        if (!is_string($value)) {
            return \false;
        }
        json_decode($value);
        return json_last_error() === \JSON_ERROR_NONE;
    }
    /**
     * Determines if a variable is a valid URL.
     *
     * @see Url for more advanced URL handling
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @param string[]|null $allowed_protocols optional (defaults to http and https, null to allow all)
     * @return bool
     */
    public static function is_url($value, ?array $allowed_protocols = ['http', 'https']): bool
    {
        if (!is_string($value)) {
            return \false;
        }
        if (!filter_var($value, \FILTER_VALIDATE_URL)) {
            return \false;
        }
        if (!$allowed_protocols) {
            return \true;
        }
        $url = wp_parse_url($value);
        if (!$url || !isset($url['scheme']) || !in_array($url['scheme'], $allowed_protocols, \true)) {
            return \false;
        }
        return \true;
    }
    /**
     * Determines if a variable is a valid email address.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return bool
     */
    public static function is_email($value): bool
    {
        return is_string($value) && is_email($value);
    }
    /**
     * Determines if a variable is a valid shortcode.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @param string|null $tag optional (if omitted checks whether the value is a literal shortcode tag, otherwise checks if the value is the specified shortcode)
     * @return bool
     */
    public static function is_shortcode($value, ?string $tag = null): bool
    {
        if (!is_string($value)) {
            return \false;
        }
        if (null === $tag) {
            return shortcode_exists($value);
        }
        $value = new self(trim($value));
        return $value->starts_with('[') && $value->ends_with(']') && has_shortcode($value->to_string(), $tag);
    }
    /**
     * Determines if a value is contained in the string.
     *
     * @since 1.0.0
     *
     * @param mixed $value
     * @return bool
     */
    public function contains($value): bool
    {
        foreach ((array) $value as $needle) {
            if ($needle === '') {
                continue;
            }
            if (is_string($needle) || is_numeric($needle)) {
                if (\false === mb_strpos(self::$string, (string) $needle)) {
                    return \false;
                }
            } else {
                return \false;
            }
        }
        return \true;
    }
    /**
     * Determines if the string starts with a given string.
     *
     * @since 1.0.0
     *
     * @param string $substring
     * @return bool
     */
    public function starts_with(string $substring): bool
    {
        // @TODO replace with str_starts_with when PHP 8.0 is the minimum requirement
        return substr(self::$string, 0, strlen($substring)) === $substring;
    }
    /**
     * Determines if the string ends with a given string.
     *
     * @param string $substring
     * @return bool
     */
    public function ends_with(string $substring): bool
    {
        if ('' === $substring || $substring === self::$string) {
            return \true;
        }
        if ('' === self::$string) {
            return \false;
        }
        $length = strlen($substring);
        // @TODO replace with str_ends_with when PHP 8.0 is the minimum requirement
        return $length <= strlen(self::$string) && 0 === substr_compare(self::$string, $substring, -$length);
    }
    /**
     * Replaces one or more strings.
     *
     * @since 1.1.0
     *
     * @param string|string[] $what
     * @param string|string[] $with
     * @return $this
     */
    public function replace($what, $with): self
    {
        self::$string = str_replace($what, $with, self::$string);
        return $this;
    }
    /**
     * Appends a string to the current string.
     *
     * @since 1.0.0
     *
     * @param string $string
     * @return $this
     */
    public function append(string $string): self
    {
        self::$string .= $string;
        return $this;
    }
    /**
     * Prepends a string to the current string.
     *
     * @since 1.0.0
     *
     * @param string $string
     * @return $this
     */
    public function prepend(string $string): self
    {
        self::$string = $string . self::$string;
        return $this;
    }
    /**
     * Appends a trailing slash to the string, if not present already.
     *
     * @since 1.1.0
     *
     * @return $this
     */
    public function with_trailing_slash(): self
    {
        self::$string = trailingslashit(self::$string);
        return $this;
    }
    /**
     * Removes a trailing slash from the string, if present.
     *
     * @since 1.1.0
     *
     * @return $this
     */
    public function without_trailing_slash(): self
    {
        self::$string = untrailingslashit(self::$string);
        return $this;
    }
    /**
     * Ensures the string ends with a period.
     *
     * @since 1.6.0
     *
     * @return $this
     */
    public function ensure_period(): self
    {
        if (!$this->ends_with('.')) {
            self::$string .= '.';
        }
        return $this;
    }
    /**
     * Removes a trailing period from the string, if present.
     *
     * @since 1.6.0
     *
     * @return $this
     */
    public function remove_period(): self
    {
        self::$string = rtrim(self::$string, '.');
        return $this;
    }
    /**
     * Converts the string to lowercase.
     *
     * @since 1.0.0
     *
     * @return $this
     */
    public function lowercase(): self
    {
        self::$string = mb_strtolower(self::$string, 'UTF-8');
        return $this;
    }
    /**
     * Converts the first character of the string to lowercase.
     *
     * @since 1.0.0
     *
     * @return $this
     */
    public function lowercase_first(): self
    {
        self::$string = lcfirst(self::$string);
        return $this;
    }
    /**
     * Converts the string to uppercase.
     *
     * @since 1.0.0
     *
     * @return $this
     */
    public function uppercase(): self
    {
        self::$string = mb_strtoupper(self::$string, 'UTF-8');
        return $this;
    }
    /**
     * Converts the first character of the string to uppercase.
     *
     * @since 1.0.0
     *
     * @return $this
     */
    public function uppercase_first(): self
    {
        self::$string = ucfirst(self::$string);
        return $this;
    }
    /**
     * Strips non-alphanumeric characters from the string.
     *
     * @since 1.0.0
     *
     * @param string $replace_with characters to replace non-alphanumeric with
     * @return $this
     */
    public function alphanumeric_only(string $replace_with = ''): self
    {
        self::$string = trim((string) preg_replace('/[^\p{L}0-9' . implode('', []) . ']+/iu', $replace_with, self::$string));
        return $this;
    }
    /**
     * Converts the string to snake_case.
     *
     * @since 1.0.0
     *
     * @return $this
     */
    public function snake_case(): self
    {
        // inserts spaces between words
        self::$string = preg_replace('/(?<=[a-z])(?=[A-Z])/u', ' ', self::$string);
        return $this->alphanumeric_only('_')->lowercase();
    }
    /**
     * Converts the string to kebab-case.
     *
     * @since 1.0.0
     *
     * @return $this
     */
    public function kebab_case(): self
    {
        // inserts spaces between words
        self::$string = preg_replace('/(?<=[a-z])(?=[A-Z])/u', ' ', self::$string);
        return $this->alphanumeric_only('-')->lowercase();
    }
    /**
     * Converts the string to camelCase.
     *
     * @since 1.0.0
     *
     * @return $this
     */
    public function camel_case(): self
    {
        return $this->pascal_case()->lowercase_first();
    }
    /**
     * Converts the string to PascalCase.
     *
     * @since 1.0.0
     *
     * @return $this
     */
    public function pascal_case(): self
    {
        $string = self::$string;
        $words = preg_split('/
			(?<=\p{Ll})(?=\p{Lu}) # split between lowercase and uppercase letters
			|                     # OR
			[^A-Za-z0-9]+         # split at any non-alphanumeric characters
		/xu', $string, -1, \PREG_SPLIT_NO_EMPTY);
        self::$string = implode('', array_map('ucfirst', $words));
        return $this;
    }
    /**
     * Converts the string to the specified case.
     *
     * @since 1.1.0
     *
     * @param string $case
     * @return $this
     */
    public function convert_case(string $case): self
    {
        switch ($case) {
            case self::CAMEL_CASE:
                return $this->camel_case();
            case self::KEBAB_CASE:
                return $this->kebab_case();
            case self::PASCAL_CASE:
                return $this->pascal_case();
            case self::SNAKE_CASE:
                return $this->snake_case();
            default:
                return $this;
        }
    }
}

<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Url;
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
    /** @var string string in context */
    private string $string;
    /**
     * Constructor.
     *
     * @param string $string
     */
    private function __construct(string $string)
    {
        $this->string = $string;
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
        return $this->string;
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
        return '' === trim($this->string);
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
            $result = preg_match('/\b\d+\.\d+\.\d+(?:-[a-zA-Z0-9.-]+)?(?:\+[a-zA-Z0-9.-]+)?\b/', $value);
        } else {
            $result = preg_match('/\b\d+(\.\d+){0,2}(?:-[a-zA-Z0-9.-]+)?(?:\+[a-zA-Z0-9.-]+)?\b/', $value);
        }
        return \false !== $result && $result > 0;
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
     * @param string|null $operator 'AND'|'OR' optional (defaults to 'AND' for single values, 'OR' for arrays)
     * @return bool
     */
    public function contains($value, ?string $operator = null): bool
    {
        if (null === $operator) {
            if (is_array($value)) {
                $operator = 'OR';
            } else {
                $operator = 'AND';
            }
        } else {
            $operator = strtoupper($operator);
        }
        if (!in_array($operator, ['AND', 'OR'], \true)) {
            return \false;
        }
        foreach ((array) $value as $needle) {
            if ($needle === '') {
                continue;
            }
            if (is_string($needle) || is_numeric($needle)) {
                if ('OR' === $operator && mb_strpos($this->string, (string) $needle) !== \false) {
                    return \true;
                } elseif ('AND' === $operator && mb_strpos($this->string, (string) $needle) === \false) {
                    return \false;
                }
            } else {
                return \false;
            }
        }
        return 'AND' === $operator;
    }
    /**
     * Determines if any of the specified values are contained in the string.
     *
     * @since 1.8.1
     *
     * @param array<mixed> $values
     * @return bool
     */
    public function contains_any(array $values): bool
    {
        return $this->contains($values, 'OR');
    }
    /**
     * Determines if all of the specified values are contained in the string.
     *
     * @since 1.8.1
     *
     * @param array<mixed> $values
     * @return bool
     */
    public function contains_all(array $values): bool
    {
        return $this->contains($values, 'AND');
    }
    /**
     * Determines if the string starts with a given string.
     *
     * @since 1.0.0
     *
     * @param mixed|string|string[]|Strings|Strings[] $substring
     * @return bool
     */
    public function starts_with($substring): bool
    {
        if ($substring instanceof Strings) {
            $substring = $substring->to_string();
        }
        if (is_array($substring)) {
            foreach ($substring as $sub) {
                if ($this->starts_with($sub)) {
                    return \true;
                }
            }
            return \false;
        } elseif (is_string($substring)) {
            // @TODO replace with str_starts_with when PHP 8.0 is the minimum requirement
            return '' === $substring || $substring === $this->string || substr($this->string, 0, strlen($substring)) === $substring;
        }
        return \false;
    }
    /**
     * Determines if the string ends with a given string.
     *
     * @since  1.0.0
     *
     * @param mixed|string|string[]|Strings|Strings[] $substring
     * @return bool
     */
    public function ends_with($substring): bool
    {
        if ($substring instanceof Strings) {
            $substring = $substring->to_string();
        }
        if (is_array($substring)) {
            foreach ($substring as $sub) {
                if ($this->ends_with($sub)) {
                    return \true;
                }
            }
            return \false;
        } elseif (is_string($substring)) {
            $length = strlen($substring);
            // @TODO replace with str_ends_with when PHP 8.0 is the minimum requirement
            return '' === $substring || $substring === $this->string || $length <= strlen($this->string) && 0 === substr_compare($this->string, $substring, -$length);
        }
        return \false;
    }
    /**
     * Determines if the string is equal to another string.
     *
     * @since 1.7.1
     *
     * @param mixed|string|string[]|Strings|Strings[] $to
     * @return bool
     */
    public function equals($to): bool
    {
        if ($to instanceof Strings) {
            $to = $to->to_string();
        } elseif (is_array($to)) {
            foreach ($to as $string) {
                if ($this->equals($string)) {
                    return \true;
                }
            }
            return \false;
        } elseif (!is_string($to)) {
            return \false;
        }
        return $this->string === $to;
    }
    /**
     * Returns the portion of the string after the first occurrence of a given string.
     *
     * @since 1.7.2
     *
     * @param string $string
     * @return $this
     */
    public function before(string $string): self
    {
        if ($string === '') {
            return $this;
        } elseif ($this->starts_with($string)) {
            $this->string = '';
        } else {
            $this->string = strstr($this->string, $string, \true) ?: '';
        }
        return $this;
    }
    /**
     * Returns the portion of the string after the first occurrence of a given string.
     *
     * @since 1.7.2
     *
     * @param string $string
     * @return $this
     */
    public function after(string $string): self
    {
        if ($string === '') {
            return $this;
        } elseif ($this->ends_with($string)) {
            $this->string = '';
        } elseif ($substring = strstr($this->string, $string)) {
            $this->string = substr($substring, strlen($string));
        } else {
            $this->string = '';
        }
        return $this;
    }
    /**
     * Trims characters or whitespace from the start of the string.
     *
     * @since 1.7.1
     *
     * @param string|null $characters
     * @return $this
     */
    public function trim_start(?string $characters = null): self
    {
        if (null === $characters) {
            $this->string = ltrim($this->string);
        } else {
            $this->string = ltrim($this->string, $characters);
        }
        return $this;
    }
    /**
     * Trims characters or whitespace from the end of the string.
     *
     * @since 1.7.1
     *
     * @param string|null $characters
     * @return $this
     */
    public function trim_end(?string $characters = null): self
    {
        if (null === $characters) {
            $this->string = rtrim($this->string);
        } else {
            $this->string = rtrim($this->string, $characters);
        }
        return $this;
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
        $this->string = str_replace($what, $with, $this->string);
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
        $this->string .= $string;
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
        $this->string = $string . $this->string;
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
        $this->string = trailingslashit($this->string);
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
        $this->string = untrailingslashit($this->string);
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
            $this->string .= '.';
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
        $this->string = rtrim($this->string, '.');
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
        $this->string = mb_strtolower($this->string, 'UTF-8');
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
        $this->string = lcfirst($this->string);
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
        $this->string = mb_strtoupper($this->string, 'UTF-8');
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
        $this->string = ucfirst($this->string);
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
        $this->string = trim((string) preg_replace('/[^\p{L}0-9' . implode('', []) . ']+/iu', $replace_with, $this->string));
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
        $this->string = preg_replace('/(?<=[a-z])(?=[A-Z])/u', ' ', $this->string);
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
        $this->string = preg_replace('/(?<=[a-z])(?=[A-Z])/u', ' ', $this->string);
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
        $string = $this->string;
        $words = preg_split('/
			(?<=\p{Ll})(?=\p{Lu}) # split between lowercase and uppercase letters
			|                     # OR
			[^A-Za-z0-9]+         # split at any non-alphanumeric characters
		/xu', $string, -1, \PREG_SPLIT_NO_EMPTY);
        $this->string = implode('', array_map('ucfirst', $words));
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

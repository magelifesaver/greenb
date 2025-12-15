<?php

declare(strict_types=1);

namespace ACA\JetEngine\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Setting\Formatter;
use AC\Type\Value;
use ACA\JetEngine\Field;

class Media implements Formatter
{

    private $field;

    public function __construct(Field\Field $field)
    {
        $this->field = $field;
    }

    public function format(Value $value)
    {
        if ( ! $value->get_value()) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $url = $this->get_media_url_by_value((string)$value->get_value());

        $label = $url
            ? ac_helper()->html->link($url, esc_html(basename($url)), ['target' => '_blank'])
            : '<em>' . __('Invalid attachment', 'codepress-admin-columns') . '</em>';

        return $value->with_value($label);
    }

    private function get_media_url_by_value($value): ?string
    {
        if ( ! $this->field instanceof Field\ValueFormat) {
            return $value;
        }

        switch ($this->field->get_value_format()) {
            case Field\ValueFormat::FORMAT_ID:
                return wp_get_attachment_url($value) ?: null;
            case Field\ValueFormat::FORMAT_BOTH:
                return is_array($value) && isset($value['url']) ? $value['url'] : null;
            default:
                return $value;
        }
    }

}
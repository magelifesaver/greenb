<?php

declare(strict_types=1);

namespace ACA\ACF\Export;

use AC\Setting\Formatter;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\Collection\Separator;
use AC\Value\Formatter\ForeignId;
use AC\Value\Formatter\Media\AttachmentUrl;
use AC\Value\Formatter\StripNewLine;
use ACA\ACF\Field;
use ACA\ACF\FieldType;
use ACA\ACF\Value\Formatter\RelationIdCollection;
use ACP;

class ServiceFactory
{

    public function create(Field $field, Formatter $formatter, FormatterCollection $formatters): ?ACP\Export\Service
    {
        switch ($field->get_type()) {
            case FieldType::TYPE_DATE_PICKER:
                return new Model\Date($formatter);
            case FieldType::TYPE_LINK:
                return new Model\Link($formatter);
            case FieldType::TYPE_GALLERY:
                return new ACP\Export\Model\FormatterCollection(
                    new FormatterCollection([
                        $formatter,
                        new RelationIdCollection(),
                        new AttachmentUrl(),
                        new Separator(','),
                    ])
                );

            case FieldType::TYPE_FILE:
            case FieldType::TYPE_IMAGE:
                return new ACP\Export\Model\FormatterCollection(
                    new FormatterCollection([
                        $formatter,
                        new ForeignId(),
                        new AttachmentUrl(),
                    ])
                );

            // Only apply base formatter
            case FieldType::TYPE_PASSWORD:
            case FieldType::TYPE_TIME_PICKER:
            case FieldType::TYPE_OEMBED:
            case FieldType::TYPE_NUMBER:
            case FieldType::TYPE_BOOLEAN:
            case FieldType::TYPE_COLOR_PICKER:
                return new ACP\Export\Model\FormatterCollection(new FormatterCollection([$formatter]));

            // Stripped render value
            default:
                return new ACP\Export\Model\FormatterCollection($formatters->with_formatter(new StripNewLine()));
        }
    }

}
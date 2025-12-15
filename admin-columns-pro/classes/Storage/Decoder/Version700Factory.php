<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\ColumnFactories\Aggregate;
use AC\TableScreenFactory;
use ACP\ConditionalFormat;
use ACP\Storage\Decoder;
use ACP\Storage\DecoderFactory;

final class Version700Factory implements DecoderFactory
{

    private ConditionalFormat\Decoder $conditional_format_decoder;

    private TableScreenFactory $table_screen_factory;

    private Aggregate $column_factory;

    public function __construct(
        ConditionalFormat\Decoder $conditional_format_decoder,
        TableScreenFactory $table_screen_factory,
        Aggregate $column_factory
    ) {
        $this->conditional_format_decoder = $conditional_format_decoder;
        $this->table_screen_factory = $table_screen_factory;
        $this->column_factory = $column_factory;
    }

    public function create(array $encoded_data): Decoder
    {
        return new Version700(
            $encoded_data,
            $this->conditional_format_decoder,
            $this->table_screen_factory,
            $this->column_factory
        );
    }

}
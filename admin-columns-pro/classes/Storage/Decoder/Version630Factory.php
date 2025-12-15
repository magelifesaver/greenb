<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\ColumnFactories\Aggregate;
use AC\TableScreenFactory;
use ACP\Storage\Decoder;
use ACP\Storage\DecoderFactory;

final class Version630Factory implements DecoderFactory
{

    private TableScreenFactory $table_screen_factory;

    private Aggregate $column_factory;

    public function __construct(
        TableScreenFactory $table_screen_factory,
        Aggregate $column_factory
    ) {
        $this->table_screen_factory = $table_screen_factory;
        $this->column_factory = $column_factory;
    }

    public function create(array $encoded_data): Decoder
    {
        return new Version630(
            $encoded_data,
            $this->table_screen_factory,
            $this->column_factory
        );
    }

}
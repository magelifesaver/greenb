<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\ColumnFactories\Aggregate;
use AC\TableScreenFactory;
use ACP\Storage\Decoder;
use ACP\Storage\DecoderFactory;

final class Version510Factory implements DecoderFactory
{

    private TableScreenFactory $list_screen_factory;

    private Aggregate $column_factory;

    public function __construct(TableScreenFactory $list_screen_factory, Aggregate $column_factory)
    {
        $this->list_screen_factory = $list_screen_factory;
        $this->column_factory = $column_factory;
    }

    public function create(array $encoded_data): Decoder
    {
        return new Version510($encoded_data, $this->list_screen_factory, $this->column_factory);
    }

}
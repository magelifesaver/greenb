<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\ColumnFactories\Aggregate;
use AC\ColumnIterator;
use AC\ColumnIterator\ProxyColumnIterator;
use AC\ColumnRepository\EncodedData;
use AC\ListScreen;
use AC\Plugin\Version;
use AC\Setting\ConfigCollection;
use AC\TableScreen;
use AC\TableScreenFactory;
use AC\Type\ListScreenId;
use AC\Type\ListScreenStatus;
use AC\Type\TableId;
use ACP\Exception\NonDecodableDataException;
use DateTime;
use Exception;

final class Version510 extends BaseDecoder implements ListScreenDecoder
{

    private TableScreenFactory $table_screen_factory;

    private Aggregate $column_factory;

    public function __construct(
        array $encoded_data,
        TableScreenFactory $table_screen_factory,
        Aggregate $column_factory
    ) {
        parent::__construct($encoded_data);

        $this->table_screen_factory = $table_screen_factory;
        $this->column_factory = $column_factory;
    }

    public function get_version(): Version
    {
        return new Version('5.1.0');
    }

    public function has_list_screen(): bool
    {
        try {
            $list_key = new TableId($this->encoded_data['type'] ?? '');
        } catch (Exception $e) {
            return false;
        }

        if ( ! $this->table_screen_factory->can_create($list_key)) {
            return false;
        }

        return true;
    }

    public function get_list_screen(): ListScreen
    {
        if ( ! $this->has_required_version() || ! $this->has_list_screen()) {
            throw new NonDecodableDataException($this->encoded_data);
        }

        $table_screen = $this->table_screen_factory->create(new TableId($this->encoded_data['type']));

        return new ListScreen(
            new ListScreenId($this->encoded_data['id']),
            $this->encoded_data['title'] ?? '',
            $table_screen,
            $this->create_column_iterator($table_screen, $this->encoded_data['columns'] ?? []),
            $this->encoded_data['settings'] ?? [],
            new ListScreenStatus($this->encoded_data['status'] ?? null),
            DateTime::createFromFormat('U', (string)$this->encoded_data['updated'])
        );
    }

    private function create_column_iterator(TableScreen $table_screen, array $encoded_columns): ColumnIterator
    {
        foreach ($encoded_columns as $name => $encoded_column) {
            // Older decoders did not set the `name` property
            if (empty($encoded_column['name'])) {
                $encoded_columns[$name]['name'] = $name;
            }
        }

        return new ProxyColumnIterator(
            new EncodedData(
                $this->column_factory->create($table_screen),
                ConfigCollection::create_from_array($encoded_columns)
            )
        );
    }

}
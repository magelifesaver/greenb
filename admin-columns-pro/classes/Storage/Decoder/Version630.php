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
use ACP\Search\Entity\Segment;
use ACP\Search\SegmentCollection;
use ACP\Search\Type\SegmentKey;
use DateTime;
use Exception;

class Version630 extends BaseDecoder implements SegmentsDecoder, ListScreenDecoder
{

    public const SEGMENTS = 'segments';

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
        return new Version('6.3');
    }

    public function has_segments(): bool
    {
        $segments = $this->encoded_data[self::SEGMENTS] ?? null;

        return $segments && is_array($segments);
    }

    public function get_segments(): SegmentCollection
    {
        if ( ! $this->has_required_version() || ! $this->has_segments()) {
            throw new NonDecodableDataException($this->encoded_data);
        }

        $segments = [];

        foreach ($this->encoded_data[self::SEGMENTS] as $encoded_segment) {
            // Backwards compatibility for segments that have not stored their creation date
            $date_created = isset($encoded_segment['date_created'])
                ? DateTime::createFromFormat('U', (string)$encoded_segment['date_created'])
                : new DateTime();

            $segments[] = new Segment(
                new SegmentKey($encoded_segment['key']),
                $encoded_segment['name'],
                $encoded_segment['url_parameters'],
                new ListScreenId($encoded_segment['list_screen_id']),
                null,
                $date_created
            );
        }

        return new SegmentCollection($segments);
    }

    public function has_list_screen(): bool
    {
        try {
            $list_key = new TableId($this->encoded_data['list_screen']['type'] ?? '');
        } catch (Exception $e) {
            return false;
        }

        return $this->table_screen_factory->can_create($list_key);
    }

    public function get_list_screen(): ListScreen
    {
        if ( ! $this->has_required_version() || ! $this->has_list_screen()) {
            throw new NonDecodableDataException($this->encoded_data);
        }

        $data = $this->encoded_data['list_screen'];

        $preferences = $data['settings'] ?? [];

        $table_screen = $this->table_screen_factory->create(new TableId($data['type']));

        $list_screen = new ListScreen(
            new ListScreenId($data['id']),
            $data['title'] ?? '',
            $table_screen,
            $this->create_column_iterator($table_screen, $data['columns'] ?? []),
            $preferences,
            new ListScreenStatus($data['status'] ?? null),
            DateTime::createFromFormat('U', (string)$data['updated'])
        );

        if ($this->has_segments()) {
            $list_screen->set_segments($this->get_segments());
        }

        return $list_screen;
    }

    private function create_column_iterator(TableScreen $table_screen, array $encoded_columns): ColumnIterator
    {
        return new ProxyColumnIterator(
            new EncodedData(
                $this->column_factory->create($table_screen),
                ConfigCollection::create_from_array($encoded_columns)
            )
        );
    }

}
<?php

namespace ACP\Export\Strategy;

use AC\ListTableFactory;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;
use WP_Query;

class Post extends Strategy
{

    private TableDataFactory $table_data_factory;

    private ResponseFactory $response_factory;

    public function __construct(TableDataFactory $table_data_factory, ResponseFactory $response_factory)
    {
        $this->table_data_factory = $table_data_factory;
        $this->response_factory = $response_factory;
    }

    public function get_total_items(): int
    {
        $table = ListTableFactory::create_from_globals();

        return $table
            ? $table->get_total_items()
            : 0;
    }

    public function filter_ids($clauses, WP_Query $query)
    {
        global $wpdb;

        if ($query->is_main_query() && $this->ids) {
            $clauses['where'] .= sprintf("\nAND $wpdb->posts.ID IN( %s )", implode(',', $this->ids));
        }

        return $clauses;
    }

    /**
     * Modify the main posts query to use the correct pagination arguments. This should be attached
     * to the pre_get_posts hook when an AJAX request is sent
     */
    public function modify_posts_query(WP_Query $query): void
    {
        if ($query->is_main_query()) {
            $query->set('nopaging', false);
            $query->set('offset', $this->counter * $this->items_per_iteration);
            $query->set('posts_per_page', $this->items_per_iteration);
            $query->set('posts_per_archive_page', $this->items_per_iteration);
            $query->set('fields', 'all');
        }
    }

    public function handle_export(): void
    {
        add_action('pre_get_posts', [$this, 'modify_posts_query'], 16);
        add_filter('posts_clauses', [$this, 'filter_ids'], 20, 2);
        add_filter('the_posts', [$this, 'send_response'], 10, 2);
    }

    public function send_response($posts, WP_Query $query)
    {
        if ( ! $query->is_main_query()) {
            return $posts;
        }

        $data = $this->table_data_factory->create(
            $this->columns,
            wp_list_pluck($posts, 'ID'),
            0 === $this->counter
        );

        $this->response_factory->create(
            $data
        );
        exit;
    }

}
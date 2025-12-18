<?php
/**
 * Data Manager
 *
 * Responsible for collecting posts, products or orders from WordPress and
 * preparing them for ingestion into the selected vector store.  It also
 * retrieves lists of available post types and meta keys.  Separating this
 * logic keeps the admin UI and scheduling code clean and focused.
 */
class Vector_Sync_Data_Manager {
    /**
     * Fetch all available post types.  Uses WordPress’s `get_post_types()`
     * function to retrieve objects representing each registered type【54297608815490†L55-L106】.
     * Only public post types and WooCommerce order/product types are included.
     *
     * @return array Array of WP_Post_Type objects keyed by slug.
     */
    public function get_all_post_types() {
        $args = array(
            'public'   => true,
        );
        $post_types = get_post_types( $args, 'objects' );
        // Explicitly include WooCommerce orders if present.  WooCommerce
        // registers orders as non‑public post types, so we need to add them
        // manually for indexing.
        $order_types = array( 'shop_order', 'shop_order_refund' );
        foreach ( $order_types as $ot ) {
            if ( post_type_exists( $ot ) && ! isset( $post_types[ $ot ] ) ) {
                $post_types[ $ot ] = get_post_type_object( $ot );
            }
        }
        // Add a pseudo post type for users so they can be indexed alongside
        // posts.  We create a minimal object with a labels property to
        // satisfy the admin UI.  Using stdClass avoids requiring WP_Post_Type.
        $user_type = new stdClass();
        $user_type->name = 'user';
        $user_type->labels = (object) array( 'name' => __( 'Users', 'vector-sync' ) );
        $post_types['user'] = $user_type;
        return $post_types;
    }

    /**
     * Retrieve a list of supported statuses for a given post type.  This
     * helper centralises logic for building status filters.  For orders we
     * return WooCommerce statuses; for other post types we return the core
     * statuses.  Users have no statuses.
     *
     * @param string $post_type Post type slug.
     * @return array Associative array of status slug => label.
     */
    public function get_statuses( $post_type ) {
        // No statuses for users.
        if ( 'user' === $post_type ) {
            return array();
        }
        // For WooCommerce orders and refunds, return order statuses if
        // WooCommerce is active.  wc_get_order_statuses() returns an array
        // mapping status slugs (e.g. wc-completed) to labels (e.g.
        // 'Completed')【157546820630183†L72-L83】.
        if ( in_array( $post_type, array( 'shop_order', 'shop_order_refund' ), true ) ) {
            if ( function_exists( 'wc_get_order_statuses' ) ) {
                return wc_get_order_statuses();
            }
            return array();
        }
        // For all other post types, use the built‑in post statuses.  We
        // exclude internal statuses like auto-draft and inherit.  The
        // get_post_statuses() function returns slug => label pairs for
        // registered statuses.
        $statuses = get_post_statuses();
        // Remove statuses not typically useful for indexing.
        unset( $statuses['auto-draft'], $statuses['inherit'] );
        return $statuses;
    }

    /**
     * Retrieve meta keys for a specific post type.  Uses a performance‑optimized
     * query from WordPress core ticket #24498【897998478852209†L75-L85】.  Keys
     * beginning with an underscore are excluded because they’re private or
     * internal.  For large databases this query can be expensive; consider
     * caching results or limiting to recently used keys.
     *
     * @param string $post_type Post type slug.
     * @return array List of meta keys.
     */
    public function get_meta_keys( $post_type ) {
        global $wpdb;
        // Handle user meta separately from post meta because user data lives
        // in wp_usermeta rather than wp_postmeta.
        if ( 'user' === $post_type ) {
            $sql = "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} WHERE meta_key NOT LIKE '\\_%' ORDER BY meta_key LIMIT 200";
            return $wpdb->get_col( $sql );
        }
        // Join against posts to limit to selected post type.  Exclude meta
        // keys beginning with an underscore, which are considered private
        // fields.  The original query used NOT BETWEEN '_' AND '_z', but that
        // inadvertently filters out valid keys that start with other
        // characters.  Instead we simply omit keys starting with an
        // underscore【897998478852209†L75-L85】.
        $sql = $wpdb->prepare(
            "SELECT DISTINCT pm.meta_key
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = %s
              AND pm.meta_key NOT LIKE '\\_%'
            ORDER BY pm.meta_key
            LIMIT 200",
            $post_type
        );
        return $wpdb->get_col( $sql );
    }

    /**
     * Count the number of items for a given post type based on optional
     * filters.  This helper is used by the admin UI to display counts
     * next to each content type, giving users feedback on how many
     * records will be synced.  The count respects the start date and
     * status filters provided.  For users, the count is the number of
     * registered users after the start date.
     *
     * @param string $post_type Post type slug or 'user'.
     * @param string $start_date Optional date in Y-m-d format.  Only items
     *                           modified/registered after this date are counted.
     * @param array  $statuses   Optional list of statuses to include.  If
     *                           empty, defaults are used as in get_posts().
     * @return int Number of items.
     */
    public function get_count_for_type( $post_type, $start_date = '', $statuses = array() ) {
        // Count users separately; no statuses apply.
        if ( 'user' === $post_type ) {
            $args = array(
                'fields' => 'ID',
            );
            $users = get_users( $args );
            if ( $start_date ) {
                $ts = strtotime( $start_date );
                $count = 0;
                foreach ( $users as $user_id ) {
                    $user = get_user_by( 'ID', $user_id );
                    if ( $user && strtotime( $user->user_registered ) >= $ts ) {
                        $count++;
                    }
                }
                return $count;
            }
            return count( $users );
        }
        // Determine statuses based on provided list or defaults.
        $status_list = array();
        if ( ! empty( $statuses ) ) {
            $status_list = array_values( $statuses );
        } else {
            if ( in_array( $post_type, array( 'shop_order', 'shop_order_refund' ), true ) ) {
                if ( function_exists( 'wc_get_order_statuses' ) ) {
                    $status_list = array_keys( wc_get_order_statuses() );
                } else {
                    $status_list = array( 'any' );
                }
            } else {
                $status_list = array( 'publish' );
            }
        }
        // Use WP_Query with posts_per_page=1 to avoid loading all posts.  The
        // found_posts property gives us the total number of matching posts.
        $args = array(
            'post_type'      => $post_type,
            'post_status'    => $status_list,
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
        );
        if ( $start_date ) {
            $args['date_query'] = array(
                array(
                    'after'     => $start_date,
                    'inclusive' => true,
                ),
            );
        }
        $query = new WP_Query( $args );
        return (int) $query->found_posts;
    }

    /**
     * Import existing data into the vector store based on saved options.  This
     * method loops through selected post types, fetches posts within the
     * specified date range and sends them to the API client for upsert.
     *
     * @param array $options Plugin settings saved from the admin page.
     */
    public function import_existing_data( array $options ) {
        $service     = $options['service'];
        $space_id    = $options['vector_space'];
        $post_types  = isset( $options['post_types'] ) ? (array) $options['post_types'] : array();
        $meta_map    = isset( $options['meta_fields'] ) ? (array) $options['meta_fields'] : array();
        $status_map  = isset( $options['statuses'] ) ? (array) $options['statuses'] : array();
        $start_date  = isset( $options['start_date'] ) ? $options['start_date'] : '';
        $api_client  = new Vector_Sync_Api_Client();
        foreach ( $post_types as $type ) {
            $meta_keys   = isset( $meta_map[ $type ] ) ? (array) $meta_map[ $type ] : array();
            $statuses    = isset( $status_map[ $type ] ) ? (array) $status_map[ $type ] : array();
            $posts       = $this->get_posts( $type, $start_date, $statuses );
            $vectors     = array();
            foreach ( $posts as $post ) {
                $vectors[] = $this->prepare_vector( $post, $meta_keys );
                // Batch upserts to avoid large payloads.
                if ( count( $vectors ) >= 50 ) {
                    $api_client->upsert_vectors( $service, $space_id, $vectors );
                    $vectors = array();
                }
            }
            if ( ! empty( $vectors ) ) {
                $api_client->upsert_vectors( $service, $space_id, $vectors );
            }
        }
    }

    /**
     * Perform an incremental sync of recent changes.  This function can be
     * hooked to events or scheduled and should only handle posts modified
     * since the last run.  The timestamp of the last run can be stored in
     * plugin options.
     *
     * @param array $options Plugin options.
     */
    public function sync_recent_changes( array $options ) {
        // For simplicity, call import_existing_data which reprocesses all data.
        // A production implementation should track last run time and only
        // process posts created/updated since then.  TODO: implement delta
        // synchronisation.
        $this->import_existing_data( $options );
    }

    /**
     * Query posts of a specific type filtered by date.
     *
     * @param string $post_type Post type slug.
     * @param string $start_date Optional start date in Y-m-d format.
     * @return WP_Post[] Array of posts.
     */
    private function get_posts( $post_type, $start_date = '', $statuses = array() ) {
        // Retrieve users separately; statuses do not apply.
        if ( 'user' === $post_type ) {
            $args = array(
                'fields' => 'all',
            );
            $users = get_users( $args );
            if ( $start_date ) {
                $filtered = array();
                foreach ( $users as $user ) {
                    if ( strtotime( $user->user_registered ) >= strtotime( $start_date ) ) {
                        $filtered[] = $user;
                    }
                }
                return $filtered;
            }
            return $users;
        }
        $args = array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'ASC',
            'date_query'     => array(),
        );
        // Determine statuses.  If specific statuses were provided, use them.
        if ( ! empty( $statuses ) ) {
            $args['post_status'] = array_values( $statuses );
        } else {
            // Default statuses: for orders include all statuses; for others use 'publish'.
            if ( in_array( $post_type, array( 'shop_order', 'shop_order_refund' ), true ) ) {
                if ( function_exists( 'wc_get_order_statuses' ) ) {
                    $args['post_status'] = array_keys( wc_get_order_statuses() );
                } else {
                    $args['post_status'] = 'any';
                }
            } else {
                $args['post_status'] = 'publish';
            }
        }
        if ( $start_date ) {
            $args['date_query'][] = array(
                'after'     => $start_date,
                'inclusive' => true,
            );
        }
        $query = new WP_Query( $args );
        return $query->posts;
    }

    /**
     * Prepare a single post for ingestion.  Creates a vector payload with a
     * unique ID, the embedding values placeholder and any metadata.
     *
     * @param WP_Post $post      Post object.
     * @param array   $meta_keys List of meta keys to include.
     * @return array Vector representation.
     */
    private function prepare_vector( $post, array $meta_keys ) {
        // When indexing users, build a synthetic content string and metadata
        // using user fields and meta.  Users have no post_title or
        // post_content, so we use display_name and other attributes.
        if ( $post instanceof WP_User ) {
            $content = trim( $post->display_name );
            // Append selected user meta fields.
            foreach ( $meta_keys as $key ) {
                $value = get_user_meta( $post->ID, $key, true );
                if ( ! empty( $value ) ) {
                    $content .= "\n\n" . $key . ": " . $value;
                }
            }
            // Placeholder embedding; would call external API for real vectors.【53352282232969†L204-L247】
            $embedding = array();
            $id        = 'user_' . $post->ID;
            return array(
                'id'     => $id,
                'values' => $embedding,
                'metadata' => array(
                    'post_type' => 'user',
                    'display_name' => $post->display_name,
                    'registered'   => $post->user_registered,
                    'email'        => $post->user_email,
                    'url'          => '',
                ),
            );
        }
        // Compose content from post title and content.
        $content = trim( $post->post_title . "\n\n" . $post->post_content );
        // Append selected meta fields.
        foreach ( $meta_keys as $key ) {
            $value = get_post_meta( $post->ID, $key, true );
            if ( ! empty( $value ) ) {
                $content .= "\n\n" . $key . ": " . $value;
            }
        }
        // Compute embedding using OpenAI API as placeholder.  In practice you
        // would call the embeddings endpoint (e.g. text-embedding-3-small) and
        // convert the response to a numeric vector.【53352282232969†L204-L247】
        $embedding = array();
        // Unique ID for the vector; use post ID prefixed with post type.
        $id = $post->post_type . '_' . $post->ID;
        return array(
            'id'     => $id,
            'values' => $embedding,
            'metadata' => array(
                'post_type' => $post->post_type,
                'title'     => $post->post_title,
                'date'      => $post->post_date,
                'url'       => get_permalink( $post ),
            ),
        );
    }
}
<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field;

use AC\Meta\Query;
use AC\Meta\QueryMetaFactory;
use AC\Type\PostTypeSlug;

trait MetaQueryTrait
{

    protected ?PostTypeSlug $post_type;

    protected function get_query_meta(): Query
    {
        $query = (new QueryMetaFactory())->create($this->field->get_name(), $this->field->get_meta_type());

        if ($this->post_type) {
            $query->where_post_type((string)$this->post_type);
        }

        return $query;
    }
}
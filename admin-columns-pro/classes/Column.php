<?php

declare(strict_types=1);

namespace ACP;

use AC;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\Sorting\Model\QueryBindings;

interface Column extends AC\Column
{

    public function editing(): ?Editing\Service;

    public function export(): ?Export\Service;

    public function sorting(): ?QueryBindings;

    public function search(): ?Search\Comparison;

    public function conditional_format(): ?FormattableConfig;

}
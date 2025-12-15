<?php

namespace ACA\MetaBox\Field;

interface Numeric
{

    public function get_min(): ?float;

    public function get_max(): ?float;

    public function get_step(): ?string;

}
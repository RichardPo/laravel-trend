<?php

namespace Flowframe\Trend\Adapters;

use Flowframe\Trend\TrendInterval;

abstract class AbstractAdapter
{
    abstract public function format(string $column, TrendInterval $interval): string;
}

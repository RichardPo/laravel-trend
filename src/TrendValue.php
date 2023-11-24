<?php

namespace Flowframe\Trend;

use Carbon\Carbon;

class TrendValue
{
    public function __construct(
        public Carbon $date,
        public mixed $aggregate,
    ) {
    }
}

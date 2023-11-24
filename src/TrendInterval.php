<?php

namespace Flowframe\Trend;

class TrendInterval
{
    public string $unit;

    public int $amount;

    public array $setUnits = [
        'minute',
        'hour',
        'day',
        'month',
        'year'
    ];

    public function __construct(string $unit, int $amount)
    {
        $this->unit = $unit;

        $this->amount = $amount;

        $arr = [];

        $reversed = array_reverse($this->setUnits);

        foreach($reversed as $item) {
            $arr[] = $item;

            if($item === $this->unit) {
                break;
            }
        }

        $this->setUnits = $arr;
    }
}

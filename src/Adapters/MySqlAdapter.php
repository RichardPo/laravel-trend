<?php

namespace Flowframe\Trend\Adapters;

use Flowframe\Trend\TrendInterval;

class MySqlAdapter extends AbstractAdapter
{
    public array $units = [
        'minute',
        'hour',
        'day',
        'month',
        'year'
    ];

    public function format(string $column, TrendInterval $interval): string
    {
        $str = str();

        $reversed = array_reverse($this->units);

        foreach($reversed as $unit) {
            if(!in_array($unit, $interval->setUnits)) {
                $str->append('0 as {$unit}');

                continue;
            }

            if($unit === $interval->unit) {
                $str->append('(datepart(${unit}, DT.[{$column}]) / {$interval->amount}) as {$unit}');
            }

            $str->append('datepart({$unit}, DT.[{$column}]) as {$unit}');
        }

        dd($str);

        return $str;
    }
}

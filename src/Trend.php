<?php

namespace Flowframe\Trend;

use Carbon\CarbonPeriod;
use Error;
use Flowframe\Trend\Adapters\MySqlAdapter;
use Flowframe\Trend\Adapters\PgsqlAdapter;
use Flowframe\Trend\Adapters\SqliteAdapter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Trend
{
    public TrendInterval $interval;

    public Carbon $start;

    public Carbon $end;

    public string $dateColumn = 'created_at';

    public string $dateAlias = 'date';

    public function __construct(public Builder $builder)
    {
    }

    public static function query(Builder $builder): self
    {
        return new static($builder);
    }

    public static function model(string $model): self
    {
        return new static($model::query());
    }

    public function between($start, $end): self
    {
        $this->start = $start;
        $this->end = $end;

        return $this;
    }

    private function interval(string $unit, int $amount): self
    {
        $this->interval = new TrendInterval($unit, $amount);

        return $this;
    }

    public function perMinute(): self
    {
        return $this->interval('minute', 1);
    }

    public function perHour(): self
    {
        return $this->interval('hour', 1);
    }

    public function perDay(): self
    {
        return $this->interval('day', 1);
    }

    public function perMonth(): self
    {
        return $this->interval('month', 1);
    }

    public function perYear(): self
    {
        return $this->interval('year', 1);
    }

    public function dateColumn(string $column): self
    {
        $this->dateColumn = $column;

        return $this;
    }

    public function dateAlias(string $alias): self
    {
        $this->dateAlias = $alias;

        return $this;
    }

    public function aggregate(string $column, string $aggregate): Collection
    {
        $values = $this->builder
            ->toBase()
            ->selectRaw("
                {$this->getSqlDate()}
                {$aggregate}({$column}) as aggregate
            ")
            ->whereBetween($this->dateColumn, [$this->start, $this->end])
            ->groupBy($this->dateAlias)
            ->orderBy($this->dateAlias)
            ->get();

        return $this->mapValuesToDates($values);
    }

    public function average(string $column): Collection
    {
        return $this->aggregate($column, 'avg');
    }

    public function min(string $column): Collection
    {
        return $this->aggregate($column, 'min');
    }

    public function max(string $column): Collection
    {
        return $this->aggregate($column, 'max');
    }

    public function sum(string $column): Collection
    {
        return $this->aggregate($column, 'sum');
    }

    public function count(string $column = '*'): Collection
    {
        return $this->aggregate($column, 'count');
    }

    public function mapValuesToDates(Collection $values): Collection
    {
        $values = $values->map(fn ($value) => new TrendValue(
            date: $value->{$this->dateAlias},
            aggregate: $value->aggregate,
        ));

        $placeholders = $this->getDatePeriod()->map(
            fn (Carbon $date) => new TrendValue(
                date: $date,
                aggregate: 0,
            )
        );

        return $values
            ->merge($placeholders)
            ->unique('date')
            ->sort()
            ->flatten();
    }

    protected function getDatePeriod(): Collection
    {
        return collect(
            CarbonPeriod::between(
                $this->start,
                $this->end,
            )->interval("{$this->interval->amount} {$this->interval->unit}")
        );
    }

    protected function getSqlDate(): string
    {
        $adapter = match ($this->builder->getConnection()->getDriverName()) {
            'mysql' => new MySqlAdapter(),
            'sqlite' => new SqliteAdapter(),
            'pgsql' => new PgsqlAdapter(),
            default => throw new Error('Unsupported database driver.'),
        };

        return $adapter->format($this->dateColumn, $this->interval->unit);
    }
}

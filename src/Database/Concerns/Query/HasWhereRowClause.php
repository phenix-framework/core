<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\Operator;

trait HasWhereRowClause
{
    public function whereRowEqual(array $columns, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::EQUAL, $this->prepareRowFields($columns));

        return $this;
    }

    public function whereRowDistinct(array $columns, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::DISTINCT, $this->prepareRowFields($columns));

        return $this;
    }

    public function whereRowGreaterThan(array $columns, Closure $subquery): static
    {
        $this->whereSubquery(
            $subquery,
            Operator::GREATER_THAN,
            $this->prepareRowFields($columns)
        );

        return $this;
    }

    public function whereRowGreaterThanOrEqual(array $columns, Closure $subquery): static
    {
        $this->whereSubquery(
            $subquery,
            Operator::GREATER_THAN_OR_EQUAL,
            $this->prepareRowFields($columns)
        );

        return $this;
    }

    public function whereRowLessThan(array $columns, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::LESS_THAN, $this->prepareRowFields($columns));

        return $this;
    }

    public function whereRowLessThanOrEqual(array $columns, Closure $subquery): static
    {
        $this->whereSubquery(
            $subquery,
            Operator::LESS_THAN_OR_EQUAL,
            $this->prepareRowFields($columns)
        );

        return $this;
    }

    public function whereRowIn(array $columns, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::IN, $this->prepareRowFields($columns));

        return $this;
    }

    public function whereRowNotIn(array $columns, Closure $subquery): static
    {
        $this->whereSubquery($subquery, Operator::NOT_IN, $this->prepareRowFields($columns));

        return $this;
    }

    private function prepareRowFields(array $fields)
    {
        return 'ROW(' . $this->prepareColumns($fields) . ')';
    }
}

<?php

declare(strict_types=1);

namespace Yiisoft\ActiveRecord;

/**
 * Compatibility stub for ActiveQuery
 * This is a temporary compatibility layer for alex-no/field-lingo package
 */
class ActiveQuery implements ActiveQueryInterface
{
    protected array $where = [];
    protected array $orderBy = [];
    protected array $groupBy = [];
    protected array $select = [];

    public function __construct(protected string $modelClass)
    {
    }

    public function where(array|string|null $condition): static
    {
        $this->where = is_array($condition) ? $condition : [$condition];
        return $this;
    }

    public function andWhere(array|string $condition): static
    {
        if (is_string($condition)) {
            $this->where[] = $condition;
        } else {
            $this->where = array_merge($this->where, $condition);
        }
        return $this;
    }

    public function orWhere(array|string $condition): static
    {
        return $this->andWhere($condition);
    }

    public function filterWhere(array $condition): static
    {
        $this->where = array_filter($condition, fn($v) => $v !== null && $v !== '');
        return $this;
    }

    public function andFilterWhere(array $condition): static
    {
        $filtered = array_filter($condition, fn($v) => $v !== null && $v !== '');
        $this->where = array_merge($this->where, $filtered);
        return $this;
    }

    public function orFilterWhere(array $condition): static
    {
        return $this->andFilterWhere($condition);
    }

    public function orderBy(array|string $columns): static
    {
        $this->orderBy = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    public function addOrderBy(array|string $columns): static
    {
        if (is_string($columns)) {
            $this->orderBy[] = $columns;
        } else {
            $this->orderBy = array_merge($this->orderBy, $columns);
        }
        return $this;
    }

    public function groupBy(array|string $columns): static
    {
        $this->groupBy = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    public function addGroupBy(array|string $columns): static
    {
        if (is_string($columns)) {
            $this->groupBy[] = $columns;
        } else {
            $this->groupBy = array_merge($this->groupBy, $columns);
        }
        return $this;
    }

    public function select(array|string $columns): static
    {
        $this->select = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    public function all(): array
    {
        // This is a stub - actual implementation would query the database
        return [];
    }

    public function one(): ?ActiveRecord
    {
        // This is a stub - actual implementation would query the database
        return null;
    }
}

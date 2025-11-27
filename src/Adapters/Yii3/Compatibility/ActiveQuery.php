<?php

declare(strict_types=1);

namespace Yiisoft\ActiveRecord;

/**
 * Compatibility stub for ActiveQuery
 * This is a temporary compatibility layer for alex-no/field-lingo package
 *
 * Provides basic query building and execution using PDO
 */
class ActiveQuery implements ActiveQueryInterface
{
    protected array $where = [];
    protected array $orderBy = [];
    protected array $groupBy = [];
    protected array $select = [];
    protected ?int $limit = null;
    protected ?int $offset = null;

    public function __construct(protected string $modelClass)
    {
    }

    public function where(array|string|null $condition): static
    {
        if ($condition === null) {
            $this->where = [];
        } elseif (is_string($condition)) {
            $this->where = ['_raw' => $condition];
        } else {
            $this->where = $condition;
        }
        return $this;
    }

    public function andWhere(array|string $condition): static
    {
        if (is_string($condition)) {
            $this->where[] = ['_raw' => $condition];
        } else {
            $this->where = array_merge($this->where, $condition);
        }
        return $this;
    }

    public function orWhere(array|string $condition): static
    {
        // Simplified: just treat as andWhere for now
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
        $this->orderBy = is_array($columns) ? $columns : [$columns => SORT_ASC];
        return $this;
    }

    public function addOrderBy(array|string $columns): static
    {
        if (is_string($columns)) {
            $this->orderBy[$columns] = SORT_ASC;
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

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Execute query and return all records
     */
    public function all(): array
    {
        $sql = $this->buildSql();
        $params = $this->buildParams();

        /** @var ActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        $db = $modelClass::getDb();

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $models = [];

        foreach ($rows as $row) {
            $models[] = $modelClass::populateRecord($row);
        }

        return $models;
    }

    /**
     * Execute query and return one record
     */
    public function one(): ?ActiveRecord
    {
        $this->limit = 1;
        $results = $this->all();

        return $results[0] ?? null;
    }

    /**
     * Count records
     */
    public function count(): int
    {
        /** @var ActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        $db = $modelClass::getDb();

        $model = new $modelClass();
        $tableName = $model->getTableName();
        $tableName = str_replace(['{{%', '}}'], '', $tableName);

        $sql = "SELECT COUNT(*) FROM `{$tableName}`";

        if (!empty($this->where)) {
            $whereClause = $this->buildWhereClause();
            $sql .= " WHERE {$whereClause}";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($this->buildParams());

        return (int) $stmt->fetchColumn();
    }

    /**
     * Build SQL query
     */
    protected function buildSql(): string
    {
        /** @var ActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        $model = new $modelClass();
        $tableName = $model->getTableName();

        // Remove Yii2 table name placeholders
        $tableName = str_replace(['{{%', '}}'], '', $tableName);

        // SELECT
        $selectClause = empty($this->select) ? '*' : implode(', ', $this->select);
        $sql = "SELECT {$selectClause} FROM `{$tableName}`";

        // WHERE
        if (!empty($this->where)) {
            $whereClause = $this->buildWhereClause();
            $sql .= " WHERE {$whereClause}";
        }

        // GROUP BY
        if (!empty($this->groupBy)) {
            $groupByClause = implode(', ', array_map(fn($col) => "`{$col}`", $this->groupBy));
            $sql .= " GROUP BY {$groupByClause}";
        }

        // ORDER BY
        if (!empty($this->orderBy)) {
            $orderParts = [];
            foreach ($this->orderBy as $column => $direction) {
                if (is_int($column)) {
                    // String format like "id DESC"
                    $orderParts[] = $direction;
                } else {
                    $dir = ($direction === SORT_DESC || $direction === 'DESC') ? 'DESC' : 'ASC';
                    $orderParts[] = "`{$column}` {$dir}";
                }
            }
            $sql .= " ORDER BY " . implode(', ', $orderParts);
        }

        // LIMIT
        if ($this->limit !== null) {
            $sql .= " LIMIT " . (int)$this->limit;
        }

        // OFFSET
        if ($this->offset !== null) {
            $sql .= " OFFSET " . (int)$this->offset;
        }

        return $sql;
    }

    /**
     * Build WHERE clause
     */
    protected function buildWhereClause(): string
    {
        if (empty($this->where)) {
            return '';
        }

        $conditions = [];

        foreach ($this->where as $column => $value) {
            if ($column === '_raw') {
                // Raw SQL condition
                $conditions[] = $value;
            } elseif (is_int($column)) {
                // Numeric key with raw condition
                if (is_array($value) && isset($value['_raw'])) {
                    $conditions[] = $value['_raw'];
                }
            } else {
                // Column = value condition
                $conditions[] = "`{$column}` = :{$column}";
            }
        }

        return implode(' AND ', $conditions);
    }

    /**
     * Build parameter bindings
     */
    protected function buildParams(): array
    {
        $params = [];

        foreach ($this->where as $column => $value) {
            if ($column !== '_raw' && !is_int($column)) {
                $params[":{$column}"] = $value;
            }
        }

        return $params;
    }
}

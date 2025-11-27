<?php

declare(strict_types=1);

namespace Yiisoft\ActiveRecord;

/**
 * Compatibility stub for ActiveQueryInterface
 * This is a temporary compatibility layer for alex-no/field-lingo package
 */
interface ActiveQueryInterface
{
    public function where(array|string|null $condition): static;
    public function andWhere(array|string $condition): static;
    public function orWhere(array|string $condition): static;
    public function filterWhere(array $condition): static;
    public function andFilterWhere(array $condition): static;
    public function orFilterWhere(array $condition): static;
    public function orderBy(array|string $columns): static;
    public function addOrderBy(array|string $columns): static;
    public function groupBy(array|string $columns): static;
    public function addGroupBy(array|string $columns): static;
    public function select(array|string $columns): static;
    public function all(): array;
    public function one(): ?ActiveRecord;
}

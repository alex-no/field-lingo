<?php
declare(strict_types=1);

namespace FieldLingo\Adapters\Yii3;
/**
 * @file LingoActiveQuery.php - ActiveQuery supporting localized attribute names.
 * Class LingoActiveQuery
 *
 * ActiveQuery extension that localizes columns/conditions/order/group keys before passing them to parent.
 * This file is part of FieldLingo package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @license MIT
 * @package FieldLingo\Adapters\Yii3
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 * @since 1.0.0
 */
use Yiisoft\ActiveRecord\ActiveQuery;

class LingoActiveQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     */
    use LocalizedAttributeTrait;

    /**
     * {@inheritdoc}
     */
    public function select(array|string $columns): static
    {
        return parent::select($this->localizeColumns($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function where(array|string|null $condition): static
    {
        return parent::where($this->localizeCondition($condition));
    }

    /**
     * {@inheritdoc}
     */
    public function andWhere(array|string $condition): static
    {
        return parent::andWhere($this->localizeCondition($condition));
    }

    /**
     * {@inheritdoc}
     */
    public function orWhere(array|string $condition): static
    {
        return parent::orWhere($this->localizeCondition($condition));
    }

    /**
     * {@inheritdoc}
     */
    public function filterWhere(array $condition): static
    {
        return parent::filterWhere($this->localizeCondition($condition));
    }

    /**
     * {@inheritdoc}
     */
    public function andFilterWhere(array $condition): static
    {
        return parent::andFilterWhere($this->localizeCondition($condition));
    }

    /**
     * {@inheritdoc}
     */
    public function orFilterWhere(array $condition): static
    {
        return parent::orFilterWhere($this->localizeCondition($condition));
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy(array|string $columns): static
    {
        return parent::orderBy($this->localizeOrderColumns($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function addOrderBy(array|string $columns): static
    {
        return parent::addOrderBy($this->localizeOrderColumns($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(array|string $columns): static
    {
        return parent::groupBy($this->localizeColumns($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function addGroupBy(array|string $columns): static
    {
        return parent::addGroupBy($this->localizeColumns($columns));
    }

    /**
     * Convert columns (string or array) to localized equivalents.
     *
     * @param string|array $columns
     * @return array
     */
    protected function localizeColumns(string|array $columns): array
    {
        // Keep strings like 'id, @@name' -> array
        $columns = $this->stringToArray($columns);

        $result = [];
        foreach ($columns as $key => $col) {
            if (is_string($key)) {
                // associative: alias => column
                $result[$key] = $this->getLocalizedAttributeName((string)$col);
            } else {
                $result[] = $this->getLocalizedAttributeName((string)$col);
            }
        }

        return $result;
    }

    /**
     * Convert orderBy-style columns (can include ASC/DESC).
     *
     * @param string|array $columns
     * @return array
     */
    protected function localizeOrderColumns(string|array $columns): array
    {
        $columns = $this->stringToArray($columns);

        $result = [];
        foreach ($columns as $key => $col) {
            if (is_string($key)) {
                $result[$this->getLocalizedAttributeName((string)$key)] = $col;
            } else {
                // $col can be like '@@name DESC' or '@@name'
                $parts = preg_split('/\s+/', trim((string)$col));
                $colName = $parts[0];
                $direction = isset($parts[1]) ? strtoupper($parts[1]) : 'ASC';
                $localized = $this->getLocalizedAttributeName($colName);
                $result[$localized] = ($direction === 'DESC') ? SORT_DESC : SORT_ASC;
            }
        }

        return $result;
    }

    /**
     * Convert condition arrays (['@@name' => 'Cat', 'status' => 1]) recursively.
     *
     * @param mixed $condition
     * @return mixed
     */
    protected function localizeCondition(mixed $condition): mixed
    {
        if (is_array($condition)) {
            $localized = [];
            foreach ($condition as $key => $value) {
                if (is_string($key)) {
                    $localizedKey = $this->getLocalizedAttributeName($key);
                    // If value is an array with nested conditions — recurse
                    if (is_array($value)) {
                        $localized[$localizedKey] = $this->localizeCondition($value);
                    } else {
                        $localized[$localizedKey] = $value;
                    }
                } else {
                    // numeric keys -> nested arrays or expressions
                    $localized[] = is_array($value) ? $this->localizeCondition($value) : $value;
                }
            }
            return $localized;
        }

        // if string or expression - return as is
        return $condition;
    }

    /**
     * Normalize string or array into array of columns.
     *
     * @param string|array $columns
     * @return array
     */
    protected function stringToArray(string|array $columns): array
    {
        return match(true) {
            is_string($columns) => preg_split('/\s*,\s*/', trim($columns)) ?: [],
            $columns === null => [],
            is_array($columns) => $columns,
            default => [$columns],
        };
    }
}

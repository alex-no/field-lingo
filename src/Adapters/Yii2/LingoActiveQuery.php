<?php
declare(strict_types=1);

namespace AlexNo\Fieldlingo\Adapters\Yii2;
/**
 * @file LingoActiveQuery.php - ActiveQuery supporting localized attribute names.
 * Class LingoActiveQuery
 *
 * ActiveQuery extension that localizes columns/conditions/order/group keys before passing them to parent.
 * This file is part of LanguageDetector package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @license MIT
 * @package LanguageDetector\Adapters\Yii2
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 * @since 1.0.0
 */
use yii\db\ActiveQuery;

class LingoActiveQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     */
    use LocalizedAttributeTrait;

    /**
     * {@inheritdoc}
     */
    public function select($columns, $option = null): array|string
    {
        return parent::select($this->localizeColumns($columns), $option);
    }

    /**
     * {@inheritdoc}
     */
    public function where($condition, $params = []): array|string
    {
        return parent::where($this->localizeCondition($condition), $params);
    }

    /**
     * {@inheritdoc}
     */
    public function andWhere($condition, $params = []): array|string
    {
        return parent::andWhere($this->localizeCondition($condition), $params);
    }

    /**
     * {@inheritdoc}
     */
    public function orWhere($condition, $params = []): array|string
    {
        return parent::orWhere($this->localizeCondition($condition), $params);
    }

    /**
     * {@inheritdoc}
     */
    public function filterWhere($condition): array|string
    {
        return parent::filterWhere($this->localizeCondition($condition));
    }

    /**
     * {@inheritdoc}
     */
    public function andFilterWhere($condition): array|string
    {
        return parent::andFilterWhere($this->localizeCondition($condition));
    }

    /**
     * {@inheritdoc}
     */
    public function orFilterWhere($condition): array|string
    {
        return parent::orFilterWhere($this->localizeCondition($condition));
    }

    /**
     * {@inheritdoc}
     */
    public function orderBy($columns): array|string
    {
        return parent::orderBy($this->localizeOrderColumns($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function addOrderBy($columns): array|string
    {
        return parent::addOrderBy($this->localizeOrderColumns($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy($columns): array|string
    {
        return parent::groupBy($this->localizeColumns($columns));
    }

    /**
     * {@inheritdoc}
     */
    public function addGroupBy($columns): array|string
    {
        return parent::addGroupBy($this->localizeColumns($columns));
    }

    /**
     * Convert columns (string or array) to localized equivalents.
     *
     * @param string|array $columns
     * @return array|string
     */
    protected function localizeColumns($columns): array|string
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
     * @return array|string
     */
    protected function localizeOrderColumns($columns): array|string
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
    protected function localizeCondition($condition): mixed
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
    protected function stringToArray($columns): array
    {
        if (is_string($columns)) {
            // split by commas for simple lists
            $parts = preg_split('/\s*,\s*/', trim($columns));
            return $parts ?: [];
        }

        if ($columns === null) {
            return [];
        }

        if (!is_array($columns)) {
            return [$columns];
        }

        return $columns;
    }
}

<?php
declare(strict_types=1);

namespace FieldLingo\Adapters\Laravel;

use Illuminate\Database\Eloquent\Builder;

/**
 * Class LingoBuilder
 *
 * Custom Eloquent Query Builder that supports localized attribute names (e.g. @@name).
 * Extends Laravel's Eloquent Builder to intercept query methods and translate
 * structured field names into language-specific column names.
 *
 * This file is part of FieldLingo package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package FieldLingo\Adapters\Laravel
 * @license MIT
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 * @since 1.0.0
 */
class LingoBuilder extends Builder
{
    /**
     * Set the columns to be selected.
     *
     * @param  array|mixed  $columns
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $columns = $this->localizeColumns($columns);

        return parent::select($columns);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  \Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_array($column)) {
            $column = $this->localizeCondition($column);
        } elseif (is_string($column)) {
            $column = $this->localizeAttributeName($column);
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param  \Closure|string|array  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        if (is_array($column)) {
            $column = $this->localizeCondition($column);
        } elseif (is_string($column)) {
            $column = $this->localizeAttributeName($column);
        }

        return parent::orWhere($column, $operator, $value);
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|\Illuminate\Database\Query\Expression|string  $column
     * @param  string  $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        if (is_string($column)) {
            $column = $this->localizeAttributeName($column);
        }

        return parent::orderBy($column, $direction);
    }

    /**
     * Add a "group by" clause to the query.
     *
     * @param  array|string  ...$groups
     * @return $this
     */
    public function groupBy(...$groups)
    {
        $groups = $this->localizeColumns($groups);

        return parent::groupBy(...$groups);
    }

    /**
     * Add a "having" clause to the query.
     *
     * @param  string  $column
     * @param  string|null  $operator
     * @param  string|null  $value
     * @param  string  $boolean
     * @return $this
     */
    public function having($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_string($column)) {
            $column = $this->localizeAttributeName($column);
        }

        return parent::having($column, $operator, $value, $boolean);
    }

    /**
     * Convert array of columns to localized equivalents.
     *
     * @param array $columns
     * @return array
     */
    protected function localizeColumns(array $columns): array
    {
        $result = [];

        foreach ($columns as $key => $value) {
            if (is_string($key)) {
                // Associative array: alias => column
                $result[$key] = $this->localizeAttributeName($value);
            } elseif (is_string($value)) {
                $result[] = $this->localizeAttributeName($value);
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Convert condition arrays recursively.
     *
     * @param array $condition
     * @return array
     */
    protected function localizeCondition(array $condition): array
    {
        $localized = [];

        foreach ($condition as $key => $value) {
            if (is_string($key)) {
                $localizedKey = $this->localizeAttributeName($key);

                if (is_array($value)) {
                    $localized[$localizedKey] = $this->localizeCondition($value);
                } else {
                    $localized[$localizedKey] = $value;
                }
            } else {
                // Numeric key - nested conditions
                if (is_array($value)) {
                    $localized[] = $this->localizeCondition($value);
                } else {
                    $localized[] = $value;
                }
            }
        }

        return $localized;
    }

    /**
     * Convert a single attribute name to localized version.
     *
     * @param string $name
     * @return string
     */
    protected function localizeAttributeName(string $name): string
    {
        // Get the model instance
        $model = $this->getModel();

        if (method_exists($model, 'getLocalizedAttributeName')) {
            return $model->getLocalizedAttributeName($name);
        }

        return $name;
    }
}

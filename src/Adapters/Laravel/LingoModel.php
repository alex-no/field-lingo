<?php
declare(strict_types=1);

namespace FieldLingo\Adapters\Laravel;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LingoModel
 *
 * Eloquent Model base class that supports localized attribute names (e.g. @@name).
 * Extend your Eloquent models from this class to benefit from automatic localized
 * attribute resolution.
 *
 * Example:
 *   $model->getAttribute('@@name') // will resolve to name_en/name_uk depending on app()->getLocale()
 *   $model->{'@@name'}              // same as above
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
abstract class LingoModel extends Model
{
    use LocalizedAttributeTrait;

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new LingoBuilder($query);
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $localized = $this->getLocalizedAttributeName($key);
        return parent::getAttribute($localized);
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        $localized = $this->getLocalizedAttributeName($key);
        return parent::setAttribute($localized, $value);
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = parent::toArray();

        // If you want to convert attribute keys back to @@notation in output,
        // you can implement that logic here. For now, we return as-is.

        return $attributes;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }
}

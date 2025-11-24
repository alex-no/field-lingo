<?php
declare(strict_types=1);

namespace FieldLingo\Adapters\Symfony;

/**
 * Class LingoEntity
 *
 * Base entity class that supports localized attribute names (e.g. @@name).
 * Extend your Doctrine entities from this class to benefit from automatic
 * localized attribute resolution.
 *
 * Example:
 *   $entity->{'@@name'} // will resolve to name_en/name_uk depending on current locale
 *
 * This file is part of FieldLingo package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package FieldLingo\Adapters\Symfony
 * @license MIT
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 * @since 1.0.0
 */
abstract class LingoEntity
{
    use LocalizedAttributeTrait;

    /**
     * Get localized value by attribute name
     *
     * @param string $attribute
     * @return mixed
     */
    public function getLocalized(string $attribute): mixed
    {
        return $this->__get($attribute);
    }

    /**
     * Set localized value by attribute name
     *
     * @param string $attribute
     * @param mixed $value
     * @return static
     */
    public function setLocalized(string $attribute, mixed $value): static
    {
        $this->__set($attribute, $value);
        return $this;
    }
}

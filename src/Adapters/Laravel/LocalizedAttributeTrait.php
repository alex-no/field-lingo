<?php
declare(strict_types=1);

namespace FieldLingo\Adapters\Laravel;

/**
 * Trait LocalizedAttributeTrait
 *
 * Trait for localized attribute name handling for Laravel Eloquent models.
 *
 * Configurable properties (can be overridden via config or per-model):
 *  - public string|array $localizedPrefixes = '@@';
 *  - public bool $isStrict = true;
 *  - public string $defaultLanguage = 'en';
 *
 * The trait will attempt to read global defaults from config('field-lingo')
 * keyed by either the concrete model class or by 'default'.
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
trait LocalizedAttributeTrait
{
    /**
     * Prefix or prefixes used to mark structured localized names.
     * Example: '@@' or ['@@', '##'].
     * @var string|string[]
     */
    public $localizedPrefixes = '@@';

    /**
     * If true - throw when localized column not found.
     * If false - try fallback to defaultLanguage.
     * @var bool
     */
    public bool $isStrict = true;

    /**
     * Default fallback language (two-letter code).
     * @var string
     */
    public string $defaultLanguage = 'en';

    /**
     * Initialize localized settings from global configuration.
     * Called during model boot.
     *
     * @return void
     */
    protected function initializeLocalizedAttributeTrait(): void
    {
        $config = config('field-lingo', []);

        // Prefer per-model override when available
        $modelClass = static::class;
        $settings = $config[$modelClass] ?? ($config['default'] ?? []);

        if (is_array($settings)) {
            foreach ($settings as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * Normalize prefixes to array.
     *
     * @return string[]
     */
    protected function getPrefixesArray(): array
    {
        return match(true) {
            is_array($this->localizedPrefixes) => array_values($this->localizedPrefixes),
            $this->localizedPrefixes === null || $this->localizedPrefixes === '' => [],
            default => [(string)$this->localizedPrefixes],
        };
    }

    /**
     * Convert structured name like '@@name' to actual localized attribute name.
     *
     * Behavior:
     *  - if $name does not start with any configured prefix — returns $name unchanged.
     *  - determines current language from app()->getLocale() (first part before _ or -).
     *  - forms candidate: {base}_{lang}
     *  - if method hasColumn exists (Laravel's Schema):
     *      - if candidate exists => return it
     *      - else if isStrict => throw MissingLocalizedAttributeException
     *      - else try fallback {base}_{defaultLanguage} and if exists return it, otherwise return candidate
     *
     * @param string $name
     * @return string
     * @throws MissingLocalizedAttributeException
     */
    protected function getLocalizedAttributeName(string $name): string
    {
        foreach ($this->getPrefixesArray() as $prefix) {
            if ($prefix !== '' && str_starts_with($name, $prefix)) {
                $base = substr($name, strlen($prefix));

                $lang = app()->getLocale();
                $lang = (is_string($lang) && $lang !== '')
                    ? strtolower(preg_split('/[_-]/', $lang)[0])
                    : $this->defaultLanguage;

                $candidate = "{$base}_{$lang}";

                // Check if column exists in model's table
                if ($this->hasColumn($candidate)) {
                    return $candidate;
                }

                // attribute not found
                if ($this->isStrict) {
                    throw new MissingLocalizedAttributeException($candidate);
                }

                // Non-strict: try fallback language only if different
                $fallback = "{$base}_{$this->defaultLanguage}";
                if ($this->defaultLanguage !== $lang && $this->hasColumn($fallback)) {
                    return $fallback;
                }

                // Return the original candidate (let DB handle if it doesn't exist)
                return $candidate;
            }
        }

        return $name;
    }

    /**
     * Check if a column exists in the model's table.
     *
     * @param string $column
     * @return bool
     */
    protected function hasColumn(string $column): bool
    {
        // Check in model's attributes first (for existing instances)
        if (array_key_exists($column, $this->attributes ?? [])) {
            return true;
        }

        // More robust: check if column is in model's known attributes
        // This works after model is loaded from DB
        try {
            return method_exists($this, 'getConnection')
                && $this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), $column);
        } catch (\Throwable) {
            // If we can't check, assume it doesn't exist (for query building)
            return false;
        }
    }

    /**
     * Convert array of field names into localized equivalents.
     *
     * @param array $fields
     * @return array
     */
    protected function convertLocalizedFields(array $fields): array
    {
        return array_map(
            fn($f) => $this->getLocalizedAttributeName((string)$f),
            $fields
        );
    }
}

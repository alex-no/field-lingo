<?php
declare(strict_types=1);

namespace AlexNo\FieldLingo\Adapters\Yii2;
/**
 * Trait LocalizedAttributeTrait
 * @file LocalizedAttributeTrait.php - Trait for localized attribute name handling.
 *
 * Trait for localized attribute name handling for Yii2 ActiveRecord/ActiveQuery adapters.
 *
 * Configurable properties (can be overridden via component config or per-model):
 *  - public string|array $localizedPrefixes = '@@';
 *  - public bool $isStrict = true;
 *  - public string $defaultLanguage = 'en';
 *
 * The trait will attempt to read global defaults from Yii::$app->params['LingoActive']
 * keyed by either the concrete model class (if $this->modelClass exists) or by the
 * adapter class name using self::class.
 *
 * This file is part of LanguageDetector package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @license MIT
 * @package LanguageDetector\Adapters\Yii2
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 * @since 1.0.0
 */
use Yii;

/**
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
     *
     * Called by classes that use this trait if they call parent::init() or call this init explicitly.
     *
     * @return void
     */
    public function init(): void
    {
        // If the using class has parent::init, call it (same pattern as in original).
        if (is_callable('parent::init')) {
            parent::init();
        }

        $globalConfig = Yii::$app->params['LingoActive'] ?? [];
        // Prefer per-model override when available (common pattern for Query/Record adapters)
        $class = isset($this->modelClass) ? $this->modelClass : static::class;
        $baseClass = self::class;

        $config = $globalConfig[$class] ?? ($globalConfig[$baseClass] ?? []);

        if (is_array($config)) {
            foreach ($config as $key => $value) {
                // Only set known properties
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
        if (is_array($this->localizedPrefixes)) {
            return array_values($this->localizedPrefixes);
        }

        // allow null or empty string to behave as empty array
        if ($this->localizedPrefixes === null || $this->localizedPrefixes === '') {
            return [];
        }

        return [(string)$this->localizedPrefixes];
    }

    /**
     * Convert structured name like '@@name' to actual localized attribute name.
     *
     * Behavior:
     *  - if $name does not start with any configured prefix — returns $name unchanged.
     *  - determines current language from Yii::$app->language (first part before _ or -).
     *  - forms candidate: {base}_{lang}
     *  - if method hasAttribute exists:
     *      - if candidate exists => return it
     *      - else if isStrict => throw MissingLocalizedAttributeException
     *      - else try fallback {base}_{defaultLanguage} and if exists return it, otherwise return candidate
     *  - if hasAttribute doesn't exist (cannot check) — return candidate (or fallback candidate when not prefixed)
     *  - if isStrict and cannot check existence — returns candidate anyway (caller may validate)
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

                $lang = Yii::$app->language ?? null;
                if (is_string($lang) && $lang !== '') {
                    $parts = preg_split('/[_-]/', $lang);
                    $lang = strtolower($parts[0]);
                } else {
                    $lang = $this->defaultLanguage;
                }

                $candidate = "{$base}_{$lang}";

                // If we can check attributes, prefer that
                if (method_exists($this, 'hasAttribute')) {
                    // attribute exists: return it
                    if ($this->hasAttribute($candidate)) {
                        return $candidate;
                    }

                    // attribute not found
                    if ($this->isStrict) {
                        throw new MissingLocalizedAttributeException($candidate);
                    }

                    // Non-strict: try fallback language only if different
                    $fallback = "{$base}_{$this->defaultLanguage}";
                    if ($this->defaultLanguage !== $lang && $this->hasAttribute($fallback)) {
                        return $fallback;
                    }
                }

                // If attribute existence cannot be checked or fallback not found, return the original candidate
                return $candidate;
            }
        }

        return $name;
    }

    /**
     * Convert array of field names used in toArray/select into localized equivalents.
     *
     * @param array $fields
     * @return array
     */
    protected function convertLocalizedFields(array $fields): array
    {
        return array_map(function ($f) {
            return $this->getLocalizedAttributeName((string)$f);
        }, $fields);
    }
}

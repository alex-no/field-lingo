<?php
declare(strict_types=1);

namespace AlexNo\FieldLingo\Adapters\Yii2;
/**
 * Trait LocalizedAttributeTrait
 * @file LocalizedAttributeTrait.php - Trait for localized attribute name handling.
 *
 * Provides localized attribute name handling for Yii2 ActiveRecord/ActiveQuery adapters.
 *
 * Configurable properties (can be overridden via component config or per-model):
 *  - public string|array $localizedPrefixes = '@@';
 *  - public bool $isStrict = true;
 *  - public string $defaultLanguage = 'en';
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
     * Normalize prefixes to array.
     *
     * @return string[]
     */
    protected function getPrefixesArray(): array
    {
        return is_array($this->localizedPrefixes) ?
            array_values($this->localizedPrefixes) :
            [$this->localizedPrefixes];
    }

    /**
     * Convert structured name like '@@name' to actual localized attribute name.
     *
     * @param string $name
     * @return string
     * @throws MissingLocalizedAttributeException
     */
    protected function getLocalizedAttributeName(string $name): string
    {
        foreach ($this->getPrefixesArray() as $prefix) {
            if (str_starts_with($name, $prefix)) {
                $base = substr($name, strlen($prefix));
                $lang = Yii::$app->language ?? null;
                if (is_string($lang) && $lang !== '') {
                    $parts = preg_split('/[_-]/', $lang);
                    $lang = strtolower($parts[0]);
                } else {
                    $lang = $this->defaultLanguage;
                }

                $localized = "{$base}_{$lang}";

                // If model has attribute checking, prefer that; otherwise return candidate and let caller handle
                if (method_exists($this, 'hasAttribute') && $this->hasAttribute($localized)) {
                    return $localized;
                }

                if ($this->isStrict) {
                    // Strict mode: if attribute doesn't exist — throw
                    throw new MissingLocalizedAttributeException($localized);
                }

                // Non-strict: try default language fallback if different
                $fallback = "{$base}_{$this->defaultLanguage}";
                if (method_exists($this, 'hasAttribute') && $this->hasAttribute($fallback)) {
                    return $fallback;
                }

                // If attribute existence cannot be checked or fallback not found, return the original candidate
                return $localized;
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

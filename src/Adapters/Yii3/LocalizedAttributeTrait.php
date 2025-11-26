<?php
declare(strict_types=1);

namespace FieldLingo\Adapters\Yii3;
/**
 * Trait LocalizedAttributeTrait
 * @file LocalizedAttributeTrait.php - Trait for localized attribute name handling.
 *
 * Trait for localized attribute name handling for Yii3 ActiveRecord/ActiveQuery adapters.
 *
 * Configurable properties (can be overridden via component config or per-model):
 *  - public string|array $localizedPrefixes = '@@';
 *  - public bool $isStrict = true;
 *  - public string $defaultLanguage = 'en';
 *
 * The trait will attempt to read global defaults from the application configuration
 * keyed by either the concrete model class (if $this->modelClass exists) or by the
 * adapter class name using self::class.
 *
 * This file is part of FieldLingo package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @license MIT
 * @package FieldLingo\Adapters\Yii3
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 * @since 1.0.0
 */
use Yiisoft\Translator\TranslatorInterface;

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
     * Current locale/language for Yii3 context
     * @var string|null
     */
    private ?string $currentLocale = null;

    /**
     * Translator interface for Yii3 (optional)
     * @var TranslatorInterface|null
     */
    private ?TranslatorInterface $translator = null;

    /**
     * Set the current locale for localized attribute resolution.
     *
     * @param string $locale
     * @return static
     */
    public function setLocale(string $locale): static
    {
        $this->currentLocale = $locale;
        return $this;
    }

    /**
     * Get the current locale.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->currentLocale ?? $this->defaultLanguage;
    }

    /**
     * Set translator instance (optional, for Yii3 DI integration).
     *
     * @param TranslatorInterface|null $translator
     * @return static
     */
    public function setTranslator(?TranslatorInterface $translator): static
    {
        $this->translator = $translator;
        if ($translator !== null) {
            $this->currentLocale = $translator->getLocale();
        }
        return $this;
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
     *  - determines current language from configured locale or translator (first part before _ or -).
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

                $lang = $this->getLocale();
                $lang = strtolower(preg_split('/[_-]/', $lang)[0]);

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
        return array_map(
            fn($f) => $this->getLocalizedAttributeName((string)$f),
            $fields
        );
    }
}

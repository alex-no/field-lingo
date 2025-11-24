<?php
declare(strict_types=1);

namespace FieldLingo\Adapters\Symfony;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Trait LocalizedAttributeTrait
 *
 * Trait for localized attribute name handling for Symfony Doctrine entities.
 *
 * Configurable properties (can be overridden via config or per-entity):
 *  - public string|array $localizedPrefixes = '@@';
 *  - public bool $isStrict = true;
 *  - public string $defaultLanguage = 'en';
 *
 * The trait will attempt to read global defaults from container parameters
 * under 'field_lingo' key, keyed by either the concrete entity class or by 'default'.
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
     * Current locale from request context
     * @var ?string
     */
    private ?string $currentLocale = null;

    /**
     * Initialize localized settings from configuration.
     * Should be called in entity constructor if needed.
     *
     * @param ?ParameterBagInterface $parameterBag
     * @return void
     */
    protected function initializeLocalizedAttribute(?ParameterBagInterface $parameterBag = null): void
    {
        if ($parameterBag === null) {
            return;
        }

        $config = $parameterBag->get('field_lingo') ?? [];

        // Prefer per-entity override when available
        $entityClass = static::class;
        $settings = $config[$entityClass] ?? ($config['default'] ?? []);

        if (is_array($settings)) {
            foreach ($settings as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * Set current locale for localization
     *
     * @param string $locale
     * @return void
     */
    public function setCurrentLocale(string $locale): void
    {
        $this->currentLocale = $locale;
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
     *  - determines current language from currentLocale or defaultLanguage
     *  - forms candidate: {base}_{lang}
     *  - if reflection can check property exists:
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

                $lang = $this->currentLocale;
                $lang = (is_string($lang) && $lang !== '')
                    ? strtolower(preg_split('/[_-]/', $lang)[0])
                    : $this->defaultLanguage;

                $candidate = "{$base}_{$lang}";

                // Check if property exists using reflection
                if ($this->hasProperty($candidate)) {
                    return $candidate;
                }

                // Property not found
                if ($this->isStrict) {
                    throw new MissingLocalizedAttributeException($candidate);
                }

                // Non-strict: try fallback language only if different
                $fallback = "{$base}_{$this->defaultLanguage}";
                if ($this->defaultLanguage !== $lang && $this->hasProperty($fallback)) {
                    return $fallback;
                }

                // Return the original candidate (let Doctrine handle if it doesn't exist)
                return $candidate;
            }
        }

        return $name;
    }

    /**
     * Check if a property exists in the entity.
     *
     * @param string $property
     * @return bool
     */
    protected function hasProperty(string $property): bool
    {
        try {
            $reflection = new \ReflectionClass($this);
            return $reflection->hasProperty($property);
        } catch (\ReflectionException) {
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

    /**
     * Magic getter with localization support
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        $localized = $this->getLocalizedAttributeName($name);

        if (property_exists($this, $localized)) {
            return $this->$localized;
        }

        $getter = 'get' . str_replace([' ', '_'], '', ucwords($localized, ' _'));
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        throw new \LogicException("Property '{$name}' does not exist on entity " . static::class);
    }

    /**
     * Magic setter with localization support
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $localized = $this->getLocalizedAttributeName($name);

        $setter = 'set' . str_replace([' ', '_'], '', ucwords($localized, ' _'));
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return;
        }

        if (property_exists($this, $localized)) {
            $this->$localized = $value;
            return;
        }

        throw new \LogicException("Property '{$name}' does not exist on entity " . static::class);
    }
}

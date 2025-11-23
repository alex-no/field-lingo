<?php
declare(strict_types=1);

namespace FieldLingo\Core;
/**
 * Class Localizer
 * Framework-agnostic service that converts structured localized attribute names
 * (for example '@@name') into concrete attribute/column names (for example 'name_en').
 * This implementation expects a ConfigInterface implementation (simple DTO or array wrapper).
 *
 * @package AlexNo\FieldLingo\Core
 * @license MIT
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 * @since 1.0.0
 */
use FieldLingo\Core\Contracts\ConfigInterface;
use FieldLingo\Core\Contracts\LocalizerInterface;
use Yii;

/**
 * Class Localizer
 *
 * Framework-agnostic service that converts structured localized attribute names
 * (for example '@@name') into concrete attribute/column names (for example 'name_en').
 *
 * This implementation expects a ConfigInterface implementation (simple DTO or array wrapper).
 */
class Localizer implements LocalizerInterface
{
    /**
     * Localizer constructor.
     * @param ConfigInterface $config
     */
    public function __construct(
        private ConfigInterface $config
    ) {}

    /**
     * {@inheritdoc}
     */
    public function convert(string $name): string
    {
        // If it does not start with any prefix — return as is.
        foreach ($this->config->getPrefixes() as $prefix) {
            if (str_starts_with($name, $prefix)) {
                $base = substr($name, strlen($prefix));
                $lang = $this->getCurrentLanguage();
                $candidate = "{$base}_{$lang}";

                // If we are running inside Yii and model/attribute existence check is required,
                // the adapter (e.g. Yii2 adapter) is responsible for checking. Here we only
                // handle fallback behavior when strict=false: return defaultLanguage candidate.
                if ($this->config->isStrict()) {
                    // In strict mode we return the candidate — adapter will validate existence and may throw.
                    return $candidate;
                }

                // Non-strict: prefer current language but fallback to default.
                return $candidate; // adapter may try defaultLanguage if not found
            }
        }

        return $name;
    }

    /**
     * {@inheritdoc}
     *
     * The method will recursively walk arrays and strings, converting values that look like structured names.
     *
     * @param mixed $input
     * @return mixed
     */
    public function convertMixed($input): mixed
    {
        if (is_string($input)) {
            return $this->convert($input);
        }

        if (is_array($input)) {
            $result = [];
            foreach ($input as $k => $v) {
                // If key is string and looks like a structured name, convert it too
                $newKey = is_string($k) ? $this->convert($k) : $k;
                $result[$newKey] = $this->convertMixed($v);
            }
            return $result;
        }

        // For other types (objects, scalars) — return as is.
        return $input;
    }

    /**
     * Get current language (framework-specific resolution if possible).
     *
     * @return string
     */
    protected function getCurrentLanguage(): string
    {
        // Prefer Yii::$app->language when Yii is available; otherwise fallback to config default.
        try {
            if (defined('YII_ENV') || class_exists('\Yii')) {
                $lang = Yii::$app->language ?? null;
                if (is_string($lang) && $lang !== '') {
                    // normalize to two-letter code if possible (e.g. 'en-US' → 'en')
                    $parts = preg_split('/[_-]/', $lang);
                    return strtolower($parts[0]);
                }
            }
        } catch (\Throwable $e) {
            // ignore — will fallback to default language
        }

        return $this->config->getDefaultLanguage();
    }
}

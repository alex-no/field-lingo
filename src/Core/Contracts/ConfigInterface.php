<?php
declare(strict_types=1);

namespace AlexNo\Fieldlingo\Core\Contracts;

/**
 * Interface ConfigInterface
 *
 * Configuration contract used by Localizer implementations.
 *
 * Provides access to runtime settings that control how structured localized
 * names (for example "@@name") are converted into concrete column/attribute names
 * (for example "name_en").
 *
 * @package AlexNo\Fieldlingo\Core\Contracts
 * @license MIT
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 * @since 1.0.0
 */
interface ConfigInterface
{
    /**
     * Get configured localized prefixes.
     *
     * @return string[] Array of prefixes (e.g. ['@@']).
     */
    public function getPrefixes(): array;

    /**
     * Get default/fallback language code.
     *
     * @return string
     */
    public function getDefaultLanguage(): string;

    /**
     * Whether localizer should be strict (throw on missing localized attribute).
     *
     * @return bool
     */
    public function isStrict(): bool;
}

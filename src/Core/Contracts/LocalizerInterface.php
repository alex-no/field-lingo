<?php
declare(strict_types=1);
/**
 * @file LocalizerInterface.php - Contract for Localizer service.
 * This file is part of LanguageDetector package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @license MIT
 * @package LanguageDetector\Core
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 */
namespace AlexNo\FieldLingo\Core\Contracts;

/**
 * Interface LocalizerInterface
 *
 * Contract for a service that converts "structured" localized names (e.g. @@name)
 * into concrete column/attribute names (e.g. name_en) based on configuration and current language.
 */
interface LocalizerInterface
{
    /**
     * Convert a single structured name to concrete name.
     *
     * Example: convert('@@title') => 'title_en' (depending on current language)
     *
     * @param string $name Structured attribute name or plain attribute name.
     * @return string Concrete attribute/column name.
     */
    public function convert(string $name): string;

    /**
     * Convert an array (or mixed structure) of names into localized equivalents.
     *
     * This method should accept arrays used in query builders (strings, associative arrays, nested arrays).
     *
     * @param mixed $input
     * @return mixed Converted structure with localized names.
     */
    public function convertMixed($input);
}

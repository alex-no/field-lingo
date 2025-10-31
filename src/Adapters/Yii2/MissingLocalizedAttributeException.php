<?php
declare(strict_types=1);

namespace AlexNo\Fieldlingo\Adapters\Yii2;
/**
 * @file MissingLocalizedAttributeException.php - Exception for missing localized attribute.
 * This file is part of LanguageDetector package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @license MIT
 * @package LanguageDetector\Adapters\Yii2
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 * @since 1.0.0
 */
use RuntimeException;

/**
 * Class MissingLocalizedAttributeException
 *
 * Thrown when a requested localized attribute (e.g. name_uk) is missing on the model/table
 * and the system is running in strict mode.
 */
class MissingLocalizedAttributeException extends RuntimeException
{
    /**
     * MissingLocalizedAttributeException constructor.
     *
     * @param string $attribute
     */
    public function __construct(string $attribute)
    {
        parent::__construct("The localized attribute '{$attribute}' is missing.");
    }
}

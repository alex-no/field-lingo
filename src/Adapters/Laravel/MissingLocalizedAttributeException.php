<?php
declare(strict_types=1);

namespace FieldLingo\Adapters\Laravel;

use Exception;

/**
 * Exception thrown when a localized attribute is not found in strict mode.
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
class MissingLocalizedAttributeException extends Exception
{
    /**
     * @param string $attribute The missing localized attribute name
     */
    public function __construct(string $attribute)
    {
        parent::__construct("Localized attribute '{$attribute}' not found in strict mode.");
    }
}

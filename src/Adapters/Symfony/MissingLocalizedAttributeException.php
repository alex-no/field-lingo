<?php
declare(strict_types=1);

namespace FieldLingo\Adapters\Symfony;

/**
 * Exception thrown when a localized attribute is not found in strict mode.
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
class MissingLocalizedAttributeException extends \RuntimeException
{
    public function __construct(string $attributeName, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Localized attribute '{$attributeName}' not found in entity.";
        parent::__construct($message, $code, $previous);
    }
}

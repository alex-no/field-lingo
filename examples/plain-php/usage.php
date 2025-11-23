<?php
/**
 * Plain PHP usage example (framework-agnostic core)
 *
 * This example demonstrates how to use the core Localizer class
 * without any framework dependencies.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use FieldLingo\Core\Localizer;
use FieldLingo\Core\Contracts\ConfigInterface;

/**
 * Simple config implementation
 */
class SimpleConfig implements ConfigInterface
{
    public function __construct(
        private array $prefixes = ['@@'],
        private string $defaultLanguage = 'en',
        private bool $strict = false
    ) {}

    public function getPrefixes(): array
    {
        return $this->prefixes;
    }

    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }
}

// Create configuration
$config = new SimpleConfig(
    prefixes: ['@@', '##'],
    defaultLanguage: 'en',
    strict: false
);

// Create localizer instance
$localizer = new Localizer($config);

// Example 1: Convert single field name
$fieldName = '@@title';
$converted = $localizer->convert($fieldName);
echo "Field: $fieldName -> $converted\n";
// Output: Field: @@title -> title_en

// Example 2: Convert array of field names
$fields = ['id', '@@name', '@@description', 'price'];
$convertedFields = $localizer->convertMixed($fields);
print_r($convertedFields);
// Output: Array([0] => id, [1] => name_en, [2] => description_en, [3] => price)

// Example 3: Convert associative array (like query conditions)
$conditions = [
    '@@title' => 'Product Name',
    '@@category' => 'Electronics',
    'price' => ['>', 100]
];
$convertedConditions = $localizer->convertMixed($conditions);
print_r($convertedConditions);
// Output: Array(
//   [title_en] => Product Name,
//   [category_en] => Electronics,
//   [price] => Array([0] => >, [1] => 100)
// )

// Example 4: Using different prefix
$altFieldName = '##status';
$convertedAlt = $localizer->convert($altFieldName);
echo "Field: $altFieldName -> $convertedAlt\n";
// Output: Field: ##status -> status_en

// Example 5: Non-prefixed fields remain unchanged
$regularField = 'created_at';
$convertedRegular = $localizer->convert($regularField);
echo "Field: $regularField -> $convertedRegular\n";
// Output: Field: created_at -> created_at

echo "\nCore localizer works independently from any framework!\n";

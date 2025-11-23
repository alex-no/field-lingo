<?php
/**
 * Sample usage of LingoActiveRecord
 *
 * This example demonstrates how to use Field-lingo with Yii2 ActiveRecord
 * to work with localized database columns.
 */

use FieldLingo\Adapters\Yii2\LingoActiveRecord;

/**
 * Example Product model with localized fields
 *
 * Database table structure:
 * CREATE TABLE product (
 *     id INT PRIMARY KEY AUTO_INCREMENT,
 *     name_en VARCHAR(255),
 *     name_uk VARCHAR(255),
 *     name_ru VARCHAR(255),
 *     description_en TEXT,
 *     description_uk TEXT,
 *     description_ru TEXT,
 *     price DECIMAL(10,2),
 *     created_at TIMESTAMP
 * );
 */
class Product extends LingoActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name_en', 'name_uk', 'name_ru'], 'required'],
            [['description_en', 'description_uk', 'description_ru'], 'string'],
            [['price'], 'number'],
            [['name_en', 'name_uk', 'name_ru'], 'string', 'max' => 255],
        ];
    }
}

// Usage example:
// Assuming Yii::$app->language is set to 'uk' (Ukrainian)

// Create new product
$product = new Product();
$product->setAttribute('@@name', '>CB1C:');  // Sets name_uk
$product->setAttribute('@@description', '>BC6=89 =>CB1C: 4;O @>1>B8');  // Sets description_uk
$product->price = 25000.00;
$product->save();

// Read product
$product = Product::findOne(1);
echo $product->getAttribute('@@name');  // Returns name_uk value
echo $product->getAttribute('@@description');  // Returns description_uk value

// Property-style access via magic methods
echo $product->{'@@name'};  // Same as getAttribute('@@name')

// Export to array with localized fields
$data = $product->toArray(['id', '@@name', '@@description', 'price']);
// Result: ['id' => 1, 'name_uk' => '>CB1C:', 'description_uk' => '...', 'price' => 25000.00]

print_r($data);

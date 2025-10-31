<?php
declare(strict_types=1);
/**
 * @file LingoActiveRecord.php - ActiveRecord supporting localized attribute names.
 * Class LingoActiveRecord
 *
 * ActiveRecord base class that supports localized attribute names (e.g. @@name).
 * Extend your AR models from this class to benefit from automatic localized attribute resolution.
 *
 * Example:
 *   $model->{"@@name"} // will resolve to name_en/name_uk depending on Yii::$app->language
 *
 * This file is part of LanguageDetector package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @license MIT
 * @package LanguageDetector\Adapters\Yii2
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 * @since 1.0.0
 */
namespace AlexNo\Fieldlingo\Adapters\Yii2;

use yii\db\ActiveRecord;

/**
 * Class LingoActiveRecord
 */
abstract class LingoActiveRecord extends ActiveRecord
{
    use LocalizedAttributeTrait;

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name)
    {
        $localized = $this->getLocalizedAttributeName((string)$name);
        return parent::getAttribute($localized);
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        // If getter exists, call it
        $getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', (string)$name)));
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        $localized = $this->getLocalizedAttributeName((string)$name);
        return parent::__get($localized);
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', (string)$name)));
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return;
        }

        $localized = $this->getLocalizedAttributeName((string)$name);
        parent::__set($localized, $value);
    }

    /**
     * Override toArray to convert requested fields to localized ones.
     *
     * @param array $fields
     * @param array $expand
     * @param bool $recursive
     * @return array
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        if (!empty($fields)) {
            $fields = $this->convertLocalizedFields($fields);
        }
        return parent::toArray($fields, $expand, $recursive);
    }
}

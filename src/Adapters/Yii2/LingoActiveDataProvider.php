<?php
declare(strict_types=1);

namespace FieldLingo\Adapters\Yii2;
/**
 * @file LingoActiveDataProvider.php - ActiveDataProvider supporting localized attribute names.
 * Class LingoActiveDataProvider
 *
 * DataProvider that adjusts sort attributes and defaultOrder to localized column names.
 * This file is part of FieldLingo package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @license MIT
 * @package FieldLingo\Adapters\Yii2
 * @author  Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 * @since 1.0.0
 */
use yii\data\ActiveDataProvider;

class LingoActiveDataProvider extends ActiveDataProvider
{
    use LocalizedAttributeTrait;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        // If query's model class supports localized settings, import them.
        if ($this->query && isset($this->query->modelClass) && is_string($this->query->modelClass)) {
            $modelClass = $this->query->modelClass;
            if (method_exists($modelClass, 'getLocalizedSettings')) {
                $model = new $modelClass();
                $settings = $model->getLocalizedSettings();
                foreach ($settings as $k => $v) {
                    if (property_exists($this, $k)) {
                        $this->$k = $v;
                    }
                }
            }
        }

        $this->processSortAttributes();
    }

    /**
     * Process sort attributes to convert them to localized names.
     * Convert sort->attributes keys to localized ones.
     * @return void
     */
    protected function processSortAttributes(): void
    {
        if (!$this->sort || empty($this->sort->attributes)) {
            return;
        }

        $localizedAttributes = [];
        foreach ($this->sort->attributes as $name => $definition) {
            $localizedName = $this->getLocalizedAttributeName((string)$name);
            $localizedAttributes[$localizedName] = $definition;
        }
        $this->sort->attributes = $localizedAttributes;

        if (!empty($this->sort->defaultOrder)) {
            $localizedOrder = [];
            foreach ($this->sort->defaultOrder as $attribute => $direction) {
                $localizedName = $this->getLocalizedAttributeName((string)$attribute);
                $localizedOrder[$localizedName] = $direction;
            }
            $this->sort->defaultOrder = $localizedOrder;
        }
    }
}

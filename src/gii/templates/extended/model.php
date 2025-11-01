<?php
namespace <?= $generator->ns ?>\base;

use <?= $generator->baseClass ?>;

/**
 * Base model for table "<?= $tableName ?>"
 */
class <?= $className ?> extends <?= basename(str_replace('\\', '/', $generator->baseClass)) ?>
{
    public static function tableName()
    {
        return '{{%<?= $tableName ?>}}';
    }

    public function rules()
    {
        return [
            <?= empty($rules) ? '' : implode(",\n            ", $rules) ?>,
        ];
    }

    public function attributeLabels()
    {
        return [
            <?= empty($labels) ? '' : implode(",\n            ", array_map(fn($k,$v) => "'$k' => $v", array_keys($labels), $labels)) ?>,
        ];
    }
}

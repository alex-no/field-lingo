<?php
use yii\helpers\Html;

/** @var $generator \AlexNo\FieldLingo\Gii\ExtendedModelGenerator */

echo $form->field($generator, 'db');
echo $form->field($generator, 'tableName');
echo $form->field($generator, 'modelClass');
echo $form->field($generator, 'ns');
echo $form->field($generator, 'baseClass')->textInput(['list' => 'base-class-options']);
echo Html::tag('datalist', implode("\n", array_map(fn($c) => Html::tag('option','',['value'=>$c]), $generator->baseClassOptions)), ['id'=>'base-class-options']);
echo $form->field($generator, 'generateChildClass')->checkbox();

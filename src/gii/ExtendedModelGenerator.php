<?php
namespace AlexNo\FieldLingo\Gii;

use Yii;
use yii\gii\generators\model\Generator;
use yii\gii\CodeFile;

/**
 * Advanced Model Generator for Yii2 using FieldLingo
 */
class ExtendedModelGenerator extends Generator
{
    public $generateChildClass = true;

    public $baseClassOptions = [
        'yii\db\ActiveRecord',
        'AlexNo\FieldLingo\Adapters\Yii2\LingoActiveRecord',
    ];

    public $queryBaseClassOptions = [
        'yii\db\ActiveQuery',
        'AlexNo\FieldLingo\Adapters\Yii2\LingoActiveQuery',
    ];

    public function getName()
    {
        return 'FieldLingo Extended Model Generator';
    }

    public function getDescription()
    {
        return 'Generates a parent model class with FieldLingo support and an optional child class for custom methods.';
    }

    public function rules()
    {
        return array_merge(parent::rules(), [
            ['generateChildClass', 'boolean'],
        ]);
    }

    public function generate()
    {
        $files = parent::generate();

        $modelClass = $this->getModelClass();
        $baseFile = dirname($files[0]->path) . '/base/' . $modelClass . '.php';

        // We move the main file to base
        foreach ($files as $i => $file) {
            if (str_ends_with($file->path, $modelClass . '.php')) {
                $files[$i]->path = $baseFile;
                if (file_exists($baseFile) && $files[$i]->operation === CodeFile::OP_CREATE) {
                    $files[$i]->operation = CodeFile::OP_OVERWRITE;
                }
                break;
            }
        }

        // Generation of child class
        if ($this->generateChildClass) {
            $childPath = Yii::getAlias('@app/models/' . $modelClass . '.php');
            if (!file_exists($childPath)) {
                $files[] = new CodeFile(
                    $childPath,
                    $this->render('model-child.php', ['className' => $modelClass])
                );
            }
        }

        return $files;
    }

    protected function getModelClass(): string
    {
        if (empty($this->modelClass)) {
            throw new \RuntimeException('ModelClass is not set.');
        }
        return basename(str_replace('\\', '/', $this->modelClass));
    }
}

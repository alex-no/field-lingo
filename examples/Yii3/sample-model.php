<?php
/**
 * Example Yii3 model using LingoActiveRecord
 *
 * This example demonstrates how to use Field-lingo with Yii3 ActiveRecord.
 */

namespace App\Models;

use FieldLingo\Adapters\Yii3\LingoActiveRecord;
use FieldLingo\Adapters\Yii3\LingoActiveQuery;

/**
 * Post model
 *
 * Database table structure:
 * - id (int)
 * - title_en (varchar)
 * - title_uk (varchar)
 * - title_ru (varchar)
 * - content_en (text)
 * - content_uk (text)
 * - content_ru (text)
 * - created_at (datetime)
 */
class Post extends LingoActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%post}}';
    }

    /**
     * Override find() to return LingoActiveQuery
     * IMPORTANT: This is required for localized field support in queries
     *
     * @return LingoActiveQuery
     */
    public static function find(): LingoActiveQuery
    {
        return new LingoActiveQuery(static::class);
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['title_en', 'title_uk'], 'required'],
            [['content_en', 'content_uk'], 'string'],
            [['title_en', 'title_uk', 'title_ru'], 'string', 'max' => 255],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'title_en' => 'Title (English)',
            'title_uk' => 'Title (Ukrainian)',
            'title_ru' => 'Title (Russian)',
            'content_en' => 'Content (English)',
            'content_uk' => 'Content (Ukrainian)',
            'content_ru' => 'Content (Russian)',
            'created_at' => 'Created At',
        ];
    }
}

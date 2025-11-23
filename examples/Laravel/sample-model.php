<?php

/**
 * Example Laravel Model using FieldLingo
 *
 * This example shows how to use FieldLingo with Laravel Eloquent models
 * to automatically handle localized database columns.
 *
 * Database structure:
 * posts table:
 *   - id (int)
 *   - title_en (varchar)
 *   - title_uk (varchar)
 *   - title_ru (varchar)
 *   - content_en (text)
 *   - content_uk (text)
 *   - content_ru (text)
 *   - created_at (timestamp)
 *   - updated_at (timestamp)
 */

namespace App\Models;

use FieldLingo\Adapters\Laravel\LingoModel;

/**
 * Post Model with localized attributes
 *
 * @property int $id
 * @property string $title_en
 * @property string $title_uk
 * @property string $title_ru
 * @property string $content_en
 * @property string $content_uk
 * @property string $content_ru
 */
class Post extends LingoModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title_en',
        'title_uk',
        'title_ru',
        'content_en',
        'content_uk',
        'content_ru',
    ];

    /**
     * Optional: Override localization settings for this specific model
     * These settings override the defaults from config/field-lingo.php
     */
    // public $localizedPrefixes = '@@';
    // public bool $isStrict = true;
    // public string $defaultLanguage = 'en';
}

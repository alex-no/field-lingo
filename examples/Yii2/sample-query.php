<?php
/**
 * Sample usage of LingoActiveQuery
 *
 * This example demonstrates how to use Field-lingo with Yii2 ActiveQuery
 * to build queries with localized field names.
 */

use FieldLingo\Adapters\Yii2\LingoActiveRecord;
use FieldLingo\Adapters\Yii2\LingoActiveQuery;

/**
 * Example Post model with localized fields and custom query
 */
class Post extends LingoActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'post';
    }

    /**
     * @inheritdoc
     * @return LingoActiveQuery
     */
    public static function find()
    {
        return new LingoActiveQuery(get_called_class());
    }
}

// Usage examples:
// Assuming Yii::$app->language is set to 'en' (English)

// 1. Select with localized fields
$posts = Post::find()
    ->select(['id', '@@title', '@@content'])  // Selects: id, title_en, content_en
    ->all();

// 2. Where condition with localized field
$posts = Post::find()
    ->where(['@@title' => 'Hello World'])  // WHERE title_en = 'Hello World'
    ->all();

// 3. Order by localized field
$posts = Post::find()
    ->orderBy(['@@title' => SORT_ASC])  // ORDER BY title_en ASC
    ->all();

// 4. Complex query with multiple localized fields
$posts = Post::find()
    ->select(['id', '@@title', '@@content'])
    ->where(['like', '@@title', 'News'])  // WHERE title_en LIKE '%News%'
    ->andWhere(['>', 'created_at', '2024-01-01'])
    ->orderBy(['@@title' => SORT_DESC])
    ->limit(10)
    ->all();

// 5. Group by localized field
$stats = Post::find()
    ->select(['@@category', 'COUNT(*) as count'])
    ->groupBy(['@@category'])  // GROUP BY category_en
    ->asArray()
    ->all();

// 6. Filter where with localized fields
$posts = Post::find()
    ->filterWhere([
        '@@title' => $_GET['title'] ?? null,  // Filters by title_en if provided
        '@@category' => $_GET['category'] ?? null,
    ])
    ->all();

foreach ($posts as $post) {
    echo $post->getAttribute('@@title') . "\n";
    echo $post->getAttribute('@@content') . "\n\n";
}

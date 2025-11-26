<?php
/**
 * Example usage of Field-lingo with Yii3
 *
 * This file demonstrates various ways to use localized attributes with Yii3 ActiveRecord.
 */

use App\Models\Post;

// ========================================
// Configuration (in your config/params.php or similar)
// ========================================

/*
return [
    'field-lingo' => [
        'default-locale' => 'en',
        'strict-mode' => false,
        'prefixes' => '@@',
    ],
];
*/

// ========================================
// Example 1: Creating a new record
// ========================================

$post = new Post();

// Set locale before working with localized attributes
$post->setLocale('uk');

// Set localized attributes using @@ prefix
$post->setAttribute('@@title', 'Новина дня');
$post->setAttribute('@@content', 'Текст новини українською мовою');

// Or using property access
$post->{'@@title'} = 'Новина дня';
$post->{'@@content'} = 'Текст новини українською мовою';

// Save the record
$post->save();

echo "Post created with ID: {$post->id}\n";

// ========================================
// Example 2: Reading localized attributes
// ========================================

$post = Post::findOne(1);

// Set locale for reading
$post->setLocale('en');
echo "Title (EN): " . $post->getAttribute('@@title') . "\n";

$post->setLocale('uk');
echo "Title (UK): " . $post->getAttribute('@@title') . "\n";

// Or using property access
echo "Content (UK): " . $post->{'@@content'} . "\n";

// ========================================
// Example 3: Querying with localized fields
// ========================================

// Create query with locale
$query = Post::find()->setLocale('uk');

// Select specific localized fields
$posts = $query
    ->select(['id', '@@title', '@@content'])
    ->where(['@@title' => 'Новина дня'])
    ->all();

// ========================================
// Example 4: Where conditions with localized fields
// ========================================

$posts = Post::find()
    ->setLocale('en')
    ->where(['like', '@@title', 'News'])
    ->andWhere(['>', 'created_at', '2024-01-01'])
    ->orderBy(['@@title' => SORT_ASC])
    ->all();

foreach ($posts as $post) {
    echo "Title: " . $post->getAttribute('@@title') . "\n";
}

// ========================================
// Example 5: Ordering by localized fields
// ========================================

$posts = Post::find()
    ->setLocale('uk')
    ->orderBy(['@@title' => SORT_DESC])
    ->limit(10)
    ->all();

// ========================================
// Example 6: Group by localized fields
// ========================================

$stats = Post::find()
    ->setLocale('en')
    ->select(['@@category', 'COUNT(*) as count'])
    ->groupBy(['@@category'])
    ->asArray()
    ->all();

print_r($stats);

// ========================================
// Example 7: Using with Translator service (DI)
// ========================================

/*
// In your container configuration
$container->set(Post::class, function ($container) {
    $post = new Post();
    $post->setTranslator($container->get(TranslatorInterface::class));
    return $post;
});

// Now the locale will be automatically taken from Translator
$post = $container->get(Post::class);
echo $post->getAttribute('@@title'); // Uses translator's current locale
*/

// ========================================
// Example 8: Complex query with multiple conditions
// ========================================

$posts = Post::find()
    ->setLocale('uk')
    ->select(['id', '@@title', '@@content', 'created_at'])
    ->where(['like', '@@title', 'Новини'])
    ->andWhere(['>', 'created_at', '2024-01-01'])
    ->orderBy(['@@title' => SORT_DESC, 'created_at' => SORT_DESC])
    ->limit(20)
    ->all();

// ========================================
// Example 9: Working with toArray()
// ========================================

$post = Post::findOne(1);
$post->setLocale('uk');

// Export with localized fields
$data = $post->toArray(['id', '@@title', '@@content', 'created_at']);
print_r($data);
// Output: ['id' => 1, 'title_uk' => 'Новина дня', 'content_uk' => '...', 'created_at' => '...']

// ========================================
// Example 10: Fallback mechanism (when strict mode is off)
// ========================================

// If you set isStrict = false in configuration
$post = new Post();
$post->isStrict = false;
$post->setLocale('ru'); // Russian locale

// If title_ru doesn't exist in DB, it will fall back to defaultLanguage (e.g., 'en')
$title = $post->getAttribute('@@title'); // Will return title_en if title_ru is missing

echo "\n=== All examples completed ===\n";

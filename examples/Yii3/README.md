# Field-lingo Yii3 Examples

This directory contains examples of using Field-lingo with Yii3 framework.

## Overview

Field-lingo for Yii3 provides seamless integration with Yii3's ActiveRecord system, allowing you to work with multi-language database columns using a simple `@@` prefix notation.

## Files

- **sample-model.php** - Example model extending `LingoActiveRecord`
- **sample-usage.php** - Various usage examples demonstrating different features

## Key Features

### 1. Locale Management

Yii3 adapter uses explicit locale setting instead of relying on application-level language configuration:

```php
$post = new Post();
$post->setLocale('uk');  // Set Ukrainian locale
```

### 2. Translator Integration

You can integrate with Yii3's `TranslatorInterface` for automatic locale resolution:

```php
$post->setTranslator($translator);
// Locale is now automatically taken from translator
```

### 3. Localized Attribute Access

```php
// Reading
$title = $post->getAttribute('@@title');  // Returns title_uk if locale is 'uk'

// Writing
$post->setAttribute('@@title', 'Новина дня');

// Property access
echo $post->{'@@title'};
$post->{'@@title'} = 'New value';
```

### 4. Query Support

```php
$posts = Post::find()
    ->setLocale('en')
    ->select(['id', '@@title', '@@content'])
    ->where(['like', '@@title', 'News'])
    ->orderBy(['@@title' => SORT_ASC])
    ->all();
```

## Configuration

### Basic Configuration

Configure Field-lingo settings for your models:

```php
// In your model class
class Post extends LingoActiveRecord
{
    public function __construct()
    {
        parent::__construct();

        // Configure localization settings
        $this->localizedPrefixes = '@@';  // or ['@@', '##']
        $this->isStrict = false;          // Use fallback on missing columns
        $this->defaultLanguage = 'en';    // Fallback language
    }

    // IMPORTANT: Override find() for query support
    public static function find(): LingoActiveQuery
    {
        return new LingoActiveQuery(static::class);
    }
}
```

### Using with Dependency Injection

```php
// In your container configuration
$container->set(Post::class, function ($container) {
    $post = new Post();
    $post->setTranslator($container->get(TranslatorInterface::class));
    $post->isStrict = false;
    $post->defaultLanguage = 'en';
    return $post;
});
```

## Database Schema Example

```sql
CREATE TABLE post (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title_en VARCHAR(255) NOT NULL,
    title_uk VARCHAR(255),
    title_ru VARCHAR(255),
    content_en TEXT NOT NULL,
    content_uk TEXT,
    content_ru TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## Differences from Yii2 Adapter

1. **Explicit Locale Setting**: Unlike Yii2 which uses `Yii::$app->language`, Yii3 adapter requires explicit locale setting via `setLocale()` or `setTranslator()`

2. **Type Hints**: Yii3 adapter uses modern PHP 8.2+ type hints and return types throughout

3. **Dependency Injection**: Better integration with Yii3's DI container through `setTranslator()`

4. **No Global State**: Doesn't rely on global application state for configuration

## Common Use Cases

### 1. Multi-language Blog

```php
$post = new Post();
$post->setLocale('uk');
$post->{'@@title'} = 'Привіт, світ!';
$post->{'@@content'} = 'Це мій перший пост';
$post->save();

// Later, read in different language
$post->setLocale('en');
echo $post->{'@@title'};  // Will fallback to English if available
```

### 2. Dynamic Language Switching

```php
function getPostTitle(int $postId, string $locale): string
{
    $post = Post::findOne($postId);
    $post->setLocale($locale);
    return $post->getAttribute('@@title');
}

echo getPostTitle(1, 'uk');  // Ukrainian title
echo getPostTitle(1, 'en');  // English title
```

### 3. Search in Current Language

```php
function searchPosts(string $query, string $locale): array
{
    return Post::find()
        ->setLocale($locale)
        ->where(['like', '@@title', $query])
        ->orWhere(['like', '@@content', $query])
        ->orderBy(['@@title' => SORT_ASC])
        ->all();
}
```

## Troubleshooting

### Issue: Localized fields not working in queries

**Solution**: Make sure you override the `find()` method in your model:

```php
public static function find(): LingoActiveQuery
{
    return new LingoActiveQuery(static::class);
}
```

### Issue: Getting wrong language results

**Solution**: Explicitly set locale before querying:

```php
$query = Post::find()->setLocale('uk');  // Set locale on query
// OR
$post->setLocale('uk');  // Set locale on model instance
```

### Issue: MissingLocalizedAttributeException thrown

**Solution**: Either:
1. Add the missing column to your database
2. Set `isStrict = false` to enable fallback to default language

```php
$post->isStrict = false;
$post->defaultLanguage = 'en';
```

## Further Reading

- [Main README](../../README.md) - Full documentation
- [Yii3 ActiveRecord Documentation](https://github.com/yiisoft/active-record)
- [Yii3 Translator Documentation](https://github.com/yiisoft/translator)

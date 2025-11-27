# 🗂️ Field-lingo

[![Packagist Version](https://img.shields.io/packagist/v/alex-no/field-lingo.svg)](https://packagist.org/packages/alex-no/field-lingo)
[![PHP Version](https://img.shields.io/packagist/php-v/alex-no/field-lingo.svg)](https://packagist.org/packages/alex-no/field-lingo)
[![Total Downloads](https://img.shields.io/packagist/dt/alex-no/field-lingo.svg)](https://packagist.org/packages/alex-no/field-lingo)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Field-lingo** — lightweight library to easily work with database columns that store multiple language versions of the same attribute in one row (e.g. `name_en`, `name_uk`, `name_ru`).
It provides a simple, consistent mechanism to reference "structured localized attribute names" (like `@@name`) and transparently map them to the actual column `name_<lang>` according to current language settings.

This repository contains full integrations for:
- **Yii2** (ActiveRecord / ActiveQuery / DataProvider) — `src/Adapters/Yii2`
- **Yii3** (ActiveRecord / ActiveQuery) — `src/Adapters/Yii3`
- **Laravel** (Eloquent Models / Query Builder) — `src/Adapters/Laravel`
- **Symfony** (Doctrine Entities / Repositories / QueryBuilder) — `src/Adapters/Symfony`
- **Framework-agnostic core** — `src/Core` for custom implementations

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Requirements](#-requirements)
- [Key Classes](#-key-classes)
- [Quick Start](#-quick-start)
  - [Yii2](#yii2)
  - [Yii3](#yii3)
  - [Laravel](#laravel)
  - [Symfony](#symfony)
- [Detailed Usage (Yii2)](#️-detailed-usage-yii2)
  - [Install](#install)
  - [Optional Recommendation](#optional-recommendation)
  - [Basic Idea](#basic-idea)
  - [Configure](#configure)
  - [Configuration Options](#configuration-options)
- [LocalizedAttributeTrait Behavior](#-localizedattributetrait--behavior-summary)
- [Usage Examples](#-usage-examples)
  - [ActiveRecord](#activerecord)
  - [ActiveQuery](#activequery)
  - [ActiveDataProvider](#activedataprovider)
- [Fallback Mechanism](#-fallback-mechanism)
- [Exception Handling](#️-exception)
- [Advanced Topics](#-advanced-topics--hooks)
- [Migration Guide](#-migration-guide)
- [Troubleshooting](#-troubleshooting)
- [Core Design](#-core-design)
- [Directory Structure](#-directory-structure)
- [Examples](#examples)
- [Testing](#-testing)
- [Contribution](#-contribution)
- [Roadmap](#️-roadmap)
- [License](#-license)
- [Contact](#-contact)

---

## 🌍 Overview

Field-lingo provides three Yii2 adapters that transparently translate specially formatted field names into language-specific attributes. The pattern is simple: a prefix (by default `@@`) marks a structured field name. When Field-lingo encounters a name like `@@name`, it resolves the current language and converts that token to `name_{lang}` (for example `name_en` or `name_uk`).

Works in:
  - Attribute access (`$model->@@name`) and property-style (`$model->name` via trait).
  - Query building: `select`, `where`, `orderBy`, `groupBy` using `@@` names.
  - DataProvider sorting integration.

Primary goals:
 - Allow code and queries to use language-agnostic field names (`@@title`) and get language-specific attributes automatically.
 - Support per-adapter and per-model configuration (prefixes, fallback language, strict mode).
 - Keep the adapter API close to native Yii classes so integration is minimal.

---

## 📦 Requirements

- **PHP**: >= 8.2

**Framework-specific requirements:**
- **Yii2**: ^2.0 (for Yii2 adapter)
- **Yii3**: ^3.0 (yiisoft/active-record ^3.0, optional: yiisoft/translator ^3.0)
- **Laravel**: ^9.0 || ^10.0 || ^11.0 (for Laravel adapter)
- **Symfony**: ^5.4 || ^6.0 || ^7.0 (for Symfony adapter)
- **Doctrine ORM**: ^2.10 || ^3.0 (for Symfony/Doctrine adapter)

**Optional but recommended:**
- [alex-no/language-detector](https://packagist.org/packages/alex-no/language-detector) — for automatic user language detection (requires separate configuration)

---

## 🧩 Key classes

- **`\FieldLingo\Adapters\Yii2\LingoActiveRecord`**
   - Extends `yii\db\ActiveRecord`.
   - Used when working with model attributes (reads/writes, forms, `toArray()`).

- **`\FieldLingo\Adapters\Yii2\LingoActiveQuery`**
   - Extends `yii\db\ActiveQuery`.
   - Used to transform field names in conditions, `select()` lists, and custom textual SQL logic within the query layer.

- **`\FieldLingo\Adapters\Yii2\LingoActiveDataProvider`**
   - Extends `yii\data\ActiveDataProvider` (or `yii\db\ActiveDataProvider` depending on implementation).
   - Used for operations that require field translation in the data provider level (for example sorting, pagination where attribute names are passed externally).

These adapters rely on a shared trait `LocalizedAttributeTrait` which performs the core parsing and resolution logic.

---

## ⚙️ Quick Start

### Installation

```bash
composer require alex-no/field-lingo
```

Choose your framework adapter:

### Yii2

#### 1. Install

```bash
composer require alex-no/field-lingo
```

### Optional Recommendation

For automatic user language detection, it is recommended to install:

```bash
composer require alex-no/language-detector
```

> **Note:** This package requires its own separate configuration.

### Basic idea

In DB table we keep language-specific columns:

```bash
id | name_en | name_uk | name_ru | created_at
```

In code we refer to `@@name`. FieldLingo maps `@@name → name_{lang}` (e.g. `name_uk`) depending on `Yii::$app->language`.

---

### Configure

Add to `params` (or any config area) the LingoActive section (example):

```php
'params' => [
    'LingoActive' => [
        // Global defaults for adapters
        \FieldLingo\Adapters\Yii2\LingoActiveRecord::class => [
            'localizedPrefixes' => '@@',   // or ['@@', '##']
            'isStrict' => true,            // throw on missing localized attribute
            'defaultLanguage' => 'en',     // fallback language code
        ],
        \FieldLingo\Adapters\Yii2\LingoActiveQuery::class => [
            'localizedPrefixes' => '@@',
        ],
        // Optional per-model override example:
        // \app\models\PetType::class => [
        //     'localizedPrefixes' => '##',
        //     'isStrict' => false,
        //     'defaultLanguage' => 'uk',
        // ],
    ],
],
```

> Notes:
 - Per-model overrides have higher priority than adapter-level defaults.
 - The trait reads Yii::$app->params['LingoActive'] by adapter name or model class name.

### Configuration options

Main options:
 - `localizedPrefixes` (string|array) — prefix(es) used to mark structured names. Default: `@@`.
 - `defaultLanguage` (string) — fallback language when localized column is missing. Default: `en`.
 - `isStrict (bool)` — if true throw when localized column missing; if `false` fallback to `defaultLanguage`.

These options may be set globally, per-class (LingoActiveRecord / LingoActiveQuery) or per-model.

### Yii3

#### 1. Install

```bash
composer require alex-no/field-lingo
composer require yiisoft/active-record
```

#### 2. Extend your models

```php
use FieldLingo\Adapters\Yii3\LingoActiveRecord;
use FieldLingo\Adapters\Yii3\LingoActiveQuery;

class Post extends LingoActiveRecord
{
    public static function tableName(): string
    {
        return '{{%post}}';
    }

    /**
     * IMPORTANT: Override find() to return LingoActiveQuery
     */
    public static function find(): LingoActiveQuery
    {
        return new LingoActiveQuery(static::class);
    }
}
```

#### 3. Use localized attributes

```php
// Create
$post = new Post();
$post->setLocale('uk');  // Set current locale
$post->setAttribute('@@title', 'Новина дня');
$post->setAttribute('@@content', 'Текст новини');
$post->save();

// Read
$post->setLocale('en');
echo $post->getAttribute('@@title');

// Query
$posts = Post::find()
    ->setLocale('uk')
    ->select(['id', '@@title', '@@content'])
    ->where(['like', '@@title', 'Новини'])
    ->orderBy(['@@title' => SORT_ASC])
    ->all();

// Query with pagination
$posts = Post::find()
    ->setLocale('uk')
    ->select(['id', '@@title', '@@content'])
    ->where(['like', '@@title', 'Новини'])
    ->orderBy(['@@title' => SORT_ASC])
    ->limit(10)
    ->offset(20)
    ->all();

// Count records
$count = Post::find()
    ->setLocale('uk')
    ->where(['like', '@@title', 'Новини'])
    ->count();
```

#### 4. Database Connection

The compatibility layer uses PDO for database access. You can configure it in two ways:

**1. Via Dependency Injection (recommended):**
```php
use Yiisoft\ActiveRecord\ActiveRecord;

// In your DI container
$container->set(\PDO::class, function() {
    $dsn = "mysql:host=localhost;dbname=mydb;charset=utf8mb4";
    return new \PDO($dsn, 'username', 'password');
});

// Set in your models
ActiveRecord::setDb($container->get(\PDO::class));
```

**2. Via Environment Variables:**
```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=mydb
DB_USER=username
DB_PASSWORD=password
```

#### 5. Optional: Integrate with Translator service

```php
use Yiisoft\Translator\TranslatorInterface;

// In your DI container configuration
$container->set(Post::class, function ($container) {
    $post = new Post();
    $post->setTranslator($container->get(TranslatorInterface::class));
    return $post;
});

// Now locale is automatically taken from translator
$post = $container->get(Post::class);
echo $post->getAttribute('@@title'); // Uses translator's current locale
```

#### 6. Working with Relations

```php
class Post extends LingoActiveRecord
{
    public function getCategory(): ActiveQueryInterface
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    public function getComments(): ActiveQueryInterface
    {
        return $this->hasMany(Comment::class, ['post_id' => 'id']);
    }
}

// Usage
$post = Post::findOne(1);
$post->setLocale('uk');
echo $post->category->getAttribute('@@name');  // Localized category name

$comments = $post->comments;
```

#### Compatibility Layer

The Yii3 adapter includes a compatibility layer ([src/Adapters/Yii3/Compatibility/](src/Adapters/Yii3/Compatibility/)) that provides basic ActiveRecord and ActiveQuery functionality using PDO. This layer includes:

- Basic CRUD operations (`findOne()`, `all()`, `one()`, `count()`)
- Query building (`select()`, `where()`, `orderBy()`, `groupBy()`, `limit()`, `offset()`)
- Attribute management (`getAttribute()`, `setAttribute()`, magic properties)
- Simple relations support (`hasMany()`, `hasOne()`)

This compatibility layer is designed to work until Yii3 has an official stable ActiveRecord implementation.

#### Key Differences from Yii2:

- **Explicit locale setting**: Use `setLocale('uk')` instead of relying on `Yii::$app->language`
- **Translator integration**: Optional integration with `yiisoft/translator` for automatic locale detection
- **Modern PHP**: Uses PHP 8.2+ type hints and return types
- **No global state**: Doesn't depend on global application configuration

**See [examples/Yii3/](examples/Yii3/) for complete examples and detailed documentation.**

### Laravel

#### 1. Extend your Eloquent models

```php
use FieldLingo\Adapters\Laravel\LingoModel;

class Product extends LingoModel
{
    protected $table = 'products';

    protected $fillable = ['name_en', 'name_uk', 'description_en', 'description_uk', 'price'];
}
```

#### 2. Use localized attributes

```php
// Create
$product = new Product();
$product->setAttribute('@@name', 'Laptop');
$product->setAttribute('@@description', 'High-performance laptop');
$product->save();

// Read
echo $product->getAttribute('@@name');

// Query
$products = Product::where('@@name', 'LIKE', '%Laptop%')
    ->orderBy('@@name', 'asc')
    ->get();
```

**See [examples/Laravel/](examples/Laravel/) for complete examples.**

### Symfony

#### 1. Extend your Doctrine entities

```php
use FieldLingo\Adapters\Symfony\LingoEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product extends LingoEntity
{
    #[ORM\Column(type: 'string')]
    private ?string $name_en = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $name_uk = null;

    // Getters and setters...
}
```

#### 2. Create repository

```php
use FieldLingo\Adapters\Symfony\LingoRepository;

class ProductRepository extends LingoRepository
{
    public function findByName(string $name, string $locale = 'en'): array
    {
        return $this->setLocale($locale)
            ->createQueryBuilder('p')
            ->where('p.@@name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult();
    }
}
```

#### 3. Use in controllers

```php
$product = new Product();
$product->setCurrentLocale($request->getLocale());
$product->{'@@name'} = 'Laptop';
$product->{'@@description'} = 'High-performance laptop';

$entityManager->persist($product);
$entityManager->flush();
```

**See [examples/Symfony/](examples/Symfony/) for complete examples and configuration.**

---

## ⚙️ Detailed Usage (Yii2)

---

## 🧠 LocalizedAttributeTrait — behavior summary

The `LocalizedAttributeTrait` does the heavy lifting:
 - Normalizes `localizedPrefixes` to an array (supports a single prefix string or an array).
 - Reads runtime language from `Yii::$app->language` and uses its first part (e.g. en-US → en).
 - Produces a candidate attribute name `{base}_{lang}`.
 - If the using class implements `hasAttribute()` (as ActiveRecord does), the trait checks attribute existence:
    - If attribute exists — returns it.
    - If not and `isStrict === true` — throws `MissingLocalizedAttributeException`.
    - If not and `isStrict === false` — tries fallback `{base}_{defaultLanguage}` and returns it if exists; otherwise returns the candidate.
 - If `hasAttribute()` is not available (e.g. at query layer), the trait returns the candidate name and lets the caller use it in SQL / selections.

You can call `$this->convertLocalizedFields([ ... ])` to map arrays of fields at once.

---

## 🚀 Usage examples

### ActiveRecord

When using `LingoActiveRecord`, you can reference localized attributes directly in code:

```php
use FieldLingo\Adapters\Yii2\LingoActiveRecord;

/**
 * Example Post model
 * Table columns: id, title_en, title_uk, title_ru, content_en, content_uk, content_ru, created_at
 */
class Post extends LingoActiveRecord
{
    public static function tableName()
    {
        return 'post';
    }

    public function rules()
    {
        return [
            [['title_en', 'title_uk'], 'required'],
            [['content_en', 'content_uk'], 'string'],
            [['title_en', 'title_uk', 'title_ru'], 'string', 'max' => 255],
        ];
    }
}

// ===== Reading localized attributes =====
// Assuming Yii::$app->language = 'uk'
$post = Post::findOne(1);
$title = $post->getAttribute('@@title');  // Returns title_uk value
$content = $post->getAttribute('@@content');  // Returns content_uk value

// ===== Creating and saving records =====
$post = new Post();
$post->setAttribute('@@title', 'Новина дня');  // Sets title_uk
$post->setAttribute('@@content', 'Текст новини');  // Sets content_uk
$post->save();

// ===== Property-style access =====
echo $post->{'@@title'};  // Same as getAttribute('@@title')

// ===== Array export with localized fields =====
$data = $post->toArray(['id', '@@title', '@@content', 'created_at']);
// Result: ['id' => 1, 'title_uk' => 'Новина дня', 'content_uk' => 'Текст новини', 'created_at' => '...']
```

> **Notes for ActiveRecord:**
> - Because `hasAttribute()` is available, missing localized columns are validated according to `isStrict`.
> - If you rely on `toArray()` or `fields()` to export language-aware data, ensure the adapter or model calls `convertLocalizedFields()` where appropriate.

### ActiveQuery

`LingoActiveQuery` resolves names used in `select()`, `andWhere()`, `orderBy()`, `groupBy()` and similar places.

> **CRITICAL: Override the `find()` method**
> To use `LingoActiveQuery`, you **must** override the `find()` method in your model:

```php
use FieldLingo\Adapters\Yii2\LingoActiveRecord;
use FieldLingo\Adapters\Yii2\LingoActiveQuery;

class Post extends LingoActiveRecord
{
    public static function tableName()
    {
        return 'post';
    }

    /**
     * IMPORTANT: Override find() to return LingoActiveQuery
     * @return LingoActiveQuery
     */
    public static function find()
    {
        return new LingoActiveQuery(get_called_class());
    }
}
```

**Now you can use `@@` fields in queries:**

```php
// ===== Simple select =====
// Assuming Yii::$app->language = 'en'
$posts = Post::find()
    ->select(['id', '@@title', '@@content'])  // Selects: id, title_en, content_en
    ->all();

// ===== Where conditions =====
$posts = Post::find()
    ->where(['@@title' => 'Hello World'])  // WHERE title_en = 'Hello World'
    ->all();

$posts = Post::find()
    ->where(['like', '@@title', 'News'])  // WHERE title_en LIKE '%News%'
    ->all();

// ===== Order by localized field =====
$posts = Post::find()
    ->orderBy(['@@title' => SORT_ASC])  // ORDER BY title_en ASC
    ->all();

// ===== Complex query example =====
$posts = Post::find()
    ->select(['id', '@@title', '@@content'])
    ->where(['like', '@@title', 'News'])
    ->andWhere(['>', 'created_at', '2024-01-01'])
    ->orderBy(['@@title' => SORT_DESC])
    ->limit(10)
    ->all();

// ===== Group by localized field =====
$stats = Post::find()
    ->select(['@@category', 'COUNT(*) as count'])
    ->groupBy(['@@category'])  // GROUP BY category_en
    ->asArray()
    ->all();

// ===== FilterWhere with dynamic params =====
$posts = Post::find()
    ->filterWhere([
        '@@title' => $_GET['title'] ?? null,  // Only adds to WHERE if title is provided
        '@@category' => $_GET['category'] ?? null,
    ])
    ->all();
```

> **Notes for ActiveQuery:**
> - Query layer cannot check `hasAttribute()` easily before SQL execution. The trait returns language-specific candidates and the DB will determine if the column exists.
> - Without overriding `find()`, your queries will use standard `ActiveQuery` and `@@` fields will not be converted.

### ActiveDataProvider

`LingoActiveDataProvider` is helpful when you expose sorting/filtering to external requests (like GridView) and need to map `@@` tokens to real DB columns.

**Basic usage:**

```php
use FieldLingo\Adapters\Yii2\LingoActiveDataProvider;

$dataProvider = new LingoActiveDataProvider([
    'query' => Post::find(),
    'pagination' => [
        'pageSize' => 20,
    ],
    'sort' => [
        'attributes' => [
            'id',
            '@@title',    // Enables sorting by title_{lang}
            '@@category', // Enables sorting by category_{lang}
            'created_at',
        ],
    ],
]);
```

**Usage with GridView:**

```php
use yii\grid\GridView;

echo GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        [
            'attribute' => '@@title',
            'label' => 'Title',
            'value' => function ($model) {
                return $model->getAttribute('@@title');
            },
        ],
        [
            'attribute' => '@@category',
            'label' => 'Category',
            'filter' => ['news' => 'News', 'blog' => 'Blog'],
            'value' => function ($model) {
                return $model->getAttribute('@@category');
            },
        ],
        'created_at:datetime',
        ['class' => 'yii\grid\ActionColumn'],
    ],
]);
```

**Advanced: Custom sort configuration**

```php
$dataProvider = new LingoActiveDataProvider([
    'query' => Post::find()->where(['status' => 'published']),
    'sort' => [
        'attributes' => [
            '@@title' => [
                'asc' => ['@@title' => SORT_ASC],
                'desc' => ['@@title' => SORT_DESC],
                'default' => SORT_ASC,
                'label' => 'Title',
            ],
        ],
        'defaultOrder' => [
            '@@title' => SORT_ASC,
        ],
    ],
]);
```

> **Notes for ActiveDataProvider:**
> - `LingoActiveDataProvider` automatically converts `@@` field names in sort attributes and filter conditions.
> - When defining custom sort attributes, use `@@` notation consistently across query, sort config, and GridView columns.
> - The provider works seamlessly with Yii2's pagination and filtering mechanisms.


---

## 🔄 Fallback Mechanism

Field-lingo includes a smart fallback system to handle missing localized columns gracefully. The behavior depends on the `isStrict` configuration option.

### How Fallback Works

When you request a localized attribute (e.g., `@@title` with current language = `uk`):

1. **Library looks for `title_uk`**
   - If exists → returns `title_uk` ✅
   - If not exists → proceeds to step 2

2. **Check `isStrict` mode:**
   - **If `isStrict = true`** → throws `MissingLocalizedAttributeException` 🚫
   - **If `isStrict = false`** → tries fallback language (step 3)

3. **Fallback to `defaultLanguage`:**
   - Library looks for `title_{defaultLanguage}` (e.g., `title_en` if `defaultLanguage = 'en'`)
   - If exists → returns `title_en` ✅
   - If not exists → returns candidate name `title_uk` (DB will handle error if column truly missing)

### Configuration Examples

**Strict mode (recommended for development):**
```php
'LingoActive' => [
    \FieldLingo\Adapters\Yii2\LingoActiveRecord::class => [
        'isStrict' => true,        // Throw exception on missing localized column
        'defaultLanguage' => 'en',
    ],
],
```

**Non-strict mode with fallback (production-friendly):**
```php
'LingoActive' => [
    \FieldLingo\Adapters\Yii2\LingoActiveRecord::class => [
        'isStrict' => false,       // Use fallback language
        'defaultLanguage' => 'en', // Fallback to English
    ],
],
```

### Practical Example

```php
// Database table has: id, title_en, title_uk (no title_ru)
// Config: isStrict = false, defaultLanguage = 'en'

// When Yii::$app->language = 'en'
$post->getAttribute('@@title');  // Returns title_en ✅

// When Yii::$app->language = 'uk'
$post->getAttribute('@@title');  // Returns title_uk ✅

// When Yii::$app->language = 'ru'
$post->getAttribute('@@title');  // Returns title_en (fallback) ✅

// --- With isStrict = true ---
// When Yii::$app->language = 'ru'
$post->getAttribute('@@title');  // Throws MissingLocalizedAttributeException 🚫
```

### Per-Model Fallback Configuration

You can override fallback behavior for specific models:

```php
'LingoActive' => [
    // Global strict mode
    \FieldLingo\Adapters\Yii2\LingoActiveRecord::class => [
        'isStrict' => true,
        'defaultLanguage' => 'en',
    ],

    // But allow fallback for Product model
    \app\models\Product::class => [
        'isStrict' => false,
        'defaultLanguage' => 'uk',  // Fallback to Ukrainian for products
    ],
],
```

> **Recommendation:**
> - Use `isStrict = true` during development to catch missing translations early
> - Use `isStrict = false` in production to gracefully handle missing translations with fallback

---

## ⚠️ Exception

`MissingLocalizedAttributeException` is thrown when `isStrict` is enabled and a localized attribute candidate does not exist (only thrown when attribute existence can be checked).

Make sure this exception is available in the adapter namespace or imported where the trait is used.

---

## 🧩 Advanced topics / hooks

 - **Custom language resolver**: If your app resolves the current language from a non-standard place (cookie, user preferences, model property), consider overriding the trait by providing a `protected function resolveLanguage(): string` or modify the trait to call a `resolveLanguage()` hook.
 - **Multiple prefixes**: Set `localizedPrefixes` to an array such as `['@@', '##']` to support multiple patterns.
 - **Per-model overrides**: Per-model keys in `LingoActive` allow you to change prefixes and strictness for specific models.

---

## 🔀 Migration Guide

Migrating an existing Yii2 project to Field-lingo is straightforward. Follow these steps:

### Step 1: Install the package

```bash
composer require alex-no/field-lingo
```

### Step 2: Prepare database schema

If you don't have localized columns yet, add them to your tables:

```sql
-- Example: Adding localized columns to existing 'post' table
ALTER TABLE post
    ADD COLUMN title_en VARCHAR(255) AFTER title,
    ADD COLUMN title_uk VARCHAR(255) AFTER title_en,
    ADD COLUMN content_en TEXT AFTER content,
    ADD COLUMN content_uk TEXT AFTER content;

-- Copy existing data to default language column (if needed)
UPDATE post SET title_en = title WHERE title_en IS NULL;
UPDATE post SET content_en = content WHERE content_en IS NULL;

-- Optional: Drop old non-localized columns after migration
-- ALTER TABLE post DROP COLUMN title, DROP COLUMN content;
```

### Step 3: Configure Field-lingo

Add configuration to `config/params.php` or `config/web.php`:

```php
// config/params.php
return [
    'LingoActive' => [
        \FieldLingo\Adapters\Yii2\LingoActiveRecord::class => [
            'localizedPrefixes' => '@@',
            'isStrict' => false,       // Use fallback during migration
            'defaultLanguage' => 'en',
        ],
        \FieldLingo\Adapters\Yii2\LingoActiveQuery::class => [
            'localizedPrefixes' => '@@',
        ],
    ],
    // ... other params
];
```

### Step 4: Update your models

**Before (standard ActiveRecord):**
```php
use yii\db\ActiveRecord;

class Post extends ActiveRecord
{
    public static function tableName()
    {
        return 'post';
    }
}
```

**After (LingoActiveRecord):**
```php
use FieldLingo\Adapters\Yii2\LingoActiveRecord;
use FieldLingo\Adapters\Yii2\LingoActiveQuery;

class Post extends LingoActiveRecord  // Changed parent class
{
    public static function tableName()
    {
        return 'post';
    }

    /**
     * Override find() to use LingoActiveQuery
     */
    public static function find()
    {
        return new LingoActiveQuery(get_called_class());
    }
}
```

### Step 5: Update controllers and views

**Before:**
```php
// Controller
$post = Post::findOne($id);
$post->title = 'New Title';
$post->save();

// View
echo $post->title;
```

**After:**
```php
// Controller
$post = Post::findOne($id);
$post->setAttribute('@@title', 'New Title');  // Sets title_en or title_uk
$post->save();

// View
echo $post->getAttribute('@@title');  // Gets title_en or title_uk
```

### Step 6: Update DataProviders

**Before:**
```php
use yii\data\ActiveDataProvider;

$dataProvider = new ActiveDataProvider([
    'query' => Post::find(),
]);
```

**After:**
```php
use FieldLingo\Adapters\Yii2\LingoActiveDataProvider;

$dataProvider = new LingoActiveDataProvider([
    'query' => Post::find(),
    'sort' => [
        'attributes' => ['id', '@@title', '@@category', 'created_at'],
    ],
]);
```

### Step 7: Update GridView columns

**Before:**
```php
echo GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        'title',
        'created_at:datetime',
    ],
]);
```

**After:**
```php
echo GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        [
            'attribute' => '@@title',
            'value' => function($model) {
                return $model->getAttribute('@@title');
            },
        ],
        'created_at:datetime',
    ],
]);
```

### Step 8: Test thoroughly

```php
// Test 1: Check attribute access
$post = Post::findOne(1);
var_dump($post->getAttribute('@@title'));

// Test 2: Check query conversion
$query = Post::find()->select(['@@title'])->where(['@@title' => 'Test']);
echo $query->createCommand()->getRawSql();

// Test 3: Check GridView sorting
// Click on column headers in GridView to test sorting

// Test 4: Test fallback (if using isStrict = false)
Yii::$app->language = 'ru';  // Language without columns
echo $post->getAttribute('@@title');  // Should return fallback language
```

### Migration Checklist

- [ ] Database schema updated with localized columns
- [ ] Existing data migrated to default language columns
- [ ] Configuration added to params
- [ ] Models extend `LingoActiveRecord`
- [ ] `find()` method overridden in models
- [ ] Controllers updated to use `getAttribute()`/`setAttribute()`
- [ ] Views updated to use `getAttribute()`
- [ ] DataProviders changed to `LingoActiveDataProvider`
- [ ] GridView columns updated
- [ ] Search models updated (if using)
- [ ] Tests updated
- [ ] All functionality tested in both languages

### Gradual Migration Strategy

You can migrate gradually by:

1. **Keep both old and new columns** during transition period
2. **Migrate model by model** instead of all at once
3. **Use per-model configuration** to customize behavior:

```php
'LingoActive' => [
    // Global defaults
    \FieldLingo\Adapters\Yii2\LingoActiveRecord::class => [
        'isStrict' => false,
        'defaultLanguage' => 'en',
    ],

    // Already migrated models (strict mode)
    \app\models\Post::class => [
        'isStrict' => true,
    ],

    // Still migrating (very permissive)
    \app\models\Category::class => [
        'isStrict' => false,
        'defaultLanguage' => 'uk',
    ],
],
```

---

## 🔧 Troubleshooting

### Problem: `@@field` notation is not working in queries

**Symptoms:** Queries like `Post::find()->where(['@@title' => 'Test'])` fail or `@@title` is treated as literal string.

**Solution:**
1. Make sure you've overridden the `find()` method in your model:
```php
public static function find()
{
    return new LingoActiveQuery(get_called_class());
}
```

2. Check that you're importing the correct class:
```php
use FieldLingo\Adapters\Yii2\LingoActiveQuery;
```

### Problem: `getAttribute('@@field')` returns null or wrong value

**Possible causes:**

1. **Configuration not loaded**
   - Check `Yii::$app->params['LingoActive']` is properly configured
   - Verify config file is being loaded

2. **Column doesn't exist in database**
   - If `isStrict = true`, you'll get `MissingLocalizedAttributeException`
   - If `isStrict = false`, library will try fallback language
   - Check database schema: `SHOW COLUMNS FROM your_table`

3. **Language format mismatch**
   - Current language: `Yii::$app->language` (e.g., `en-US`, `uk`)
   - Library uses first part: `en-US` → `en`
   - Make sure column names match: `title_en`, `title_uk`, etc.

### Problem: GridView sorting not working with localized fields

**Solution:**
1. Use `LingoActiveDataProvider` instead of `ActiveDataProvider`:
```php
use FieldLingo\Adapters\Yii2\LingoActiveDataProvider;

$dataProvider = new LingoActiveDataProvider([
    'query' => Post::find(),
]);
```

2. Configure sort attributes with `@@` notation:
```php
'sort' => [
    'attributes' => ['id', '@@title', '@@category'],
],
```

### Problem: How to check if Field-lingo is working correctly?

**Quick test:**

```php
// 1. Check current language
echo Yii::$app->language; // e.g., "uk" or "en-US"

// 2. Check config
print_r(Yii::$app->params['LingoActive']);

// 3. Test attribute resolution
$post = Post::findOne(1);
echo $post->getAttribute('@@title'); // Should return title_uk or title_en

// 4. Check what column was actually used
$query = Post::find()->select(['@@title']);
echo $query->createCommand()->getRawSql();
// Should show: SELECT `title_uk` FROM `post` or similar
```

### Problem: Exception "MissingLocalizedAttributeException"

**Cause:** `isStrict = true` and requested localized column doesn't exist in the table.

**Solutions:**

1. **Add missing column to database:**
```sql
ALTER TABLE post ADD COLUMN title_ru VARCHAR(255);
```

2. **Use fallback mode (non-strict):**
```php
'LingoActive' => [
    \FieldLingo\Adapters\Yii2\LingoActiveRecord::class => [
        'isStrict' => false,  // Enable fallback to defaultLanguage
        'defaultLanguage' => 'en',
    ],
],
```

3. **Add only columns you need:**
   - If you only support English and Ukrainian, only create `*_en` and `*_uk` columns
   - Set `defaultLanguage` to one you always have

### Problem: Getting "Unknown column" SQL error

**Cause:** Query uses `@@field` but it wasn't converted to actual column name.

**Check:**
1. Model extends `LingoActiveRecord`
2. Query uses `LingoActiveQuery` (via overridden `find()`)
3. DataProvider uses `LingoActiveDataProvider`
4. Column actually exists in database

### FAQ

**Q: Can I use multiple prefixes like `@@` and `##`?**

A: Yes! Configure as array:
```php
'localizedPrefixes' => ['@@', '##'],
```

**Q: Can I change the language dynamically during runtime?**

A: Yes, Field-lingo reads `Yii::$app->language` on each call:
```php
Yii::$app->language = 'en';
echo $post->getAttribute('@@title'); // Returns title_en

Yii::$app->language = 'uk';
echo $post->getAttribute('@@title'); // Returns title_uk
```

**Q: Does Field-lingo work with relations?**

A: Yes, as long as related models also extend `LingoActiveRecord`:
```php
$post = Post::find()->with('category')->one();
echo $post->category->getAttribute('@@name'); // Works!
```

**Q: Can I use this in forms and validation?**

A: Yes, but reference actual column names in rules:
```php
public function rules()
{
    return [
        [['title_en', 'title_uk'], 'required'],
        [['content_en', 'content_uk'], 'string'],
    ];
}
```

In forms, you can use `@@` notation for display:
```php
<?= $form->field($model, 'title_' . Yii::$app->language)->textInput() ?>
// Or use getAttribute/setAttribute in controller
```

---

## 🧱 Core design

`Core/Localizer.php` — centralized logic for mapping structured names to real column names.

`Core/Contracts/LocalizerInterface.php` — contract for Localizer implementations.

The core can be reused later for adapters (Laravel Eloquent, Doctrine, plain SQL builders).

---

## 📁 Directory Structure

```css
field-lingo/
├─ src/
│  ├─ Core/
│  │  ├─ Localizer.php
│  │  └─ Contracts/
│  │     ├─ LocalizerInterface.php
│  │     └─ ConfigInterface.php
│  └── Adapters/
│      ├─ Yii2/
│      │  ├─ LingoActiveRecord.php
│      │  ├─ LingoActiveQuery.php
│      │  ├─ LingoActiveDataProvider.php
│      │  ├─ LocalizedAttributeTrait.php
│      │  └─ MissingLocalizedAttributeException.php
│      ├─ Yii3/
│      │  ├─ LingoActiveRecord.php
│      │  ├─ LingoActiveQuery.php
│      │  ├─ LocalizedAttributeTrait.php
│      │  └─ MissingLocalizedAttributeException.php
│      ├─ Laravel/
│      │  ├─ LingoModel.php
│      │  ├─ LingoBuilder.php
│      │  ├─ LocalizedAttributeTrait.php
│      │  └─ MissingLocalizedAttributeException.php
│      └─ Symfony/
│         ├─ LingoEntity.php
│         ├─ LingoRepository.php
│         ├─ LingoQueryBuilder.php
│         ├─ LocalizedAttributeTrait.php
│         └─ MissingLocalizedAttributeException.php
├─ tests/
│  ├─ unit/
│  │  ├─ LocalizerTest.php
│  │  └─ TraitTest.php
│  └─ bootstrap.php
├─ examples/
│  ├─ Yii2/
│  │  ├─ sample-model.php
│  │  └─ sample-query.php
│  ├─ Yii3/
│  │  ├─ sample-model.php
│  │  ├─ sample-usage.php
│  │  └─ README.md
│  ├─ Laravel/
│  │  ├─ sample-model.php
│  │  └─ sample-usage.php
│  ├─ Symfony/
│  │  ├─ Product.php
│  │  ├─ ProductRepository.php
│  │  ├─ usage-example.php
│  │  └─ README.md
│  └─ plain-php/
│      └─ usage.php
├─ config/
│  ├─ field-lingo.php (Laravel config example)
│  └─ field-lingo-symfony.yaml (Symfony config example)
├─ .gitignore
├─ LICENSE
├─ README.md
└─ composer.json
```

## Examples

- **Yii2**: See [examples/Yii2/](examples/Yii2/) for ActiveRecord and ActiveQuery examples
- **Yii3**: See [examples/Yii3/](examples/Yii3/) for modern Yii3 ActiveRecord examples with Translator integration
- **Laravel**: See [examples/Laravel/](examples/Laravel/) for Eloquent model and query examples
- **Symfony**: See [examples/Symfony/](examples/Symfony/) for Doctrine entity and repository examples with detailed README

## 🧪 Testing

Unit tests in `tests/`. PHPUnit recommended. Example:

```bash
composer install --dev
./vendor/bin/phpunit --configuration phpunit.xml
```

 - Add unit tests that switch Yii::$app->language and assert correct conversions.
 - Test both strict and non-strict modes and per-model overrides.


## 🤝 Contribution

Contributions welcome! Suggested workflow:

 1. Fork repository.

 2. Create feature branch.

 3. Add tests.

 4. Open pull request.

Please follow PSR-12 and add PHPDoc (English) for public APIs.

## 🗺️ Roadmap

- ✅ Core mapping logic.
- ✅ Yii2 integration (ActiveRecord, ActiveQuery, DataProvider).
- ✅ Yii3 integration (ActiveRecord, ActiveQuery with Translator support).
- ✅ Laravel Eloquent adapter (Models, Query Builder).
- ✅ Symfony/Doctrine adapter (Entities, Repositories, QueryBuilder).
- 🧩 Advanced column patterns: nested access, JSON, relation-aware localization.
- 💡 Optionally store translation meta in separate table(s) as alternative mode.

## 📄 License

MIT. See `LICENSE`.

## 📬 Contact

*Field-lingo © 2025 Oleksandr Nosov. Released under the MIT License.

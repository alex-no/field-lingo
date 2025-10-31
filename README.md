# 🗂️ Field-lingo

[![Packagist Version](https://img.shields.io/packagist/v/alex-no/field-lingo.svg)](https://packagist.org/packages/alex-no/field-lingo)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Field-lingo** — lightweight library to easily work with database columns that store multiple language versions of the same attribute in one row (e.g. `name_en`, `name_uk`, `name_ru`).  
It provides a simple, consistent mechanism to reference "structured localized attribute names" (like `@@name`) and transparently map them to the actual column `name_<lang>` according to current language settings.

This repository currently contains a full integration for **Yii2** (ActiveRecord / ActiveQuery / DataProvider) under `src/Adapters/Yii2` and a framework-agnostic core in `src/Core` for future adapters.

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

## 🧩 Key classes

- `\AlexNo\FieldLingo\Adapters\Yii2\LingoActiveRecord`
   - Extends `yii\db\ActiveRecord`.
   ` Used when working with model attributes (reads/writes, forms, `toArray()`).

- `\AlexNo\FieldLingo\Adapters\Yii2\LingoActiveQuery`
   - Extends `yii\db\ActiveQuery`.
   ` Used to transform field names in conditions, `select()` lists, and custom textual SQL logic within the query layer.

- `\AlexNo\FieldLingo\Adapters\Yii2\LingoActiveDataProvider`
   - Extends `yii\data\ActiveDataProvider` (or `yii\db\ActiveDataProvider` depending on implementation).
   - Used for operations that require field translation in the data provider level (for example sorting, pagination where attribute names are passed externally).

These adapters rely on a shared trait `LocalizedAttributeTrait` which performs the core parsing and resolution logic.

---

## ⚙️ Quick start (Yii2)

### Install

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
Configure

Add to `params` (or any config area) the LingoActive section (example):

```php
'params' => [
    'LingoActive' => [
        // Global defaults for adapters
        \AlexNo\Fieldlingo\Adapters\Yii2\LingoActiveRecord::class => [
            'localizedPrefixes' => '@@',   // or ['@@', '##']
            'isStrict' => true,            // throw on missing localized attribute
            'defaultLanguage' => 'en',     // fallback language code
        ],
        \AlexNo\Fieldlingo\Adapters\Yii2\LingoActiveQuery::class => [
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

---

## 🧠 LocalizedAttributeTrait — behavior summary

The `LocalizedAttributeTrait` does the heavy lifting:
 - Normalizes `localizedPrefixes` to an array (supports a single prefix string or an array).
 - Reads runtime language from `Yii::$app->language` and uses its first part (e.g. en-US → en).
 - Produces a candidate attribute name `{base}_{lang}`.
 - If the using class implements `hasAttribute()` (as ActiveRecord does), the trait checks attribute existence:
    - If attribute exists — returns it.
    - If not and `isStrict === true` — throws `MissingLocalizedAttributeException`.
    ` If not and `isStrict === false` — tries fallback `{base}_{defaultLanguage}` and returns it if exists; otherwise returns the candidate.
 - If `hasAttribute()` is not available (e.g. at query layer), the trait returns the candidate name and lets the caller use it in SQL / selections.

You can call `$this->convertLocalizedFields([ ... ])` to map arrays of fields at once.

---

## 🚀 Usage examples
### ActiveRecord

When using `LingoActiveRecord`, you can reference localized attributes directly in code:
```php
use AlexNo\FieldLingo\Adapters\Yii2\LingoActiveRecord;


class Post extends LingoActiveRecord
{
// table columns: id, title_en, title_uk, content_en, content_uk
}


$post = Post::findOne(1);
$value = $post->getAttribute('@@title'); // resolves to title_en or title_uk


// array export converting fields:
$data = $post->toArray(['id', '@@title', '@@content']);
// result keys will include title_en / content_en (resolved names)
```

 > **Notes for ActiveRecord:**
 > - Because `hasAttribute()` is available, missing localized columns are validated according to `isStrict`.
 > - If you rely on `toArray()` or `fields()` to export language-aware data, ensure the adapter or model calls `convertLocalizedFields()` where appropriate.

### ActiveQuery

`LingoActiveQuery` resolves names used in `select()`, `andWhere()`, `orderBy()` and similar places.

```php
$rows = Post::find()
      ->select(['id', '@@title'])
      ->where(['@@title' => 'Hello'])
      ->all();
// FieldLingo will convert `@@title` to `title_en/title_uk` based on current language.      
```
> *Notes for ActiveQuery:*
 - Query layer cannot check `hasAttribute()` easily before SQL execution. The trait returns language-specific candidates and the DB will determine if the column exists. If you want stricter validation add a model-level check before building SQL (or enable `isStrict` and use ActiveRecord assertions in tests).

### ActiveDataProvider

`DataProvider` class is helpful when you expose sorting/filtering to external requests and need to map `@@` tokens to real DB columns.

```php
$dataProvider = new \AlexNo\FieldLingo\Adapters\Yii2\LingoActiveDataProvider([
'query' => Post::find(),
]);


// You may want to transform sort attributes before passing them to GridView
$sortAttributes = $dataProvider->getSort()->attributes;
// map keys with convertLocalizedFields(...) when necessary
```
> **Notes for DataProvider:**
 - Use the adapter-level conversion to normalize incoming `sort` or filter `fields` from the request.


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
│      └─ Yii2/
│         ├─ LingoActiveRecord.php
│         ├─ LingoActiveQuery.php
│         ├─ LingoActiveDataProvider.php
│         ├─ LocalizedAttributeTrait.php
│         └─ MissingLocalizedAttributeException.php
├─ tests/
│  ├─ unit/
│  │  ├─ LocalizerTest.php
│  │  └─ TraitTest.php
│  └─ bootstrap.php
├─ examples/
│  ├─ yii2/
│  │  ├─ sample-model.php
│  │  └─ sample-query.php
│  └─ plain-php/
│      └─ usage.php
├─ scripts/
│  └─ ci/
│      └─ run-tests.sh
├─ .gitignore
├─ LICENSE
├─ README.md
└─ composer.json
```

## Examples

See `examples/yii2/sample-model.php` and `examples/yii2/sample-query.php` for short, runnable examples.

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
- ⏳ Laravel Eloquent adapter.
- ⏳ Doctrine/QueryBuilder adapter.
- 🧩 Advanced column patterns: nested access, JSON, relation-aware localization.
- 💡 Optionally store translation meta in separate table(s) as alternative mode.

## 📄 License

MIT. See `LICENSE`.

## 📬 Contact

*Field-lingo © 2025 Oleksandr Nosov. Released under the MIT License.

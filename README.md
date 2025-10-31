# Field-lingo

[![Packagist Version](https://img.shields.io/packagist/v/alex-no/field-lingo.svg)](https://packagist.org/packages/alex-no/field-lingo)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**Field-lingo** — lightweight library to easily work with database columns that store multiple language versions of the same attribute in one row (e.g. `name_en`, `name_uk`, `name_ru`).  
It provides a simple, consistent mechanism to reference "structured localized attribute names" (like `@@name`) and transparently map them to the actual column `name_<lang>` according to current language settings.

This repository currently contains a full integration for **Yii2** (ActiveRecord / ActiveQuery / DataProvider) under `src/Adapters/Yii2` and a framework-agnostic core in `src/Core` for future adapters.

---

## Features

- Use a short, structured attribute name (default prefix `@@`) — e.g. `@@name`, `@@title`.
- Transparent mapping to `name_{lang}` using current application language.
- Strict / fallback mode: if localized column is missing, optional fallback to default language column.
- Works in:
  - Attribute access (`$model->@@name`) and property-style (`$model->name` via trait).
  - Query building: `select`, `where`, `orderBy`, `groupBy` using `@@` names.
  - DataProvider sorting integration.
- Extensible and framework-agnostic core for adding adapters (Laravel, Symfony, plain-PHP).

---

## Quick start (Yii2)

### Install

```bash
composer require alex-no/field-lingo
```

## Optional Recommendation

For automatic user language detection, it is recommended to install:

```bash
composer require alex-no/language-detector
```

> **Note:** This package requires its own separate configuration.

## Basic idea

In DB table we keep language-specific columns:

```bash
id | name_en | name_uk | name_ru | created_at
```

In code we refer to `@@name`. FieldLingo maps `@@name → name_{lang}` (e.g. `name_uk`) depending on `Yii::$app->language`.

## Configure
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

> You can override per-model by adding the model class key in the LingoActive section.

## Usage in models

Make your AR models extend the provided LingoActiveRecord:

```php
namespace app\models;

use AlexNo\FieldLingo\Adapters\Yii2\LingoActiveRecord;

class PetType extends LingoActiveRecord
{
    public static function tableName()
    {
        return 'pet_type';
    }
}
```

Then:

```php
$model = PetType::findOne(1);

// attribute access
echo $model->{"@@name"}; // returns name_en / name_uk / name_ru depending on Yii::$app->language
```

## Usage in queries

```php
$query = PetType::find()
    ->select(['id', '@@name'])
    ->where(['@@name' => 'Cat'])
    ->orderBy('@@name ASC');
```

FieldLingo will convert `@@name` to `name_en/name_uk` based on current language.

## Core design

`Core/Localizer.php` — centralized logic for mapping structured names to real column names.

`Core/Contracts/LocalizerInterface.php` — contract for Localizer implementations.

`Adapters/Yii2/LingoActiveRecord.php` — extends `yii\db\ActiveRecord`, uses trait to handle attribute access.

`Adapters/Yii2/LingoActiveQuery.php` — extends `yii\db\ActiveQuery` and rewrites `select`, `where`, `orderBy`, `groupBy`, and other helpers to localize columns/conditions.

`Adapters/Yii2/LingoActiveDataProvider.php` — adjusts sort attributes and default order.

The core can be reused later for adapters (Laravel Eloquent, Doctrine, plain SQL builders).

## Configuration

Main options:

`localizedPrefixes` (string|array) — prefix(es) used to mark structured names. Default: `@@`.

`defaultLanguage` (string) — fallback language when localized column is missing. Default: `en`.

`isStrict (bool)` — if true throw when localized column missing; if `false` fallback to `defaultLanguage`.

These options may be set globally, per-class (LingoActiveRecord / LingoActiveQuery) or per-model.

## 🧱 Directory Structure

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

## Testing

Unit tests in `tests/`. PHPUnit recommended. Example:

```bash
composer install --dev
./vendor/bin/phpunit --configuration phpunit.xml
```

## Contribution

Contributions welcome! Suggested workflow:

 1. Fork repository.

 2. Create feature branch.

 3. Add tests.

 4. Open pull request.

Please follow PSR-12 and add PHPDoc (English) for public APIs.

## Roadmap

- [x] Core mapping logic.
- [x] Yii2 integration (ActiveRecord, ActiveQuery, DataProvider).
- [ ] Laravel Eloquent adapter.
- [ ] Doctrine/QueryBuilder adapter.
- [ ] Advanced column patterns: nested access, JSON, relation-aware localization.
- [ ] Optionally store translation meta in separate table(s) as alternative mode.

## License

MIT. See `LICENSE`.

## Contact

*Field-lingo © 2025 Oleksandr Nosov. Released under the MIT License.*

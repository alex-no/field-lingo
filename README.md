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

In code we refer to <span style="background-color: #f0f0f0;">@@name</span>. Fieldlingo maps <span style="background-color: #f0f0f0;">@@name → name_{lang}</span> (e.g. <span style="background-color: #f0f0f0;">name_uk</span>) depending on <span style="background-color: #f0f0f0;">Yii::$app->language</span>.

## Configure
Configure

Add to <span style="background-color: #f0f0f0;">params</span> (or any config area) the advActive section (example):

```php
'params' => [
    'advActive' => [
        \AlexNo\Fieldlingo\Adapters\Yii2\AdvActiveRecord::class => [
            'localizedPrefixes' => '@@',
            'isStrict' => true,
            'defaultLanguage' => 'en',
        ],
        \AlexNo\Fieldlingo\Adapters\Yii2\AdvActiveQuery::class => [
            'localizedPrefixes' => '@@',
        ],
    ],
],
```

> You can override per-model by adding the model class key in the advActive section.

## Usage in models

Make your AR models extend the provided AdvActiveRecord:


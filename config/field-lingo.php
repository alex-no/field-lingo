<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for all models using FieldLingo.
    | These can be overridden on a per-model basis.
    |
    */
    'default' => [
        /**
         * Prefix or prefixes used to mark structured localized names.
         * Example: '@@' or ['@@', '##']
         */
        'localizedPrefixes' => '@@',

        /**
         * If true - throw exception when localized column not found.
         * If false - try fallback to defaultLanguage.
         */
        'isStrict' => false,

        /**
         * Default fallback language (two-letter code).
         * Used when requested language column doesn't exist.
         */
        'defaultLanguage' => 'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | Per-Model Configuration
    |--------------------------------------------------------------------------
    |
    | You can override settings for specific models by adding their class name
    | as a key. Example:
    |
    | \App\Models\Post::class => [
    |     'localizedPrefixes' => '##',
    |     'isStrict' => true,
    |     'defaultLanguage' => 'uk',
    | ],
    |
    */

    // Example per-model configuration:
    // \App\Models\Post::class => [
    //     'isStrict' => true,
    //     'defaultLanguage' => 'uk',
    // ],
];

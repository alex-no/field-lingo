<?php

/**
 * Example usage of FieldLingo with Laravel Eloquent
 *
 * This file demonstrates various ways to use FieldLingo for handling
 * localized database columns in Laravel applications.
 */

use App\Models\Post;
use Illuminate\Support\Facades\App;

// ============================================================================
// CONFIGURATION
// ============================================================================

/*
In your Laravel application, publish the config file:

php artisan vendor:publish --tag=field-lingo-config

Then edit config/field-lingo.php:

return [
    'default' => [
        'localizedPrefixes' => '@@',
        'isStrict' => false,
        'defaultLanguage' => 'en',
    ],
];
*/

// ============================================================================
// BASIC USAGE: Reading localized attributes
// ============================================================================

// Set current language
App::setLocale('uk');

$post = Post::find(1);

// Using getAttribute with @@ notation
$title = $post->getAttribute('@@title');    // Returns title_uk
$content = $post->getAttribute('@@content'); // Returns content_uk

// Using property access with @@ notation
echo $post->{'@@title'};    // Same as above
echo $post->{'@@content'};  // Same as above

// ============================================================================
// CREATING AND UPDATING RECORDS
// ============================================================================

// Create new post with localized attributes
$post = new Post();
$post->setAttribute('@@title', 'Новина дня');     // Sets title_uk
$post->setAttribute('@@content', 'Текст новини'); // Sets content_uk
$post->save();

// Or using property access
$post = new Post();
$post->{'@@title'} = 'Новина дня';
$post->{'@@content'} = 'Текст новини';
$post->save();

// Mass assignment (use actual column names)
$post = Post::create([
    'title_uk' => 'Новина дня',
    'content_uk' => 'Текст новини',
]);

// ============================================================================
// QUERY BUILDING: Using @@ notation in queries
// ============================================================================

// Simple select
App::setLocale('en');
$posts = Post::select(['id', '@@title', '@@content'])
    ->get(); // SELECT id, title_en, content_en FROM posts

// Where conditions
$posts = Post::where('@@title', 'Hello World')->get();
// WHERE title_en = 'Hello World'

$posts = Post::where('@@title', 'like', '%News%')->get();
// WHERE title_en LIKE '%News%'

// Order by localized field
$posts = Post::orderBy('@@title', 'asc')->get();
// ORDER BY title_en ASC

// Group by localized field
$stats = Post::select('@@category', \DB::raw('COUNT(*) as count'))
    ->groupBy('@@category')
    ->get();
// GROUP BY category_en

// Complex query example
$posts = Post::select(['id', '@@title', '@@content'])
    ->where('@@title', 'like', '%News%')
    ->where('created_at', '>', '2024-01-01')
    ->orderBy('@@title', 'desc')
    ->limit(10)
    ->get();

// ============================================================================
// LANGUAGE SWITCHING
// ============================================================================

// English
App::setLocale('en');
$post = Post::find(1);
echo $post->{'@@title'}; // Returns title_en

// Ukrainian
App::setLocale('uk');
echo $post->{'@@title'}; // Returns title_uk

// Russian
App::setLocale('ru');
echo $post->{'@@title'}; // Returns title_ru

// ============================================================================
// FALLBACK MECHANISM (when isStrict = false)
// ============================================================================

/*
Database has: title_en, title_uk (no title_ru)
Config: isStrict = false, defaultLanguage = 'en'
*/

App::setLocale('en');
echo $post->{'@@title'}; // Returns title_en ✅

App::setLocale('uk');
echo $post->{'@@title'}; // Returns title_uk ✅

App::setLocale('ru');
echo $post->{'@@title'}; // Returns title_en (fallback) ✅

// ============================================================================
// STRICT MODE (when isStrict = true)
// ============================================================================

/*
If isStrict = true and column doesn't exist, throws exception:
FieldLingo\Adapters\Laravel\MissingLocalizedAttributeException
*/

// ============================================================================
// PER-MODEL CONFIGURATION
// ============================================================================

/*
You can override settings per model in config/field-lingo.php:

return [
    'default' => [
        'isStrict' => false,
        'defaultLanguage' => 'en',
    ],

    \App\Models\Post::class => [
        'isStrict' => true,
        'defaultLanguage' => 'uk',
    ],
];
*/

// ============================================================================
// USING TRAIT DIRECTLY (instead of extending LingoModel)
// ============================================================================

/*
If you can't extend LingoModel, use the trait directly:

use Illuminate\Database\Eloquent\Model;
use FieldLingo\Adapters\Laravel\LocalizedAttributeTrait;

class Post extends Model
{
    use LocalizedAttributeTrait;

    // You must also override newEloquentBuilder to use LingoBuilder
    public function newEloquentBuilder($query)
    {
        return new \FieldLingo\Adapters\Laravel\LingoBuilder($query);
    }
}
*/

// ============================================================================
// PAGINATION
// ============================================================================

$posts = Post::where('@@title', 'like', '%News%')
    ->orderBy('@@title')
    ->paginate(15);

foreach ($posts as $post) {
    echo $post->{'@@title'};
}

// ============================================================================
// EAGER LOADING WITH RELATIONS
// ============================================================================

/*
If you have relations with localized attributes:

$posts = Post::with('category')->get();

foreach ($posts as $post) {
    echo $post->{'@@title'};
    echo $post->category->{'@@name'}; // If Category also uses LingoModel
}
*/

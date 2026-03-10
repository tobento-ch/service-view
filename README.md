# View Service

The View Service provides a simple yet flexible way to create and render views.  
It brings together rendering, shared data, and asset management into a unified and extensible system, making it easy to build structured and maintainable templates.

## Table of Contents

- [Getting started](#getting-started)
	- [Requirements](#requirements)
	- [Highlights](#highlights)
	- [Simple Example](#simple-example)
- [Documentation](#documentation)
	- [Data](#data)
	- [Assets](#assets)
        - [Assets Handler](#assets-handler)
    - [Renderer](#renderer)
        - [PHP Renderer](#php-renderer)
        - [Chain Renderer](#chain-renderer)
    - [View](#view)
        - [Template](#template)
        - [Macros](#macros)
        - [Tags Attributes Macro](#tags-attributes-macro)
- [Credits](#credits)
___

# Getting started

Add the latest version of the view service project running this command.

```
composer require tobento/service-view
```

## Requirements

- PHP 8.4 or greater

## Highlights

- Framework-agnostic, will work with any project
- Decoupled design
- Easy to extend

## Simple Example

Here is a simple example of how to use the View service.  
We will assume the following directory structure:

```
private/
    views/
        home.php
        about.php
        inc/
            header.php
            footer.php
public/
    src/
        app.css
        js/
            app.js
```

### Create and render a view

```php
use Tobento\Service\View\View;
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\View\Data;
use Tobento\Service\View\Assets;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\Dir\Dir;

$view = new View(
    new PhpRenderer(
        new Dirs(
            new Dir('home/private/views/'),
        )
    ),
    new Data(),
    new Assets('home/public/src/', 'https://www.example.com/src/')
);

echo $view->render('about', ['title' => 'About', 'description' => 'Lorem ipsum']);
```

### The view template

```php
<!DOCTYPE html>
<html>
    <head>
        <title><?= $view->esc($title) ?></title>

        <?= $view->assets()->render() ?>

        <?php
        // Assets can be included in any subview as well.
        $view->asset('app.css');
        $view->asset('js/app.js')->attr('async');
        ?>
    </head>
    <body>
        <?= $view->render('inc/header') ?>

        <h1><?= $view->esc($title) ?></h1>

        <p><?= $view->esc($description) ?></p>

        <?= $view->render('inc/footer') ?>
    </body>
</html>
```

# Documentation

## Data

The `Data` class provides a flexible way to manage variables passed to your views.  
You can set global data available to all views, or target specific views individually.  
Data can be added, renamed, retrieved, or merged, giving you full control over what each view receives.

```php
use Tobento\Service\View\Data;

$data = new Data();

// Set or add data.
$data->set('key', 'value');
$data->add(['key' => 'value']);

// Set or add data for specific view(s) only.
$data->set('key', 'value', 'viewName');
$data->set('key', 'value', ['viewName', 'anotherViewName']);
$data->add(['key' => 'value'], 'viewName');
$data->add(['key' => 'value'], ['viewName', 'anotherViewName']);

// Get data by key.
$key = $data->get('key');
$key = $data->get('key', 'defaultValue');

// Get all data.
$allData = $data->all();

// Get all data for a specific view.
$allData = $data->all('viewName');

// Rename data keys, add data and get them all.
$allData = $data->rename(['key' => 'newKey'])->add(['bar' => 'foo'])->all();
```

## Assets

The `Assets` class provides a convenient way to register, organize, and render assets such as CSS, JavaScript, or any other file-based resources your application needs.  
It acts as a lightweight collection where each asset can define its own file path, directory, URI, attributes, group, and render order.

You can also attach an `AssetsHandlerInterface` implementation to enable advanced features like minification, combining, or rewriting imports.  
By default, assets are only collected and rendered as placeholders until you flush them.


```php
use Tobento\Service\View\Assets;
use Tobento\Service\View\Asset;
use Tobento\Service\View\AssetsHandlerInterface;

$assets = new Assets(
    assetDir: 'home/public/src/',
    assetUri: 'https://www.example.com/src/'
);

// Assets handler might be used for minifying and/or combining scripts.
// $assets->setAssetsHandler(AssetsHandlerInterface $assetsHandler);

// Adding Assets
$asset = new Asset(
    file: 'inc/styles.css',
    dir: '',
    uri: '',
    attributes: [
        'data-foo' => '1',
    ],
    order: 7,
    group: 'default'
);
$assets->add($asset);

// Creates and adds an Asset::class with default directory and uri set on Assets::class
$assets->asset(file: 'inc/app.js');

$assets->asset(file: 'inc/app.js')
       ->dir('') // clear directory if needed
       ->uri('') // clear uri if needed
       ->group('footer')
       ->order(10)
       ->attr('data-foo', 'value')
       ->attr('async');

// Get all assets.
$allAssets = $assets->all();

// Render the assets. This will only render placeholders.
var_dump($assets->render());
// string(25) "<!-- assets="default" -->"

var_dump($assets->render(group: 'footer'));
// string(24) "<!-- assets="footer" -->"

// Render the assets.
var_dump($assets->flushing($assets->render(group: 'footer')));
// string(57) "<script src="inc/app.js" data-foo="value" async></script>"

// You might clear all assets.
$assets->clear();
```

### Assets Handler

The **AssetsHandler** provides a flexible way to process and build assets without modifying any view files.  
It supports:

- automatic minification (CSS/JS) using the [Minify Service](https://github.com/tobento-ch/service-minify)
- versioning
- recursive JS import resolution
- circular-import protection
- writing processed assets into a build directory
- replacing asset references

This enables a streamlined build process and ensures that optimized, versioned assets are used consistently throughout your views.

**Requirements**

The `AssetsHandler` requires the [Minify Service](https://github.com/tobento-ch/service-minify):

```
composer require tobento/service-minify
```

**Why this is useful**

- Asset filenames can change (e.g., through versioning) without requiring updates to views
- Versioning provides reliable cache-busting, ensuring browsers always load the latest assets
- JS imports are resolved recursively, simplifying script organization
- Themes or applications can override assets cleanly
- Production builds are automatically optimized
- The build directory remains consistent and predictable

**Notes**

- This system is **not a bundler** (like Vite, Rollup, or Webpack).
- It performs **minimal ES module resolution** for relative `.js` files only.
- Non-JS imports are ignored by design.
- Versioning can be disabled via: ` ->versioning(false)`

#### Example

All methods return a **new instance**, allowing fluent configuration:

```php
use Tobento\Service\Dir\Dir;
use Tobento\Service\Minify\Factory;
use Tobento\Service\Minify\MinifierFactoryInterface;
use Tobento\Service\Minify\MinifierInterface;
use Tobento\Service\View\AssetsHandler;

$assetsHandler = new AssetsHandler(
    // Directory where processed assets are stored
    storeDir: new Dir('public/assets/build/'),
    
    // Public URI for generated assets
    assetUri: 'https://example.com/assets/build/',
    
    // Glob patterns always excluded from processing (default)
    skipAlways: ['*.min.css', '*.min.js'],
    
    // Optional logger
    logger: null,
)
    // Disable versioning if needed.
    // Not recommended, as you lose cache-busting.
    ->versioning(false)

    // Define custom minifiers (or disable them entirely).
    // Accepts: null | MinifierFactoryInterface | MinifierInterface
    ->withMinifiers(
        css: new Factory\CssMinifierFactory()->except('comments'),
        js: new Factory\JavaScriptMinifierFactory(),

        // Or disable minifying completely:
        // js: null,
        // css: null,
    )

    // Skip minifying specific files.
    // Overrides any previously set skip patterns.
    ->skipMinify(
        // Individual files:
        'css/foo.css',
        'bar.js',

        // Useful patterns:
        'css/*.legacy.css', // skip all legacy CSS
        'js/vendor/*.js',   // skip vendor JS
        '*/debug/*',        // skip any debug folder
        '*.bundle.js',      // skip pre-bundled JS
    )

    // Replace files. You may add multiple replacements.
    ->replace(
        file: 'css/foo.css',
        with: 'css/bar.css',
    )

    // Combine multiple files into one. You may add multiple combinations.
    ->combine(
        filename: 'app.css',
        files: [
            'css/basis.css',
            'css/app.css',
        ]
    );

// You may clear all previously built assets
$assetsHandler->clear();

// Assign the asset handler to the Assets instance
$assets->setAssetsHandler($assetsHandler);
```

> **Note**  
> Imported JS files are only re-processed when the *root* asset is defined in the view.  
> For example:
>
> ```php
> $view->asset('assets/file.js')->attr('type', 'module');
> ```
>
> If the root file is not referenced in the view, changes inside imported files will not trigger an update in the build output unless those imported files are also defined as separate assets.

#### JavaScript import handling

The asset handler supports rewriting **relative JavaScript imports** for cache-busting.  
Only `.js` files are processed. CSS and other asset types are **not** rewritten.

#### Supported JavaScript import patterns

The following ES module import schemes are recognized and rewritten with versioning:

| Pattern type                     | Example                                   | Supported |
|----------------------------------|-------------------------------------------|-----------|
| Same-directory relative import   | `import a from './foo.js'`               | yes         |
| Parent-directory import          | `import b from '../bar.js'`              | yes         |
| Complex relative paths           | `import c from './../baz.js'`            | yes         |
| Deep relative paths              | `import d from '../../utils/qux.js'`     | yes         |
| Normalized paths (`./`, `../`)   | `import e from './nested/../weird/path.js'` | yes      |
| Circular imports                 | `a.js → b.js → a.js`                     | yes         |
| Duplicate imports                | `import x from './foo.js'` twice         | yes (deduped) |

All resolved imports are:

- **recursively processed**
- **minified**
- **written to the build directory**
- **rewritten with the same version query** (for example: `?v=123456`)

#### Unsupported import patterns

The handler intentionally **does not** process or rewrite the following:

| Pattern type          | Example                               | Reason                                      |
|-----------------------|---------------------------------------|---------------------------------------------|
| CSS imports           | `import './styles.css'`              | CSS is not rewritten or versioned           |
| JSON imports          | `import data from './data.json'`     | Not part of the JS pipeline                 |
| Bare module imports   | `import React from 'react'`          | Cannot resolve without `node_modules`       |
| Dynamic imports       | `import('./foo.js')`                 | Not matched by the static import detection  |
| Extensionless imports | `import x from './foo'`              | Resolver requires a `.js` extension         |
| Directory imports     | `import utils from './utils'`        | No `index.js` resolution is performed       |

These imports remain unchanged in the output.

#### JavaScript combining

JavaScript combining is supported, but it is **not equivalent to bundling**.  
When multiple JS files are combined, the handler simply concatenates their contents in the order provided.

Because of this:

- JavaScript files are **not automatically reordered**
- `import` statements are **not moved to the top**
- no module graph or dependency ordering is performed

Combining JavaScript is therefore only suitable when the files are already structured in a way that allows safe concatenation.

**Recommendation:**  
Use JavaScript combining only for files that do not rely on ES module ordering, or when using a modifier that explicitly supports import rewriting. Without such a modifier, JS files are processed individually, and combining is primarily intended for CSS assets.

## Renderer

### PHP Renderer

The PHP renderer uses native PHP as its templating language, so there is no new syntax to learn.  
It also integrates with the [Dir Service](https://github.com/tobento-ch/service-dir), which provides a simple and flexible way to organize and prioritize your template directories.

```php
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\View\ViewNotFoundException;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\Dir\Dir;

$dirs = new Dirs(
    new Dir(dir: 'home/private/views/', priority: 5),
    new Dir(dir: 'home/private/theme/views/', priority: 10),
);

$renderer = new PhpRenderer($dirs->sort());

// Render a view.
try {
    echo $renderer->render('view', ['title' => 'Title']);
} catch (ViewNotFoundException $e) {
    //
}

// Check if a view exists.
if ($renderer->exists('view')) {
    //
}
```

### Chain Renderer

The chain renderer allows you to combine multiple renderers into a single rendering pipeline.  
If the first renderer cannot handle a view, the next renderer in the chain is tried, and so on.  
This makes it easy to mix different rendering engines (e.g., PHP, Twig) in the same application.

```php
use Tobento\Service\View\ChainRenderer;
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\View\ViewNotFoundException;

$renderer = new ChainRenderer(
    // new TwigRenderer(),
    new PhpRenderer(new Dirs()),
);

// Render a view.
try {
    echo $renderer->render('view');
} catch (ViewNotFoundException $e) {
    //
}

// Check if a view exists.
if ($renderer->exists('view')) {
    //
}
```

## View

The `View` class ties everything together: a renderer, shared data, and asset management.  
It provides a simple API for rendering templates, passing data, registering view aliases, and hooking into the rendering process with event callbacks.

```php
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\View\View;
use Tobento\Service\View\Data;
use Tobento\Service\View\Assets;

$renderer = new PhpRenderer(new Dirs());

$assets = new Assets(
    assetDir: 'home/public/src/',
    assetUri: 'https://www.example.com/src/'
);

$view = new View($renderer, new Data(), $assets);

// Adding data
$view->data(['key' => 'value']);
$view->with(name: 'title', value: 'Title');

// Get data.
$view->get('key');
$view->get('key', 'defaultValue');
$view->data()->get('key');

// Render a view.
echo $view->render(view: 'inc/header', data: ['key' => 'value']);

// Add a view by key.
$view->add(key: 'inc.view', view: 'inc/view');

// Render the view added by key.
echo $view->render(view: 'inc.view', data: ['key' => 'value']);

// On render a specific view.
$view->on('inc.view', function(array $data, ViewInterface $view): array {
    $data['key'] = 'value';
    return $data;
});

$view->on('comments.writing', function(array $data, ViewInterface $view): array {
    $view->add(key: 'comments.writing', view: 'comments/writing');
    $data['text'] = 'Lorem ipsum';
    return $data;
});

// On render any view using wildcard.
$view->on('*', function(array $data, ViewInterface $view, string $key): array {
    $data['key'] = 'value';
    return $data;
});

// Get the assets
$assets = $view->assets();

// Add an asset
$view->asset('app.css');
```

### Template

A template is simply a PHP file rendered by the view system.  
You can access the `$view` instance inside any template, allowing you to escape values, include subviews, register assets, or conditionally render content.

```php
<!DOCTYPE html>
<html>
    <head>
        <title><?= $view->esc($title) ?></title>

        <?= $view->assets()->render() ?>

        <?php
        // assets can be included in every subview too.
        $view->asset('app.css');
        $view->asset('js/app.js')->attr('async');
        ?>
    </head>
    <body>
        <?= $view->render('inc/header') ?>

        <h1><?= $view->esc($title) ?></h1>

        <p><?= $view->esc($description) ?></p>
        
        <?php if ($view->exists('inc/view')) { // or with view key 'inc.view' ?>
            <?= $view->render('inc/view') ?>
        <?php } else { ?>
            <p>Fallback</p>
        <?php } ?>     
    </body>
</html>
```

```php
// Rendering once only.
<?php if ($view->once(__FILE__)) { ?>
    <p>Lorem ipsum</p>
<?php } ?>      
```

### Macros

Macros allow you to extend the `View` class with your own custom helper methods.  
This makes it easy to add reusable functionality directly on the view instance without creating subclasses.

```php
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\View\View;
use Tobento\Service\View\Data;
use Tobento\Service\View\Assets;

$renderer = new PhpRenderer(new Dirs());

$assets = new Assets(
    assetDir: 'home/public/src/',
    assetUri: 'https://www.example.com/src/'
);

$view = new View($renderer, new Data(), $assets);

$view->macro('strtoupper', function($value) {
    return strtoupper($value);
});

var_dump($view->strtoupper('lorem ipsum'));
// string(11) "LOREM IPSUM"
```

### Tags Attributes Macro

The Tags Attributes macro provides a convenient way to collect and manage HTML attributes for specific tags.  
By registering the macro on the view instance, you can easily retrieve an `AttributesInterface` object and work with attributes in a structured way.

```php
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\View\View;
use Tobento\Service\View\Data;
use Tobento\Service\View\Assets;
use Tobento\Service\View\TagsAttributes;
use Tobento\Service\Tag\AttributesInterface;

$renderer = new PhpRenderer(new Dirs());

$assets = new Assets(
    assetDir: 'home/public/src/',
    assetUri: 'https://www.example.com/src/'
);

$view = new View($renderer, new Data(), $assets);

$view->macro('attr', [new TagsAttributes(), 'get']);

var_dump($view->attr('body') instanceof AttributesInterface);
// bool(true)
```

Check out the [Tag Service - Attributes Interface](https://github.com/tobento-ch/service-tag#attributes-interface) to learn more about how attributes work in general.

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)
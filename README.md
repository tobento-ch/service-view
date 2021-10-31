# View Service

The View Service is for creating and rendering views in a simple, yet flexible way.

## Table of Contents

- [Getting started](#getting-started)
	- [Requirements](#requirements)
	- [Highlights](#highlights)
	- [Simple Example](#simple-example)
- [Documentation](#documentation)
	- [Data](#data)
	- [Assets](#assets)
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

- PHP 8.0 or greater

## Highlights

- Framework-agnostic, will work with any project
- Decoupled design
- Easy to extend

## Simple Example

Here is a simple example of how to use the View. We will assume the following directory stucture:

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
        // assets can be included in every subview too.
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
```

## Renderer

### PHP Renderer

The PHP renderer uses native PHP language for views, no need to learn new syntax.\
Furthermore, it uses the [Dir Service](https://github.com/tobento-ch/service-dir) which provides a simple way to organize your template files.

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

This renderer allows combining any number of other renderer. If the first renderer cannot handle rendering, the next renderer will try and so on.

```php
use Tobento\Service\View\ChainRenderer;
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\View\Dirs;
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

```php
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\View\Dirs;
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

// Get the assets
$assets = $view->assets();

// Add an asset
$view->asset('app.css');
```

### Template

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

With macros you can easily extend the view class with any function you need.

```php
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\View\Dirs;
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

Tags Attributes might be useful to collect attributes for a specific tag.

```php
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\View\Dirs;
use Tobento\Service\View\View;
use Tobento\Service\View\Data;
use Tobento\Service\View\Assets;
use Tobento\Service\View\TagsAttributes;

$renderer = new PhpRenderer(new Dirs());

$assets = new Assets(
    assetDir: 'home/public/src/',
    assetUri: 'https://www.example.com/src/'
);

$view = new View($renderer, new Data(), $assets);

$view->macro('attr', [new TagsAttributes(), 'get']);

$view->attr('body')->add('class', 'foo');

$view->attr('body')->set('class', 'foo');

$view->attr('body')->merge(['data-foo' => 'bar']);

var_dump($view->attr('body')->has('class'));
// bool(true)

var_dump($view->attr('body')->empty());
// bool(false)

$bodyAttributes = $view->attr('body')->all();
var_dump($bodyAttributes);
// array(2) { ["class"]=> string(3) "foo" ["data-foo"]=> string(3) "bar" }

var_dump($view->attr('body')->render());
// string(26) "class="foo" data-foo="bar""
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)
# Sphinx for Laravel Scout

Sphinx Search Engine for Laravel Scout.
Forked from https://github.com/hocnt84/laravel-scout-sphinx
**Note:** Pagination did not work with the original repo. Maybe it's due a [Query Builder](https://github.com/FoolCode/SphinxQL-Query-Builder) issue. This is the only reaso we've created this fork.

## Prerequisites

You should have Sphinx service installed, see: http://sphinxsearch.com/

## Install

### Installing via composer

Use `composer require` to install the Engine.

```
$ composer require egwk/laravel-scout-sphinx
```

### Configuration

Update `config/scout.php`, and add an entry for `sphinx`:

```php
//
'sphinx' => [
        'host' => env('SCOUT_HOST', 'localhost'),
        'port' => env('SCOUT_PORT', '9306'),
],
//
```

Set `SCOUT_*` variables in your `.env` file:

```
SCOUT_DRIVER=sphinxsearch
SCOUT_PREFIX=myprefix_
SCOUT_HOST=localhost
SCOUT_PORT=9306
```

### Adding to your project

Update `config/app.php` by adding an entry for the service provider:

```php
'providers' => [
    // ...
   Egwk\LaravelScoutSphinx\Provider\SphinxEngineProvider::class,
];
```


See [Laravel Scout Docs](https://laravel.com/docs/5.5/scout) for further info.

## Authors
* The original Engine written by [@hocnt84](https://github.com/hocnt84/laravel-scout-sphinx)
* Modified by [@buchin](https://github.com/buchin/laravel-scout-sphinx)
* Merged by [@perdodi](https://github.com/perdodi) / [White Könyvtár](https://white-konyvtar.hu)

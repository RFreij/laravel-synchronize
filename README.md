# Laravel Synchronize

[![Build Status](https://travis-ci.com/RFreij/laravel-synchronize.svg?branch=master)](https://travis-ci.com/RFreij/laravel-synchronize)
[![Downloads](https://img.shields.io/packagist/dt/netcreaties/laravel-synchronize.svg)](https://packagist.org/packages/netcreaties/laravel-synchronize)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/RFreij/laravel-synchronize/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/RFreij/laravel-synchronize/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/RFreij/laravel-synchronize/?branch=master)

This package gives you the ability to create synchronization files and prevent you from having to write one time use commands when you've got for example: A database structure change that will require you to synchronize the old structure data with the new structure.

## Documentation

-   [Laravel Synchronize](#laravel-synchronize)
    -   [Documentation](#documentation)
    -   [Installation](#installation)
        -   [Laravel 5.5+](#laravel-55)
        -   [Execute migrations](#execute-migrations)
    -   [Getting started](#getting-started)
        -   [Publish config](#publish-config)
        -   [Publish migration](#publish-migration)
        -   [Execute migrations](#execute-migrations-1)
    -   [Usage](#usage)
        -   [Make command](#make-command)
        -   [Synchronize command](#synchronize-command)

<a name="installation"></a>

## Installation

The best way to install this package is through your terminal via Composer.

Run the following command from your projects root

```shell
composer require netcreaties/laravel-synchronize
```

#### Laravel 5.5+

This package supports package discovery.

#### Execute migrations

---

<a name="getting-started"></a>

## Getting started

#### Publish config (optional)

Publishing the config will enable you to overwrite some of the settings this package uses. For example you can define where synchronization files should be stored.

```shell
php artisan vendor:publish --provider="LaravelSynchronize\Providers\ServiceProvider" --tag="config"
```

#### Publish migration

```shell
php artisan vendor:publish --provider="LaravelSynchronize\Providers\ServiceProvider" --tag="migrations"
```

#### Execute migrations

```shell
php artisan migrate
```

<a name="usage"></a>

## Usage

#### Make command

```shell
php artisan make:synchronization {name}
```

Creates the synchronization file at `database/synchronizations`

#### Synchronize command

```shell
php artisan laravel-sync:synchronize
```

#### Using --class and --force

It can happen you need a synchronization before you can perform a migration. Using --class and --force can help you achieving that goal.

All you need to do is using the Laravel 5.8.16+ Migration events, or simply call them in the up or down method when lower.

Example:

```Php
    public function __construct()
    {
        Event::listen(MigrationStarted::class, function (MigrationStarted $listener) {
            if ($listener->migration instanceof $this && $listener->method === 'up') {
                Artisan::call('laravel-sync:synchronize --class=TestASync --force');
                echo Artisan::output();
            }
        });
    }
```

#### --force

--force will execute the synchronization even when it already has been run. Use with caution.

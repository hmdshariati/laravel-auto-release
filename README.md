## Laravel Automate Release

Perhaps you know the situation when after `git pull` project falls with this an error. Cos somebody in your team has added a new dependency to the project and forget to tell you about it.
Or new project features working incorrectly because you forget to reset queues… There are too many cases for describing.

Laravel Automate Release is a tool that will help you to automatize sequence of routine console commands as:

```
php artisan down
git pull origin master
composer update
nmp run dev
[…]
php artisan up
```

Instead, you just run:

```
php artisan project:release
```

And Laravel Automate Release will do all of this boring job for you. It's simple. All you need is Git as version control.

Second problem is there are actions like composer update or build frontend that waste a lot of your time. But you need to do them because somebody in your team could update composer dependencies or change styles/js. Every time run this commands is very tedious, so Laravel Automate Release will do it only in case if it necessary.

Laravel Automate Release has integration with Git and can disable or enable some long term commands like `composer update` just watching for files like `composer.json` or `composer.lock`. If this files will be modified Laravel Automate Release will know about it and execute `composer update`. Overwise ... there is no need to force developers to wait :)

So let me show you some of this features.

## Installation

```
composer require andrewlrrr/laravel-automate-release
```

After updating composer, add the ServiceProvider to the providers array in `config/app.php`:

```
AndrewLrrr\LaravelProjectBuilder\ServiceProvider::class
```

And copy the package config to your local config with the publish command:

```
php artisan vendor:publish --provider="AndrewLrrr\LaravelProjectBuilder\ServiceProvider"
```

## Usage

The first step you can just run command:

```
php artisan project:release
```

**WARNING** before running this command you must commit all your changes overwise all changed and untracked files will be resetting.

This will run the sequence of actions:

```
php artisan down
git clean -f
git reset
git checkout master
git pull origin master
php artisan migrate

[if necessary]
composer update
[endif]

php artisan up
```

But you can ease change this behavior and register new commands and remove extra. For this, you should open `AppServiceProvider` or generate a separate service provider and add for example:

```php
$releaseManager = app()->make('project.release');

$releaseManager->register('npm_install', function ($shell) {
    return $shell->execCommand('npm install')->toString();
}, 'Installing NPM');
```

First parameter here is the command alias, second is callback function to describe command and third is message that will be shown before run the command.

Within callback function, you always have access to the `$shell` property which is responsible for commands execution.

Method `execCommand` has two parameters. First is simple shell command like `cd ../`, `pwd` etc. Second is path where command will be executed, for example:

```
[...]
return $shell->execCommand('pwd', base_path() . '/storage')->toSting();
[...]
```

After `execCommand` we use method `toString` it's necessary if you want to display command output. You can also use method `toArray` for array output but don't try to return it. It can be a reason of an error.

Pretty simple.

So now our list of actions looks like:

```
php artisan down
git clean -f
git reset
git checkout master
git pull origin master
php artisan migrate

[if necessary]
composer update
[endif]

php artisan up
npm_install
```

It seems `npm_install` must be before `php artisan up`. Ok, it's easy to fix. Make small changes in our code:

```php
$releaseManager = app()->make('project.release');

$releaseManager->before('up')->register('npm_install', function ($shell) {
    return $shell->execCommand('npm install')->toString();
}, 'Installing NPM');
```

Method `before` takes only one argument, the alias of command before which want to place new command.

If you want to place command after another you need to use method `after` which is similar to the `before` method.

In order to see all aliases, you can use `getCommands` method:

```php
foreach (app()->make('project.release')->getCommands() as $command) {
    var_dump($command);
}
```

If you don't want to run `npm install` command any time, you can specify in config what files or directories should be changed to fire this command. For example:

```php
config/builder.php

[...]
'watch' => [
    [...]
    'npm_install' => ['package.json', 'npm-shrinkwrap.json'],
    [...]
],
```

In current case `npm_install` is the command name and `['package.json', 'npm-shrinkwrap.json']` are paths to files to monitor. If `package.json` or `npm-shrinkwrap.json` file has been modified, `npm_istall` command will be fired during the release of the project.

Sometime watch for files is not enough. In this case you can specify in config directories for tracking:

```php
config/builder.php

[...]
'watch' => [
    [...]
    'laravel_mix' => [
        'resources/assets/js',
        'resources/assets/sass',
        'resources/assets/fonts',
    ],
    [...]
],
```

So, if some file in `resources/assets/js`, `resources/assets/sass` or `resources/assets/fonts` directories will be modified, deleted or added, `laravel_mix` command will be fired.

You can mix files and directories, for example:

```php
config/builder.php

[...]
'watch' => [
    [...]
    'laravel_mix' => [
        'webpack.mix.js',
        'resources/assets/js',
        'resources/assets/sass',
        'resources/assets/fonts',
    ],
    [...]
],
```

In current case Laravel Automate Release will watch for modifications of `webpack.mix.js` file and `resources/assets/js`, `resources/assets/sass` or `resources/assets/fonts` directories.

If you think that some of commands are extra for your case you can just delete them using method `delete` and command alias:

```php
$releaseManager = app()->make('project.release');

$releaseManager->delete('git_clean');
$releaseManager->delete('git_reset');
[...]
```

If you want to run `artisan` command you need to use `execArtisan` method instead `execCommand`. This method has two parameters. First is artisan command signature. Second is artisan command parameters, for example:

```php
[...]
return $shell->execArtisan('migrate', ['--force' => true])->toSting();
[...]
```

You should use `execArtisan` method instead `Artisan` facade method `call` if you want to display command output.

If you want to use command options inside callback function you can use method `options` and redefine command name in config:

```php
[...]
'command' => [
    'signature'   => 'project:release --sitemap',
[...]
```

```php
$releaseManager = app()->make('project.release');

$releaseManager->before('down')->register('sitemap', function ($shell) use ($releaseManager) {
    $doSitemap = (bool) $releaseManager->option('sitemap');
    if ($doSitemap) {
        return $shell->execArtisan('sitemap:generate')->toString();
    }
});
```
## Laravel Project Builder

Perhaps you know the situation when after git pull project falls with this an error. Cos somebody in your team has added a new dependency to the project and forget to tell you about it.
Or new project features working incorrectly because you forget to reset queues… There are too many cases for describing.

Laravel Project Builder is a tool that will help you to automatize sequence of routine console commands as:

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
php artisan project:build
```

And Laravel Project Builder will do all of this boring job for you. It's simple.

Ok, you can wonder, why I should use this package while I'm able to make my own command contains the sequence of all necessary subcommands. Yes you can, but Laravel Project Builder has integration with Git and it can help you to disable or enable some long term commands like `composer update` just add to commit keyphrases _composer update_ for example. Laravel Project Builder will find this key phrase and execute `composer update` overwise ... there is no need to force developers to wait :)

So let me show you some of this features.

## Installation

```
composer require andrewlrrr/laravel-project-builder
```

After updating composer, add the ServiceProvider to the providers array in `config/app.php`:

```
AndrewLrrr\LaravelProjectBuilder\ServiceProvider
```

If you want to redefine command name and description copy the package config to your local config with the publish command:

```
php artisan vendor:publish --provider="AndrewLrrr\LaravelProjectBuilder\ServiceProvider"
```

In additional to command settings in the config file, you can change `git` settings. One of them `log_depth` needs to be explained. Increase it if you or somebody on your team making a lot of commits (more `log_depth` value) before push them. 

## Usage

The first step you can just run command:

```
php artisan project:build
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
$releaseManager = app()->make('project.builder');

$releaseManager->register('npm_install', function () {
    return $this->shell->execCommand('npm install')->toString();
}, 'Installing NPM');
```

First parameter here is the command alias, second is callback function to describe command and third is message that will be shown before run the command.

Inside callback function you have access to two objects `shell` and `vscManager`. The first is responsible for commands execution and the second is responsible for the interaction with Git (I'll tell about it a little later).

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
$releaseManager = app()->make('project.builder');

$releaseManager->before('up')->register('npm_install', function () {
    return $this->shell->execCommand('npm install')->toString();
}, 'Installing NPM');
```

Method `before` takes only one argument, the alias of command before which want to place new command.

If you want to place command after another you need to use method `after` which is similar to the `before` method.

In order to see all aliases, you can use `getActions` method:

```php
foreach (app()->make('project.builder')->getActions() as $alias) {
    var_dump($alias);
}
```

If you think that some of this commands are extra for your case you can just delete them using method `delete` and command alias:

```php
$releaseManager = app()->make('project.builder');

$releaseManager->delete('git_clean');
$releaseManager->delete('git_reset');
[...]
```

Well, now that you know how to manage commands let's try to add a few and explore the other capabilities of the Laravel Project Builder. So open `AppServiceProvider` or generated service provider and add:

```php
$releaseManager = app()->make('project.builder');

$releaseManager->register('npm_install', function () {
    $force = (bool) $this->option('npm-install');
    if ($force || $this->vscManager->findBy('npm install')) {
        return $this->shell->execCommand('npm install')->toString();
    }
}, 'Defining if npm needs to be updated...');

$releaseManager->register('laravel_mix', function () {
    $force = (bool) $this->option('build-frontend');
    if ($force || $this->vscManager->findBy(['style', 'js', 'script', 'sass'])) {
        return $this->shell->execCommand('npm run ' . (app()->environment('production') ? 'production' : 'dev'))->toString();
    }
}, 'Defining if frontend needs to be built...');

$releaseManager->register('production_optimize', function () {
    $message = '';

    if (app()->environment('production')) {
        $message .= $this->shell->execArtisan('optimize')->toString();
        $message .= $this->shell->execArtisan('config.:cache')->toString();
        $message .= $this->shell->execArtisan('route:cache')->toString();
    }

    return $message;
}, (app()->environment('production') ? 'Optimizing the framework for better perfomance' : ''));
```

As I said above inside callback function you have access to two objects `shell` and `vscManager` and also access to command options.

In the first line of `npm_install` command callback, you try to get access to command options. In the current case, you use option for force running command regardless of what result will return `vskManager`. In the second line, you try to explore new commits and define should run nmp install or not. If someone from last commits will contain phrase `npm install` `shell` method `execCommand` would be running. This method has two parameters. First is simple shell command like `cd ../`, `pwd` etc. Second is path where command will be executed, for example:

```
$this->shell->execCommand('pwd', base_path() . '/storage')
```

After `execCommand` you use method `toString` it's necessary if you want to display command output. You can also use method `toArray` for array output but don't try to return it. It can be a reason of an error.

Pretty simple.

Let's look at the second command `laravel_mix`. In general, it's the similar `npm_install`. But instead string you pass array to `vscManager` `findBy` method. It means that it will be exploring the commits content for words `style` or `js` or `script` or `sass`. If it finds one of them shell command would be running.

In the third command `production_optimize` you use `execArtisan` method instead `execCommand`. This method has two parameters. First is artisan command signature. Second is artisan command parameters, for example:

```
$this->shell->execArtisan('migrate', ['--force' => true]);
```

You should use `execArtisan` method instead `Artisan` facade method `call` if you want to display command output.
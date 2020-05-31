# CLI Utils

[![License](https://img.shields.io/packagist/l/toolkit/cli-utils.svg?style=flat-square)](LICENSE)
[![Php Version](https://img.shields.io/badge/php-%3E=7.1.0-brightgreen.svg?maxAge=2592000)](https://packagist.org/packages/toolkit/cli-utils)
[![Latest Stable Version](http://img.shields.io/packagist/v/toolkit/cli-utils.svg)](https://packagist.org/packages/toolkit/cli-utils)
[![Github Actions Status](https://github.com/php-toolkit/cli-utils/workflows/Unit-tests/badge.svg)](https://github.com/php-toolkit/cli-utils/actions)

Provide some useful utils for the php CLI. 

- Parse CLI arguments and options
- Console color render
- CLI code highlighter
- Build simple CLI application
- CLI env information helper

## Install

```bash
composer require toolkit/cli-utils
```

## Console color

```php
Color::printf('<info>%s</info> world', 'hello');
Color::println('hello world', 'info');
Color::println('hello world', 'error');
Color::println('hello world', 'warning');
Color::println('hello world', 'success');

echo Color::render('hello world', 'success');
```

![colors](example/terminal-color.png)

## PHP file highlight

> This is inspire jakub-onderka/php-console-highlighter

```php
use Toolkit\Cli\Highlighter;

// this is an comment
$rendered = Highlighter::create()->highlight(file_get_contents(__FILE__));

\Toolkit\Cli\Cli::write($rendered);
```

![colors](example/cli-php-file-highlight.png)

## Parse CLI arguments and options

```php
use Toolkit\Cli\Flags;

$argv = $_SERVER['argv'];
// notice: must shift first element.
$script = \array_shift($argv);
// do parse
list($args, $shortOpts, $longOpts) = Flags::parseArgv($argv);
```

## Build CLI application

You can quickly build an simple CLI application:

```php
use Toolkit\Cli\App;

// create app instance
$app = new App([
  'desc' => 'this is my cli application',
]);
```

### Register commands

Use closure:

```php
$app->addCommand('test', function ($app) {
    echo "args:\n";
    /** @var Toolkit\Cli\App $app */
    /** @noinspection ForgottenDebugOutputInspection */
    print_r($app->getArgs());

}, 'the description text for the command: test');
```

Use closure with config:

```php
$app->addByConfig(function ($app) {
    echo "args:\n";
    /** @var Toolkit\Cli\App $app */
    /** @noinspection ForgottenDebugOutputInspection */
    print_r($app->getArgs());

}, [
  'name' => 'cmd2',
  'desc' => 'the description text for the command: test',
]);
```

Use an object:

```php
class MyCommand
{
    public function getHelpConfig(): array
    {
        $help = <<<STR
Options:
  --info    Output some information

Example:
  {{fullCmd}}

STR;

        return [
            'name'  => 'list',
            'desc'  => 'list all directory name in src/',
            'help'  => $help,
        ];
    }

    public function __invoke(App $app)
    {
        echo "hello\n";
    }
}

// add command
$app->addObject(new MyCommand);
```

### Run application

```php
// run
$app->run();
```

Run demo: `php example/liteApp`

![cli-app](example/cli-app.png)

## CLI downloader

```php
use Toolkit\Cli\Download;

$url  = 'http://no2.php.net/distributions/php-7.2.5.tar.bz2';
$down = Download::file($url, '');

// $down->setShowType('bar');
$down->start();
```

### Progress bar output:

```text
Connected...
Mime-type: text/html; charset=utf-8
Being redirected to: http://no2.php.net/distributions/php-7.2.5.tar.bz2
Connected...
FileSize: 14280 kb
Mime-type: application/octet-stream
[========================================>                                                           ] 40% (3076/7590 kb)
```

### Progress text output:

```text
Download: http://no2.php.net/distributions/php-7.2.5.tar.bz2
Save As: /path/to/php-7.2.5.tar.bz2

Connected ...
Got the file size: 14280 kb
Found the mime-type: application/octet-stream
Made some progress, downloaded 641 kb so far
```

## License

[MIT](LICENSE)

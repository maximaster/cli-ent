# maximaster/cli-ent

Guzzle [handler](https://docs.guzzlephp.org/en/stable/handlers-and-middleware.html#handlers)
to imitate HTTP calls through CLI.

## Installing

```bash
composer require maximaster/cli-ent
```

## Reasoning

Imagine that you have a legacy CMS which can be installed only through web
interface. By using the handler you can install such a CMS using CLI just by
calling needed http queries like you would do it through web interface, but
without running webserver.

## Usage

```php
<?php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Guzzle\Parser\Cookie\CookieParser;
use Maximaster\CliEnt\CliEntHandler;
use Maximaster\CliEnt\GlobalsParser;
use Guzzle\Parser\Message\MessageParser;

$cliEntHandler = new CliEntHandler(
    new GlobalsParser(new CookieParser()),
    new MessageParser(),
    '/var/www',
    function (array &$globals) {
        // you can mofify global variables here before execution
        $globals['_ENV'] = ['a' => 'Lorem', 'b' => 'ipsum'];
        $globals['_SERVER']['DOCUMENT_ROOT'] = '/var/www';
    }
);

$client = new Client(['handler' => HandlerStack::create($cliEntHandler)]);
$response = $client->get('http://localhost/install.php');
// etc
```

## Developing

* `composer run test` to run tests;
* `composer run lint` to lint;

<?php

namespace Maximaster\CliEnt;

use Guzzle\Parser\Cookie\CookieParser;
use Guzzle\Parser\Message\MessageParser;
use Guzzle\Parser\Message\MessageParserInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use Maximaster\CliEnt\Contract\GlobalsParserInterface;

class CliEntFactory
{
    public function build(
        string $documentRoot,
        ?GlobalsParserInterface $globalsParser = null,
        ?MessageParserInterface $messageParser = null,
        ?CookieJarInterface $cookieJar = null,
        ?callable $globalsHandler = null,
        string $baseUri = 'http://localhost'
    ): ClientInterface {
        return new Client([
            'base_uri' => $baseUri,
            'handler' => new CliEntHandler(
                $globalsParser ?: new GlobalsParser(new CookieParser()),
                $messageParser ?: new MessageParser(),
                $documentRoot,
                function (array &$globals) use ($documentRoot, $globalsHandler) {
                    $globals['_SERVER'] += [
                        'DOCUMENT_ROOT' =>  $documentRoot,
                    ];

                    if ($globalsHandler) {
                        $globalsHandler($globals);
                    }
                }
            ),
            'cookie' => $cookieJar ?: new CookieJar(),
        ]);
    }
}

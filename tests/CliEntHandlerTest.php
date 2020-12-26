<?php

namespace Maximaster\CliEnt\Test;

use Exception;
use Guzzle\Parser\Cookie\CookieParser;
use Guzzle\Parser\Message\MessageParser;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Maximaster\CliEnt\CliEntHandler;
use Maximaster\CliEnt\GlobalsParser;
use PHPUnit\Framework\TestCase;

class CliEntHandlerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testGlobalsTransfer()
    {
        $cliEnt = $this->prepareCliEnt(function (array &$globals) {
            $globals['_ENV'] = ['a' => 'Lorem', 'b' => 'ipsum'];
            $globals['_SERVER']['DOCUMENT_ROOT'] = '/var/www';
        });

        /** @var Response $response */
        $response = $cliEnt(
            new Request(
                'POST',
                'https://phpunit.localhost/globals/index.php?G[a]=dolor&G[b]=sit',
                [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Cookie' => 'C[a]=adipiscing; C[b]=elit',
                ],
                http_build_query([
                    'P' => [
                        'a' => 'amet',
                        'b' => 'consectetur'
                    ],
                ])
            )
        )->wait();

        $this->assertEquals(
            trim(file_get_contents(__DIR__.'/cases/globals/output.json')),
            trim($response->getBody()->getContents())
        );
    }

    /**
     * @throws Exception
     */
    public function testSession()
    {
        $cliEnt = $this->prepareCliEnt();

        $cookies = [];
        foreach (range(1, 10) as $try) {
            /** @var Response $response */
            $response = $cliEnt(new Request(
                'GET',
                'https://phpunit.localhost/session/index.php',
                [
                    'Cookie' => implode('; ', $cookies),
                ]
            ))->wait();

            if ($response->hasHeader('Set-Cookie')) {
                $cookies = array_unique(array_merge($cookies, $response->getHeader('Set-Cookie')));
            }

            $this->assertEquals((string) $try, $response->getBody()->getContents());
        }
    }

    /**
     * @param callable|null $globalsHandler
     *
     * @return CliEntHandler
     *
     * @throws Exception
     */
    private function prepareCliEnt(?callable $globalsHandler = null): CliEntHandler
    {
        return new CliEntHandler(
            new GlobalsParser(new CookieParser(), 'EGPCS'),
            new MessageParser(),
            __DIR__.'/cases',
            $globalsHandler
        );
    }
}

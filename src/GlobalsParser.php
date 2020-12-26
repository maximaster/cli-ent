<?php

namespace Maximaster\CliEnt;

use Guzzle\Parser\Cookie\CookieParserInterface;
use Maximaster\CliEnt\Contract\GlobalsParserInterface;
use Psr\Http\Message\RequestInterface;

class GlobalsParser implements GlobalsParserInterface
{
    const DEFAULT_REQUEST_ORDER = 'EGPCS';

    /** @var CookieParserInterface */
    private $cookieParser;

    /** @var string */
    private $requestOrder;

    public function __construct(CookieParserInterface $cookieParser, string $requestOrder = self::DEFAULT_REQUEST_ORDER)
    {
        $this->cookieParser = $cookieParser;
        $this->requestOrder = $requestOrder;
    }

    public function parse(RequestInterface $request): array
    {
        $uri = $request->getUri();

        $globals = [
            '_ENV' => [],
            '_GET' => [],
            '_POST' => [],
            '_COOKIE' => [],
            '_SESSION' => [],
            '_REQUEST' => [],
            '_SERVER' => [],
        ];

        parse_str($uri->getQuery(), $globals['_GET']);

        $contentType = $request->getHeader('Content-Type');
        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
            default:
                parse_str($request->getBody()->getContents(), $globals['_POST']);
        }

        foreach ($request->getHeader('Cookie') as $cookiePart) {
            $cookieInfo = $this->cookieParser->parseCookie($cookiePart, $uri->getHost(), $uri->getPath(), true);
            if ($cookieInfo === false || empty($cookieInfo['cookies'])) {
                continue;
            }

            $globals['_COOKIE'] = array_merge($globals['_COOKIE'], $cookieInfo['cookies']);
        }

        if ($this->requestOrder) {
            foreach (str_split($this->requestOrder, 1) as $globalPart) {
                foreach (array_keys($globals) as $globalPartFullName) {
                    if (strpos($globalPartFullName, "_{$globalPart}") === 0) {
                        $globals['_REQUEST'] = array_merge($globals['_REQUEST'], $globals[$globalPartFullName]);
                    }
                }
            }
        }

        $globals['_SERVER']['PHP_SELF'] = $uri->getPath();

        return $globals;
    }
}

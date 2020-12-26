<?php

namespace Maximaster\CliEnt\Contract;

use Psr\Http\Message\RequestInterface;

interface GlobalsParserInterface
{
    public function parse(RequestInterface $request): array;
}

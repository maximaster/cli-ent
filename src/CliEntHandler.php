<?php

namespace Maximaster\CliEnt;

use Exception;
use Guzzle\Parser\Message\MessageParserInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Maximaster\CliEnt\Contract\GlobalsParserInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class CliEntHandler
{
    const CLI_ENT_INPUT = 'CLI_ENT_INPUT';
    const CLI_ENT_OUTPUT = 'CLI_ENT_OUTPUT';

    /** @var callable|null */
    private $globalsHandler;

    /** @var string */
    private $resourcesRoot;

    /** @var string */
    private $bootstrapFile;

    /** @var GlobalsParserInterface */
    private $globalsExtractor;

    /** @var MessageParserInterface */
    private $messageParser;

    /** @var bool */
    private $ignoreRunkitMiss;

    /**
     * @param GlobalsParserInterface $globalsExtractor
     * @param MessageParserInterface $messageParser
     * @param string $resourcesRoot
     * @param callable|null $globalsHandler
     * @param string|null $bootstrapFile
     *
     * @param bool $ignoreRunkitMiss
     * @throws Exception
     */
    public function __construct(
        GlobalsParserInterface $globalsExtractor,
        MessageParserInterface $messageParser,
        string $resourcesRoot,
        ?callable $globalsHandler = null,
        string $bootstrapFile = null,
        bool $ignoreRunkitMiss = false
    ) {
        $this->resourcesRoot = $resourcesRoot;

        if ($bootstrapFile && !file_exists($bootstrapFile)) {
            throw new Exception(sprintf("Specified bootstap file '%' doesn't exist", $bootstrapFile));
        }

        $this->bootstrapFile = $bootstrapFile ?: __DIR__.'/bootstrap.php';
        $this->globalsExtractor = $globalsExtractor;
        $this->messageParser = $messageParser;
        $this->globalsHandler = $globalsHandler;
        $this->ignoreRunkitMiss = $ignoreRunkitMiss;
    }

    /**
     * @param RequestInterface $request
     * @return PromiseInterface<Response>
     */
    public function __invoke(RequestInterface $request): PromiseInterface
    {
        $uri = $request->getUri();

        $resource = realpath($this->resourcesRoot . DIRECTORY_SEPARATOR . $uri->getPath());

        $globals = $this->globalsExtractor->parse($request);
        $this->globalsHandler && ($this->globalsHandler)($globals);

        $process = new Process(
            array_merge(
                [(new PhpExecutableFinder())->find(true)],
                [
                    "-d", "auto_prepend_file={$this->bootstrapFile}",
                    $resource,
                ]
            ),
            null,
            [
                self::CLI_ENT_INPUT => json_encode(compact('globals') + [
                    'ignoreRunkitMiss' => $this->ignoreRunkitMiss,
                ])
            ],
            null,
            null
        );

        $process->run();

        $rawOutput = $process->getOutput();

        $httpCode = 200;
        $headers = [];

        $output = preg_replace_callback(
            '/'.preg_quote(self::CLI_ENT_OUTPUT).'=(.*)\r?\n/',
            function (array $matches) use (&$httpCode, &$headers) {
                [, $encodedMessage] = $matches;

                $rawMessage = base64_decode($encodedMessage);
                if ($rawMessage === false) {
                    throw new Exception(sprintf("Can't base64_decode output message: %s", $encodedMessage));
                }

                $message = $this->messageParser->parseResponse($rawMessage);
                if ($message === false) {
                    throw new Exception('Response message is invalid');
                }

                $httpCode = $message['code'];
                $headers = $message['headers'];

                return '';
            },
            $rawOutput
        );

        return new FulfilledPromise(new Response(
            $httpCode,
            $headers,
            $output
        ));
    }
}

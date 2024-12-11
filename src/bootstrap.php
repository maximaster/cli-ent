<?php

/**
 * This file executes before target file to set up global variables such as $_SERVER['DOCUMENT_ROOT']
 * @phan-file-suppress PhanRedefineFunctionInternal because it's on purpose
 * @noinspection PhpSillyAssignmentInspection see [1]
 */
(function ($cliEntInput) {
    if (empty($cliEntInput)) {
        throw new Exception("CLI_ENT_INPUT environment variable hasn't been passed");
    }

    if (!is_array($cliEntInput)) {
        throw new Exception(sprintf('CLI_ENT_INPUT should be array, %s given', gettype($cliEntInput)));
    }

    foreach ($cliEntInput['globals'] ?? [] as $global => $variables) {
        $GLOBALS[$global] = $variables + ($GLOBALS[$global] ?? []);
    }

    global $cliEntHttpCode, $cliEntHeaders;

    $cliEntHttpCode = 200;
    $cliEntHeaders = [];

    if (function_exists('header') === false) {
        function header($header, $replace = true, $forcedHttpCode = null)
        {
            global $cliEntHttpCode, $cliEntHeaders;

            if ($replace === false || !in_array($header, $cliEntHeaders)) {
                $cliEntHeaders[] = $header;
            }
            if ($forcedHttpCode) {
                $cliEntHttpCode = $forcedHttpCode;
            }
        }
    }

    session_set_save_handler(
        new class($cliEntInput['globals']['_SESSION'] ?? []) implements SessionHandlerInterface {
            private array $initialSession;

            public function __construct(array $initialSession)
            {
                $this->initialSession = $initialSession;
            }
            public function open($savePath, $sessionName): bool
            {
                return true;
            }

            public function close(): bool
            {
                return true;
            }

            public function read($sessionId): string
            {
                global $cliEntHeaders;

                $cliEntHeaders[] = 'Set-Cookie: PHPSESSID=' . $sessionId;
                $_SESSION = $this->initialSession;

                return session_encode();
            }

            public function write($sessionId, $data): bool
            {
                return true;
            }

            public function destroy($sessionId): bool
            {
                return true;
            }

            public function gc($maxLifetime): int|false
            {
                return true;
            }
        },
        false
    );

    register_shutdown_function(function () use (&$cliEntHttpCode, &$cliEntHeaders) {
        $cliEntHeaders[] = 'Set-Cookie: CLI_ENT_SESSION=' . base64_encode(serialize($_SESSION));

        echo 'CLI_ENT_OUTPUT=' . base64_encode(
            "HTTP/1.1 {$cliEntHttpCode}" . PHP_EOL . implode(PHP_EOL, $cliEntHeaders) . PHP_EOL . PHP_EOL
        ) . PHP_EOL;
    });

    // [1] For some reason without this "useless" code we would have empty global variables
    // (at least $_REQUEST in my case) in called script. PHP issue?
    $_SERVER = $_SERVER;
    $_GET = $_GET;
    $_POST = $_POST;
    $_FILES = $_FILES;
    $_COOKIE = $_COOKIE;
    $_SESSION = $_SESSION ?? [];
    $_REQUEST = $_REQUEST;
    $_ENV = $_ENV;
})(
    // JSON config
    json_decode(getenv('CLI_ENT_INPUT'), true)
);

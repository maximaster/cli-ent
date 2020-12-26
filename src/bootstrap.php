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

    $runkitAvailable = extension_loaded('runkit') || extension_loaded('runkit7');
    if (!$runkitAvailable && empty($cliEntInput['ignoreRunkitMiss'])) {
        throw new Exception("runkit extension hasn't been found. If you don't need ability to track headers, you must pass ignoreRunkitMiss=true in CLI_ENT_INPUT environment variable to skip this exception");
    }

    foreach ($cliEntInput['globals'] ?? [] as $global => $variables) {
        $GLOBALS[$global] = $variables + ($GLOBALS[$global] ?? []);
    }

    $cliEntHttpCode = 200;
    $cliEntHeaders = [];

    global $__CLI_ENT_FUNCTIONS;
    $__CLI_ENT_FUNCTIONS = [
        'header' => function ($header, $replace = true, $forcedHttpCode = null) use (&$cliEntHttpCode, &$cliEntHeaders) {
            if ($replace === false || !in_array($header, $cliEntHeaders)) {
                $cliEntHeaders[] = $header;
            }
            if ($forcedHttpCode) {
                $cliEntHttpCode = $forcedHttpCode;
            }
        },
        'session_start' => function() use (&$cliEntHeaders) {
            // @phan-suppress-next-line PhanUndeclaredFunction
            $sessionStarted = php_session_start();
            if ($sessionStarted) {
                $cliEntHeaders[] = 'Set-Cookie: PHPSESSID='.session_id();
            }
            return $sessionStarted;
        },
    ];

    if (function_exists('runkit_function_remove')) {
        runkit_function_remove('header');
        function header($header, $replace = true, $forcedHttpCode = null) {
            return $GLOBALS['__CLI_ENT_FUNCTIONS'][__FUNCTION__]($header, $replace, $forcedHttpCode);
        }
    }

    if (function_exists('runkit_function_rename')) {
        runkit_function_rename('session_start', 'php_session_start');
        function session_start() {
            return $GLOBALS['__CLI_ENT_FUNCTIONS'][__FUNCTION__]();
        }
    }

    register_shutdown_function(function() use (&$cliEntHttpCode, &$cliEntHeaders) {
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
    $_SESSION = $_SESSION;
    $_REQUEST = $_REQUEST;
    $_ENV = $_ENV;
})(
    // JSON config
    json_decode(getenv('CLI_ENT_INPUT'), true)
);

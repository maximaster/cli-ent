<?php

echo json_encode(
    [
        '_ENV' => [
            'a' => $_ENV['a'],
            'b' => $_ENV['b'],
        ],
        '_GET' => [
            'G' => [
                'a' => $_GET['G']['a'],
                'b' => $_GET['G']['b'],
            ],
        ],
        '_POST' => [
            'P' => [
                'a' => $_POST['P']['a'],
                'b' => $_POST['P']['b'],
            ],
        ],
        '_COOKIE' => [
            'C[a]' => $_COOKIE['C[a]'],
            'C[b]' => $_COOKIE['C[b]'],
        ],
        '_SERVER' => [
            'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'],
        ],
    ],
    JSON_PRETTY_PRINT
);

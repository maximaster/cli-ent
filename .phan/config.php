<?php

return [
    'directory_list' => ['src', 'tests', 'vendor'],
    'exclude_analysis_directory_list' => ['vendor', 'vendor/phan'],
    'exclude_file_regex' => '@^vendor/(phan|phpunit/php-code-coverage)@',
];

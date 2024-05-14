<?php

declare(strict_types=1);

namespace Tests\Utils;

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\Transformers\Utils\Hub;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use function Codewithkyrian\Transformers\Utils\ensureDirectory;
use function Codewithkyrian\Transformers\Utils\joinPaths;

beforeEach(function () {
    Transformers::setup()
        ->setCacheDir('tests/models')
        ->apply();
});

it('joins paths correctly', function () {
    $result = joinPaths('path', 'to', 'file');
    expect($result)->toBe('path/to/file');
});

it('joins paths correctly with leading slash', function () {
    $result = joinPaths('/path', 'to', 'file');
    expect($result)->toBe('/path/to/file');
});

it('joins paths correctly with trailing slash', function () {
    $result = joinPaths('path', 'to', 'file/');
    expect($result)->toBe('path/to/file');
});

it('joins paths correctly with empty string', function () {
    $result = joinPaths('path', '', 'file');
    expect($result)->toBe('path/file');
});

it('joins paths correctly with empty string and slashes', function () {
    $result = joinPaths('path', '', '/file');
    expect($result)->toBe('path/file');
});

it('ensures directory creation', function () {
    $filePath = 'cache/test/file.txt';
    ensureDirectory($filePath);

    expect(is_dir('cache/test'))->toBeTrue()
        ->and(file_exists($filePath))->toBeFalse();

    rmdir('cache/test');
    rmdir('cache');
});

it('combines part files correctly', function () {
    // Allow test write access
    if (!is_dir('cache/test')) {
        mkdir('cache/test', 0777, true);
    }

    // Create sample part files
    file_put_contents('cache/test/file.txt.part1', 'Part 1');
    file_put_contents('cache/test/file.txt.part2', 'Part 2');
    file_put_contents('cache/test/file.txt.part3', 'Part 3');


    // Combine part files
    Hub::combinePartFiles('cache/test/file.txt', 'cache/test/file.txt.part', 3);

    // Check if combined file exists
    expect(file_exists('cache/test/file.txt'))->toBeTrue()
        ->and(file_get_contents('cache/test/file.txt'))->toBe('Part 1Part 2Part 3');

    // Clean up
    unlink('cache/test/file.txt');
    rmdir('cache/test');
    rmdir('cache');
});

//it('downloads a file correctly', function () {
////    $mock = new MockHandler([new Response(200, [], 'File content')]);
////
////    $client = new Client(['handler' => $mock]);
//
//    $filePath = Hub::getFile('model_id', 'file.txt');
//
//    expect($filePath)->toBe('tests/models/model_id/file.txt')
//        ->and(file_exists($filePath))->toBeTrue()
//        ->and(file_get_contents($filePath))->toBe('File content');
//
//    unlink($filePath);
//    rmdir('tests/models/model_id');
//});

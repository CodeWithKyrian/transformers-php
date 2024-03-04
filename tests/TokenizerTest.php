<?php

declare(strict_types=1);

namespace Tests;

use Codewithkyrian\Transformers\PretrainedTokenizers\AutoTokenizer;
use Codewithkyrian\Transformers\Transformers;

beforeAll(function () {
    Transformers::configure()
        ->setCacheDir('tests/models');
});

it('can tokenize a text', function ($textToTokenize, $expectedTokens) {
    $tokenizer = AutoTokenizer::fromPretrained('Xenova/bert-base-uncased');

    $encoded = $tokenizer($textToTokenize);

    expect($encoded['input_ids'][0]->toArray())->toBe($expectedTokens);
})
    ->with([
        "Hello, world!" => ['Hello, world!', [101, 7592, 1010, 2088, 999, 102]],
        "Hello, world! How are you?" => ['Hello, world! How are you?', [101, 7592, 1010, 2088, 999, 2129, 2024, 2017, 1029, 102]],
    ]);

it('can tokenize a text with padding and truncation', function () {
    $tokenizer = AutoTokenizer::fromPretrained('Xenova/bert-base-uncased');

    $encoded = $tokenizer(['a b c', 'd'], padding: true, truncation: true);

    $expected = [
        'input_ids' => [[101, 1037, 1038, 1039, 102], [101, 1040, 102, 0, 0]],
    ];

    expect($encoded['input_ids']->toArray())->toBe($expected['input_ids']);
});


it('should correctly add tokenTypeIds', function () {
    $tokenizer = AutoTokenizer::fromPretrained('Xenova/bert-base-uncased');

    $encoded = $tokenizer(['a b c', 'd'], ['e f g', 'h'], padding: true, truncation: true);

    $expected = [
        'input_ids' => [[101, 1037, 1038, 1039, 102, 1041, 1042, 1043, 102], [101, 1040, 102, 1044, 102, 0, 0, 0, 0]],
        'token_type_ids' => [[0, 0, 0, 0, 0, 1, 1, 1, 1], [0, 0, 0, 1, 1, 0, 0, 0, 0]],
        'attention_mask' => [[1, 1, 1, 1, 1, 1, 1, 1, 1], [1, 1, 1, 1, 1, 0, 0, 0, 0]],
    ];

    expect($encoded['input_ids']->toArray())->toBe($expected['input_ids'])
        ->and($encoded['token_type_ids']->toArray())->toBe($expected['token_type_ids'])
        ->and($encoded['attention_mask']->toArray())->toBe($expected['attention_mask']);
});





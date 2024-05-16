<?php

declare(strict_types=1);

namespace Tests\Utils;

use Codewithkyrian\Transformers\PretrainedTokenizers\PretrainedTokenizer;
use Codewithkyrian\Transformers\Transformers;

use function Codewithkyrian\Transformers\Pipelines\pipeline;

beforeEach(function () {
    Transformers::setup()
        ->setCacheDir('tests/models')
        ->apply();
});

/**
 * TODO
 */
it('trigger array_slice error using test data', function () {
    $generator = pipeline('summarization', 'Xenova/distilbart-cnn-6-6');
    $text = file_get_contents(__DIR__.'/../test_files/extracted_text_pdf.txt');
    $result = $generator($text);

    expect($result[0]['summary_text'])->toContain('last comprehensive');
});
<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Exceptions\UnsupportedTaskException;
use Codewithkyrian\Transformers\Pipelines\FeatureExtractionPipeline;
use Codewithkyrian\Transformers\Transformers;
use function Codewithkyrian\Transformers\Pipelines\pipeline;

beforeAll(function () {
    Transformers::setup()
        ->setCacheDir('tests/models')
        ->apply();
});

it('can create a pipeline for a task', function () {
    $extractor = pipeline('feature-extraction');

    expect($extractor)->toBeInstanceOf(FeatureExtractionPipeline::class);
});


it('can create a pipeline for a task with a model', function () {
    $extractor = pipeline('feature-extraction', 'Xenova/all-MiniLM-L6-v2');

    expect($extractor)->toBeInstanceOf(FeatureExtractionPipeline::class);
});

it('throws an exception when creating a pipeline for an unsupported task', function () {
    pipeline('unsupported-task');
})->throws(UnsupportedTaskException::class);
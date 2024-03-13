<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use Codewithkyrian\Transformers\Utils\AggregationStrategy;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

ini_set('memory_limit', -1);

$classifier = pipeline('token-classification', 'Xenova/bert-base-NER');

$output = $classifier(
    'My name is Kyrian and I live in United States of America',
//    aggregationStrategy: AggregationStrategy::FIRST
    ignoreLabels: []
);

dd($output, timeUsage(), memoryUsage());

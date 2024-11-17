<?php

declare(strict_types=1);

require_once './bootstrap.php';

use Codewithkyrian\Transformers\Generation\AggregationStrategy;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

ini_set('memory_limit', -1);

 $classifier = pipeline('token-classification', 'Xenova/bert-base-NER');
//$classifier = pipeline('token-classification', 'codewithkyrian/bert-english-uncased-finetuned-pos');

$output = $classifier(
    'My name is Kyrian and I live in Nigeria',
    aggregationStrategy: AggregationStrategy::FIRST
);

dd($output, timeUsage(), memoryUsage());

<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use function Codewithkyrian\Transformers\{memoryPeak, memoryUsage, pipeline};

ini_set('memory_limit', '160M');

//$classifier = pipeline('zero-shot-classification', 'Xenova/mobilebert-uncased-mnli');
//$result = $classifier('Who are you voting for in 2020?', ['politics', 'public health', 'economics', 'elections']);



//$classifier = pipeline('zero-shot-classification', 'Xenova/nli-deberta-v3-xsmall');
//$result = $classifier(
//    'I have a problem with my iphone that needs to be resolved asap!',
//    ['urgent', 'not urgent', 'phone', 'tablet', 'computer'],
//    multiLabel: true
//);


$classifier = pipeline('zero-shot-classification', 'Xenova/nli-deberta-v3-xsmall');

$result = $classifier('Apple just announced the newest iPhone 13', ["technology", "sports", "politics"]);

dd(memoryUsage(), $result);


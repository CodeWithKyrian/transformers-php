<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use function Codewithkyrian\Transformers\{memoryUsage, pipeline};

$extractor = pipeline('feature-extraction', 'Xenova/bert-base-uncased');


$result = $extractor('The quick brown fox jumps over the lazy dog.', normalize: true);

dd(memoryUsage(), $result->shape());





$extractor = pipeline('feature-extraction', 'Xenova/all-MiniLM-L6-v2');


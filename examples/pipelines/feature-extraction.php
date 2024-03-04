<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use function Codewithkyrian\Transformers\{memoryUsage, pipeline, timeUsage};

try {
    //$extractor = pipeline('feature-extraction', 'Xenova/bert-base-uncased');
    $extractor = pipeline('embeddings', 'Xenova/all-MiniLM-L6-v2');


    $embeddings = $extractor('The quick brown fox jumps over the lazy dog.', normalize: true, pooling: 'mean');

    dd(memoryUsage(), timeUsage(), count($embeddings[0]));


} catch (Exception $e) {
    dd($e->getMessage());
}
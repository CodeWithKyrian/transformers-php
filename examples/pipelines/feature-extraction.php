<?php

declare(strict_types=1);

use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\{memoryUsage, timeUsage};

require_once './bootstrap.php';


//$extractor = pipeline('feature-extraction', 'Xenova/bert-base-uncased');
$extractor = pipeline('embeddings', 'Xenova/all-MiniLM-L6-v2');
//$extractor = pipeline('embeddings', 'Xenova/paraphrase-albert-small-v2');

$embeddings = $extractor('The quick brown fox jumps over the lazy dog.', normalize: true, pooling: 'mean');

dd(memoryUsage(), timeUsage(), $embeddings[0]);

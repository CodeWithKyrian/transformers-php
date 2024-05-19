<?php

declare(strict_types=1);

use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

$classifier = pipeline('audio-classification', 'Xenova/ast-finetuned-audioset-10-10-0.4593');

//$audioUrl = __DIR__ . '/../sounds/dog_barking.wav';
$audioUrl = __DIR__ . '/../sounds/cat_meow.wav';

$output = $classifier($audioUrl, topK: 4);

dd($output, timeUsage(), memoryUsage());

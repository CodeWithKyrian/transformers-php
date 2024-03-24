<?php

declare(strict_types=1);

use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

$classifier = pipeline('zero-shot-image-classification', 'Xenova/clip-vit-base-patch32');

$url = __DIR__. '/../images/tiger.jpg';

$output = $classifier($url, ['tiger', 'horse', 'dog']);

dd($output, timeUsage(), memoryUsage());

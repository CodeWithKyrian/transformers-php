<?php

declare(strict_types=1);

use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

$classifier = pipeline('image-classification', 'Xenova/vit-base-patch16-224');

$urls = [
    __DIR__. '/../images/tiger.jpg',
    __DIR__. '/../images/corgi.jpg',
    __DIR__. '/../images/cats.jpg',
];

$output =  $classifier($urls);

dd($output, timeUsage(), memoryUsage());

<?php

declare(strict_types=1);

use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

$classifier = pipeline('image-classification', 'Xenova/vit-base-patch16-224');

$url = __DIR__. '/../images/corgi.jpg';

$output =  $classifier($url);

dd($output, timeUsage(), memoryUsage());

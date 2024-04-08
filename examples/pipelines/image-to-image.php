<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Generation\Streamers\StdOutStreamer;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

ini_set('memory_limit', '2048M');

$upscaler = pipeline('image-to-image', 'Xenova/swin2SR-classical-sr-x2-64');

$url = __DIR__. '/../images/butterfly.jpg';

$output = $upscaler($url);

$output->save(__DIR__. '/../images/butterfly-super-resolution.jpg');

dd($output->size(), timeUsage(), memoryUsage());

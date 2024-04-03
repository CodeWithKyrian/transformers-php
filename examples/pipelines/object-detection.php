<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Pipelines;

use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';
ini_set('memory_limit', '-1');
$detector = pipeline('object-detection', 'Xenova/detr-resnet-50');

$img = __DIR__. '/../images/cats.jpg';

$output = $detector($img, threshold: 0.9);

dd($output, timeUsage(), memoryUsage());


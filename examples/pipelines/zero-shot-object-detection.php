<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Pipelines;

use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

ini_set('memory_limit', '-1');

$detector = pipeline('zero-shot-object-detection', 'Xenova/owlvit-base-patch32');

$url = __DIR__ . '/../images/astronaut.png';
$candidateLabels = ['human face', 'rocket', 'helmet', 'american flag'];

//$url = __DIR__. '/../images/beach.png';
//$candidateLabels = ['hat', 'book', 'sunglasses', 'camera'];

$output = $detector($url, $candidateLabels, topK: 4, threshold: 0.05);

dd($output, timeUsage(), memoryUsage());
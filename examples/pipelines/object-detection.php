<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Pipelines;

use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

ini_set('memory_limit', '-1');

$detector = pipeline('object-detection', 'Xenova/detr-resnet-50');

$img = __DIR__.'/../images/cats.jpg';

$output = $detector($img, threshold: 0.9);

dd($output, timeUsage(), memoryUsage());

//$image = Image::read($img);
//
//foreach ($output as $item) {
//    $box = $item['box'];
//    $image = $image->drawRectangle($box['xmin'], $box['ymin'], $box['xmax'], $box['ymax'], '0099FF', thickness: 2);
//    $image = $image->drawText($item['label'], $box['xmin'], max($box['ymin'] - 5, 0), '/Users/Kyrian/Library/Fonts/JosefinSans-Bold.ttf', 14, '0099FF');
//}
//
//$image->save(__DIR__ . '/../images/cats-detection.jpg');



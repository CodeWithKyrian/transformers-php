<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\Auto\AutoModel;
use Codewithkyrian\Transformers\Processors\AutoProcessor;
use Codewithkyrian\Transformers\Utils\Image;

require_once './bootstrap.php';

ini_set('memory_limit', '-1');

$processor = AutoProcessor::fromPretrained('Xenova/yolov9-c_all');
$model = AutoModel::fromPretrained('Xenova/yolov9-c_all');

$image = Image::read(__DIR__.'/../images/corgi.jpg');

$inputs = $processor($image);

['outputs' => $outputs] = $model($inputs);

$boxes = array_map(function ($args) use ($inputs, $model): ?array {
    [$xmin, $ymin, $xmax, $ymax, $score, $id] = $args;

    if ($score < 0.11) return null;

    return [
        'xmin' => $xmin,
        'ymin' => $ymin,
        'xmax' => $xmax,
        'ymax' => $ymax,
        'score' => $score,
        'label' => $model->config['id2label'][$id] ?? 'unknown',
    ];
}, $outputs->toArray());

$boxes = array_filter($boxes);

foreach ($boxes as $box) {
    $image->drawRectangle(
        xMin: (int)$box['xmin'],
        yMin: (int)$box['ymin'],
        xMax: (int)$box['xmax'],
        yMax: (int)$box['ymax'],
        color: '0099FF',
        thickness: 2
    );
    $image->drawText(
        text: $box['label'],
        xPos: (int)$box['xmin'],
        yPos: (int)max($box['ymin'] - 5, 0),
        fontFile: '/Users/Kyrian/Library/Fonts/JosefinSans-Bold.ttf',
        fontSize: 14,
        color: '0099FF'
    );
}

$image->save(__DIR__.'/../images/corgi-detected.jpg');

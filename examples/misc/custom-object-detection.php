<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\Auto\AutoModel;
use Codewithkyrian\Transformers\Processors\AutoProcessor;
use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\Transformers\Utils\Image;
use Codewithkyrian\Transformers\Utils\ImageDriver;

require_once './bootstrap.php';

ini_set('memory_limit', '-1');

Transformers::setup()->setImageDriver(ImageDriver::GD);

$processor = AutoProcessor::fromPretrained('Xenova/yolov9-c_all');
$model = AutoModel::fromPretrained('Xenova/yolov9-c_all');

$image = Image::read(__DIR__ . '/../images/multitask.png');

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

$fontSize = 10;
$fontScalingFactor = 0.75;
$fontFile = '/Users/Kyrian/Library/Fonts/JosefinSans-Bold.ttf';
$labelBoxHeight = $fontSize * 2 * $fontScalingFactor;
$colors = array_map(fn() => sprintf('#%06x', mt_rand(0, 0xFFFFFF)), range(0, count($boxes) - 1));

foreach ($boxes as $box) {
    $detectionLabel = $box['label'] . '  ' . round($box['score'], 2);
    $color = $colors[array_search($box, $boxes)];

    $image = $image
        ->drawRectangle($box['xmin'], $box['ymin'], $box['xmax'], $box['ymax'], $color, thickness: 2)
        ->drawRectangle(
            xMin: $box['xmin'],
            yMin: max($box['ymin'] - $labelBoxHeight, 0),
            xMax: $box['xmin'] + (strlen($detectionLabel) * $fontSize * $fontScalingFactor),
            yMax: max($box['ymin'], $labelBoxHeight),
            color: $color,
            fill: true,
            thickness: 2
        )
        ->drawText(
            text: $detectionLabel,
            xPos: $box['xmin'] + 5,
            yPos: max($box['ymin'] - $labelBoxHeight, 0),
            fontFile: $fontFile,
            fontSize: $fontSize,
            color: 'FFFFFF'
        );
}

$image->save(__DIR__ . '/../images/corgi-detected.jpg');

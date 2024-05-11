<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

$img = imagecreatefromjpeg('images/carrots-oranges.jpeg');
$coco_labels = include 'coco_labels.php';
$modelPath = __DIR__ . '/models/ssd_mobilenet_v1_10.onnx';

$pixels = getPixels($img);

$model = new Codewithkyrian\Transformers\Utils\Model(__DIR__ . '/models/ssd_mobilenet_v1_10.onnx');
$result = $model->predict(['image_tensor:0' => [$pixels]]);

foreach ($result['num_detections:0'] as $idx => $n) {
    for ($i = 0; $i < $n; $i++) {
        $label = intval($result['detection_classes:0'][$idx][$i]);
        $label = $coco_labels[$label] ?? $label;

        echo sprintf(
            "Detected %s with %d%% confidence%s",
            $label,
            round($result['detection_scores:0'][$idx][$i], 4) * 100,
            PHP_EOL
        );
    }
}


function getPixels($img): array
{
    $width = imagesx($img);
    $height = imagesy($img);

    $pixels = [];

    for ($y = 0; $y < $height; $y++) {
        $row = [];
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($img, $x, $y);
            $colors = imagecolorsforindex($img, $rgb);
            $row[] = [$colors['red'], $colors['green'], $colors['blue']];
        }
        $pixels[] = $row;
    }

    return $pixels;
}


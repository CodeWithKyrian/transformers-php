<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Models\Auto\AutoModel;
use Codewithkyrian\Transformers\Processors\AutoProcessor;
use Codewithkyrian\Transformers\Utils\Image;
use function Codewithkyrian\Transformers\Utils\{memoryPeak, memoryUsage, timeUsage};

require_once './bootstrap.php';

$model = AutoModel::fromPretrained(modelNameOrPath: 'briaai/RMBG-1.4');
$processor = AutoProcessor::fromPretrained(modelNameOrPath: 'briaai/RMBG-1.4');

$url = __DIR__ . '/../images/woman-w-bag.jpeg';

$image = Image::read($url);

$fileName = pathinfo($url, PATHINFO_FILENAME);

['pixel_values' => $pixelValues] = $processor($image);

['output' => $output] = $model(['input' => $pixelValues]);
//
$mask = Image::fromTensor($output[0]->multiply(255))->resize($image->width(), $image->height());
//
$mask->save($fileName . '-mask.png');
//
$maskedImage = $image->applyMask($mask);
//
//$maskedImage->save($fileName . '-masked.png');
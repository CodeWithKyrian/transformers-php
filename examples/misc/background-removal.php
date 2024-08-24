<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Models\Auto\AutoModel;
use Codewithkyrian\Transformers\Processors\AutoProcessor;
use Codewithkyrian\Transformers\Utils\Image;
use function Codewithkyrian\Transformers\Utils\{memoryPeak, memoryUsage, timeUsage};

require_once './bootstrap.php';

$modelConfig = ['model_type' => 'custom'];
$processorConfig = [
    'do_normalize' => true,
    'do_pad' => false,
    'do_rescale' => true,
    'do_resize' => true,
    'image_mean' => [0.5, 0.5, 0.5],
    'feature_extractor_type' => "ImageFeatureExtractor",
    'image_std' => [1, 1, 1],
    'resample' => 2,
    'rescale_factor' => 0.00392156862745098,
    'size' => ['width' => 1024, 'height' => 1024],
];

$model = AutoModel::fromPretrained(modelNameOrPath: 'briaai/RMBG-1.4', config: $modelConfig);
$processor = AutoProcessor::fromPretrained(modelNameOrPath: 'briaai/RMBG-1.4', config: $processorConfig);

$url = __DIR__ . '/../images/woman-w-bag.jpeg';

$image = Image::read($url);

$fileName = pathinfo($url, PATHINFO_FILENAME);

['pixel_values' => $pixelValues] = $processor($image);

['output' => $output] = $model(['input' => $pixelValues]);

$mask = Image::fromTensor($output[0]->multiply(255))->resize($image->width(), $image->height());

$mask->save($fileName . '-mask.png');

$maskedImage = $image->applyMask($mask);

$maskedImage->save($fileName . '-masked.png');

dd('Done Processing!', timeUsage(), memoryUsage(), memoryPeak());


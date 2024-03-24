<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Processors\AutoProcessor;
use Codewithkyrian\Transformers\Utils\Image1;
use Codewithkyrian\Transformers\Utils\Image;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

$processor = AutoProcessor::fromPretrained('Xenova/vit-base-patch16-224');

$image = Image::read('images/kyrian-cartoon.jpeg');

$imageInputs = $processor($image);

dd($imageInputs['pixel_values']->shape(), $imageInputs['original_sizes'], $imageInputs['reshaped_input_sizes']);
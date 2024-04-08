<?php

declare(strict_types=1);


use Codewithkyrian\Transformers\Generation\Streamers\StdOutStreamer;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

$imageFeatureExtractor = pipeline('image-feature-extraction', 'Xenova/vit-base-patch16-224-in21k');

$url = __DIR__. '/../images/cats.jpg';

$features = $imageFeatureExtractor($url);

dd(count($features[0]), timeUsage(), memoryUsage());

<?php

declare(strict_types=1);

use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

ini_set('memory_limit', -1);
$captioner = pipeline('image-to-text', 'Xenova/vit-gpt2-image-captioning');
//$captioner = pipeline('image-to-text', 'Xenova/trocr-small-handwritten');

//$streamer = StdOutStreamer::make($captioner->tokenizer);

$url = __DIR__ . '/../images/beach.png';
//$url = __DIR__. '/../images/handwriting.jpg';
//$url = __DIR__. '/../images/handwriting3.png';
//$url = __DIR__ . '/../images/handwriting4.jpeg';

$output = $captioner($url);

dd($output, timeUsage(), memoryUsage());
<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use Codewithkyrian\Transformers\Generation\Streamers\StdOutStreamer;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

ini_set('memory_limit', -1);

$generator = pipeline('text-generation', 'Xenova/distilgpt2');

$streamer = StdOutStreamer::make($generator->tokenizer);

$output = $generator('I enjoy walking with my cute dog,', streamer: $streamer, maxNewTokens: 50, temperature: 2);

dd($output, timeUsage(), memoryUsage());

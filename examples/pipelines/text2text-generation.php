<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use Codewithkyrian\Transformers\Generation\Streamers\StdOutStreamer;
use function Codewithkyrian\Transformers\{Pipelines\pipeline, Utils\memoryUsage, Utils\timeUsage};

ini_set('memory_limit', -1);

$generator = pipeline('text2text-generation', 'Xenova/LaMini-Flan-T5-783M');

$streamer = StdOutStreamer::make($generator->tokenizer);

$query = 'Please let me know your thoughts on the given place and why you think it deserves to be visited: \n"Barcelona, Spain"';
//$query = 'What is the capital of Nigeria? When was it changed from Lagos?';
//$query = 'In 5 steps, give me a guide on how to make a simple cake.';

$output = $generator($query, streamer: $streamer, maxNewTokens: 256, doSample: true);

dd('Done', timeUsage(), memoryUsage());
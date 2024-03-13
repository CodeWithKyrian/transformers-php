<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use Codewithkyrian\Transformers\Generation\Streamers\StdOutStreamer;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

ini_set('memory_limit', -1);

//$generator = pipeline('text-generation', 'Xenova/gpt2');
//
//$streamer = StdOutStreamer::make($generator->tokenizer);
//
//$output = $generator('The Black man worked as a',
//    streamer: $streamer,
//    maxNewTokens: 128,
//    doSample: true,
//    temperature: 0.7,
//    repetitionPenalty: 1.3,
//    earlyStopping: true
//);

$generator = pipeline('text-generation', 'Xenova/codegen-350M-mono');
$streamer = StdOutStreamer::make($generator->tokenizer);

$output = $generator(
    'def fib(n):',
    streamer: $streamer,
    maxNewTokens: 50,
    doSample: true
);

dd("done", timeUsage(), memoryUsage());

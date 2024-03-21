<?php

declare(strict_types=1);

require_once './bootstrap.php';

use Codewithkyrian\Transformers\Generation\Streamers\StdOutStreamer;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

ini_set('memory_limit', -1);
//
$generator = pipeline('text-generation', 'Xenova/Qwen1.5-0.5B-Chat');

$streamer = StdOutStreamer::make($generator->tokenizer);

$messages = [
    ['role' => 'user', 'content' => 'Hello!'],
    ['role' => 'assistant', 'content' => 'Hi! How are you?'],
    ['role' => 'user', 'content' => 'I am doing great. What about you?'],
];

$input = $generator->tokenizer->applyChatTemplate($messages, addGenerationPrompt: true, tokenize: false);

$output = $generator($messages,
    streamer: $streamer,
    maxNewTokens: 128,
    doSample: true,
    temperature: 0.7,
    repetitionPenalty: 1.3,
    earlyStopping: true
);

//$generator = pipeline('text-generation', 'Xenova/codegen-350M-mono');
//$streamer = StdOutStreamer::make($generator->tokenizer);
//
//$output = $generator(
//    'def fib(n):',
//    streamer: $streamer,
//    maxNewTokens: 100,
//    doSample: true
//);

dd("done", timeUsage(), memoryUsage());

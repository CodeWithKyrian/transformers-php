<?php

declare(strict_types=1);

require_once './bootstrap.php';

use Codewithkyrian\Transformers\Generation\Streamers\StdOutStreamer;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

ini_set('memory_limit', -1);
//
//$generator = pipeline('text-generation', 'Xenova/gpt2');
$generator = pipeline('text-generation', 'Xenova/Qwen1.5-0.5B-Chat');

$streamer = StdOutStreamer::make();

$messages = [
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'What is the product of 5 and 4'],
];

$input = $generator->tokenizer->applyChatTemplate($messages, addGenerationPrompt: true, tokenize: false);

$output = $generator($input,
    streamer: $streamer,
    maxNewTokens: 128,
    doSample: true,
    returnFullText: false,
//    temperature: 0.7,
//    repetitionPenalty: 1.3,
//    earlyStopping: true
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

dd($output[0]['generated_text'], timeUsage(), memoryUsage());

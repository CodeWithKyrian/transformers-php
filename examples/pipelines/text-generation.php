<?php

declare(strict_types=1);

require_once './bootstrap.php';

use Codewithkyrian\Transformers\Generation\Streamers\StdOutStreamer;
use Codewithkyrian\Transformers\Generation\Streamers\TextStreamer;
use function Codewithkyrian\Transformers\Pipelines\pipeline;
use function Codewithkyrian\Transformers\Utils\memoryUsage;
use function Codewithkyrian\Transformers\Utils\timeUsage;

ini_set('memory_limit', -1);

//$generator = pipeline('text-generation', 'Xenova/gpt2');
//$generator = pipeline('text-generation', 'Xenova/Qwen1.5-0.5B-Chat');
//$generator = pipeline('text-generation', 'Xenova/TinyLlama-1.1B-Chat-v1.0');
$generator = pipeline('text-generation', 'onnx-community/Llama-3.2-1B-Instruct', modelFilename: 'model_q4');

$streamer = TextStreamer::make()->shouldSkipPrompt();

$messages = [
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'What is diffusion?'],
];

$input = $generator->tokenizer->applyChatTemplate($messages, addGenerationPrompt: true, tokenize: false);

$output = $generator($input,
    streamer: $streamer,
    maxNewTokens: 256,
    doSample: true,
    returnFullText: false,
//    temperature: 0.7,
//    repetitionPenalty: 1.3,
//    earlyStopping: true
);

//$generator = pipeline('text-generation', 'Xenova/codegen-350M-mono');
//$streamer = TextStreamer::make();

//$output = $generator(
//    'def fib(n):',
//    streamer: $streamer,
//    maxNewTokens: 100,
//    doSample: true,
//    returnFullText: true,
//);

dd($output[0]['generated_text'], timeUsage(), memoryUsage());

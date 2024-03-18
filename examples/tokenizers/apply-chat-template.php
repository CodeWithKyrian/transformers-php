<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\PretrainedTokenizers\AutoTokenizer;

require_once './bootstrap.php';

//$tokenizer = AutoTokenizer::fromPretrained('mistralai/Mistral-7B-Instruct-v0.1');
$tokenizer = AutoTokenizer::fromPretrained('facebook/blenderbot-400M-distill');
$messages = [
    ['role' => 'user', 'content' => 'Hello!'],
    ['role' => 'assistant', 'content' => 'Hi! How are you?'],
    ['role' => 'user', 'content' => 'I am doing great.'],
    ['role' => 'assistant', 'content' => 'That is great to hear.'],
];

$text = $tokenizer->applyChatTemplate($messages, addGenerationPrompt: true, tokenize: false);

dd($text);
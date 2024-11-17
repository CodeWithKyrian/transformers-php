<?php

declare(strict_types=1);

namespace Tests;

use Codewithkyrian\Transformers\PretrainedTokenizers\AutoTokenizer;
use Codewithkyrian\Transformers\Transformers;

ini_set('memory_limit', -1);

beforeAll(function () {
    Transformers::setup()
        ->setCacheDir('tests/models')
        ->apply();
});

describe('Tokenizers (dynamic)', function () {
    it('can tokenize a text', function ($data) {
        ['tokenizerId' => $tokenizerId, 'test' => $test] = $data;

        $tokenizer = AutoTokenizer::fromPretrained($tokenizerId);

        if (is_string($test['input']))
        {
            // Tokenize the input text
            $encoded = $tokenizer($test['input'], returnTensor: false);

             // Add the input text to the encoded object for easier debugging
            $test['encoded']['input'] = $encoded['input'] = $test['input'];

            expect($encoded)->toMatchArray($test['encoded']);

            // Skip decoding tests if encoding produces zero tokens
            if (count($encoded['input_ids']) === 0) {
                return;
            }

            $decodedWithSpecial = $tokenizer->decode($encoded['input_ids'], skipSpecialTokens: false);
            expect($decodedWithSpecial)->toBe($test['decoded_with_special']);

            $decodedWithoutSpecial = $tokenizer->decode($encoded['input_ids'], skipSpecialTokens: true);
            expect($decodedWithoutSpecial)->toBe($test['decoded_without_special']);
        } else{

            ['text' => $text, 'text_pair' => $textPair] = $test['input'];

            $encoded = $tokenizer($text, $textPair, returnTensor: false);

            expect($encoded)->toMatchArray($test['output']);
        }
    })
    ->with('regular-tokenization');
});

describe('Chat templates', function () {
    it('can apply a chat template', function () {
        $tokenizer = AutoTokenizer::fromPretrained("Xenova/mistral-tokenizer-v1");

        $chat = [
            ['role' => 'user', 'content' => 'Hello, how are you?'],
            ['role' => 'assistant', 'content' => "I'm doing great. How can I help you today?"],
            ['role' => 'user', 'content' => "I'd like to show off how chat templating works!"],
        ];

        $text = $tokenizer->applyChatTemplate($chat, tokenize: false);

        expect($text)
            ->toEqual("<s>[INST] Hello, how are you? [/INST]I'm doing great. How can I help you today?</s> [INST] I'd like to show off how chat templating works! [/INST]");

        $inputIds = $tokenizer->applyChatTemplate($chat, returnTensor: false);

        expect($inputIds)
            ->toBe([1, 733, 16289, 28793, 22557, 28725, 910, 460, 368, 28804, 733, 28748, 16289, 28793, 28737, 28742, 28719, 2548, 1598, 28723, 1602, 541, 315, 1316, 368, 3154, 28804, 2, 28705, 733, 16289, 28793, 315, 28742, 28715, 737, 298, 1347, 805, 910, 10706, 5752, 1077, 3791, 28808, 733, 28748, 16289, 28793]);
    });

    it('should support user-defined chat template', function () {
        $tokenizer = AutoTokenizer::fromPretrained("Xenova/llama-tokenizer");

        $chat = [
            ['role' => 'user', 'content' => 'Hello, how are you?'],
            ['role' => 'assistant', 'content' => "I'm doing great. How can I help you today?"],
            ['role' => 'user', 'content' => "I'd like to show off how chat templating works!"],
        ];

        $chatTemplate = "{% if messages[0]['role'] == 'system' %}".
            "{% set loop_messages = messages[1:] %}".
            "{% set system_message = messages[0]['content'] %}".
            "{% elif USE_DEFAULT_PROMPT == true and not '<<SYS>>' in messages[0]['content'] %}".
            "{% set loop_messages = messages %}".
            "{% set system_message = 'DEFAULT_SYSTEM_MESSAGE' %}".
            "{% else %}".
            "{% set loop_messages = messages %}".
            "{% set system_message = false %}".
            "{% endif %}".
            "{% if loop_messages|length == 0 and system_message %}".
            "{{ bos_token + '[INST] <<SYS>>\\n' + system_message + '\\n<</SYS>>\\n\\n [/INST]' }}".
            "{% endif %}".
            "{% for message in loop_messages %}".
            "{% if (message['role'] == 'user') != (loop.index0 % 2 == 0) %}".
            "{{ raise_exception('Conversation roles must alternate user/assistant/user/assistant/...') }}".
            "{% endif %}".
            "{% if loop.index0 == 0 and system_message != false %}".
            "{% set content = '<<SYS>>\\n' + system_message + '\\n<</SYS>>\\n\\n' + message['content'] %}".
            "{% else %}".
            "{% set content = message['content'] %}".
            "{% endif %}".
            "{% if message['role'] == 'user' %}".
            "{{ bos_token + '[INST] ' + content.strip() + ' [/INST]' }}".
            "{% elif message['role'] == 'system' %}".
            "{{ '<<SYS>>\\n' + content.strip() + '\\n<</SYS>>\\n\\n' }}".
            "{% elif message['role'] == 'assistant' %}".
            "{{ ' '  + content.strip() + ' ' + eos_token }}".
            "{% endif %}".
            "{% endfor %}";

        $chatTemplate = str_replace('USE_DEFAULT_PROMPT', 'true', $chatTemplate);
        $chatTemplate = str_replace('DEFAULT_SYSTEM_MESSAGE', 'You are a helpful, respectful and honest assistant.', $chatTemplate);

        $text = $tokenizer->applyChatTemplate($chat, chatTemplate: $chatTemplate, tokenize: false, returnTensor: false);

        expect($text)->toEqual("<s>[INST] <<SYS>>\nYou are a helpful, respectful and honest assistant.\n<</SYS>>\n\nHello, how are you? [/INST] I'm doing great. How can I help you today? </s><s>[INST] I'd like to show off how chat templating works! [/INST]");
    });
});

describe('Chat templates (dynamic)', function () {
    it('can tokenize with chat template', function ($data) {
        ['tokenizerId' => $tokenizerId, 'test' => $test] = $data;

        $tokenizer = AutoTokenizer::fromPretrained($tokenizerId);

        $generated = $tokenizer->applyChatTemplate(
            $test['messages'],
            addGenerationPrompt: $test['add_generation_prompt'],
            tokenize: $test['tokenize'],
            returnTensor: false
        );

        expect($generated)->toEqual($test['target']);
    })->with('template-tokenization');
});

describe('Tokenizer padding/truncation', function () {
    $inputs = ['a', 'b c'];

    it('should create a jagged array', function () use ($inputs) {
        $tokenizer = AutoTokenizer::fromPretrained('Xenova/bert-base-uncased');

        // Support jagged array if `returnTensor` is false
        $output = $tokenizer->tokenize($inputs, returnTensor: false);

        $expected = [
            'input_ids' => [[101, 1037, 102], [101, 1038, 1039, 102]],
            'attention_mask' => [[1, 1, 1], [1, 1, 1, 1]],
            'token_type_ids' => [[0, 0, 0], [0, 0, 0, 0]]
        ];

        expect($output)->toBe($expected);

        // Truncation
        $output = $tokenizer->tokenize($inputs, addSpecialTokens: false, truncation: true, returnTensor: false);

        $expected = [
            'input_ids' => [[1037], [1038, 1039]],
            'attention_mask' => [[1], [1, 1]],
            'token_type_ids' => [[0], [0, 0]]
        ];

        expect($output)->toBe($expected);
    });

    it('should create a tensor', function () use ($inputs) {
        $tokenizer = AutoTokenizer::fromPretrained('Xenova/bert-base-uncased');

        // Expected to throw error if jagged array
        expect(fn () => $tokenizer->tokenize($inputs))->toThrow('Unable to create tensor');

        // Truncation
        ['input_ids' => $inputIds, 'attention_mask' => $attentionMask, 'token_type_ids' => $tokenTypeIds] = $tokenizer
            ->tokenize($inputs, addSpecialTokens: false, truncation: true, maxLength: 1);

        expect($inputIds->toArray())->toBe([[1037], [1038]])
            ->and($attentionMask->toArray())->toBe([[1], [1]])
            ->and($tokenTypeIds->toArray())->toBe([[0], [0]]);

        // Padding
        ['input_ids' => $inputIds, 'attention_mask' => $attentionMask, 'token_type_ids' => $tokenTypeIds] = $tokenizer
            ->tokenize($inputs, padding: true, addSpecialTokens: false);

        expect($inputIds->toArray())->toBe([[1037, 0], [1038, 1039]])
            ->and($attentionMask->toArray())->toBe([[1, 0], [1, 1]])
            ->and($tokenTypeIds->toArray())->toBe([[0, 0], [0, 0]]);


        $textPair = ['d e', 'f g h'];

        // Padding with text pair
        ['input_ids' => $inputIds, 'attention_mask' => $attentionMask, 'token_type_ids' => $tokenTypeIds] = $tokenizer
            ->tokenize($inputs, textPair: $textPair, padding: true, addSpecialTokens: false);

        expect($inputIds->toArray())->toBe([[1037, 1040, 1041, 0, 0], [1038, 1039, 1042, 1043, 1044]])
            ->and($attentionMask->toArray())->toBe([[1, 1, 1, 0, 0], [1, 1, 1, 1, 1]])
            ->and($tokenTypeIds->toArray())->toBe([[0, 1, 1, 0, 0], [0, 0, 1, 1, 1]]);

        // Truncation + padding
        ['input_ids' => $inputIds, 'attention_mask' => $attentionMask, 'token_type_ids' => $tokenTypeIds] = $tokenizer
            ->tokenize(['a', 'b c', 'd e f'], padding: true, addSpecialTokens: false, truncation: true, maxLength: 2);

        expect($inputIds->toArray())->toBe([[1037, 0], [1038, 1039], [1040, 1041]])
            ->and($attentionMask->toArray())->toBe([[1, 0], [1, 1], [1, 1]])
            ->and($tokenTypeIds->toArray())->toBe([[0, 0], [0, 0], [0, 0]]);
    });
});

<?php

dataset('regular-tokenization', function () {
    $data = json_decode(file_get_contents(__DIR__.'/dataset-regular.json'), true);

    foreach ($data as $tokenizerId => $tests) {
        foreach ($tests as $test) {
            $label = is_string($test['input']) ? $test['input'] : json_encode($test['input']);
            yield "$tokenizerId: $label" => fn () => [
                'tokenizerId' => $tokenizerId,
                'test' => $test
            ];
        }
    }
});

dataset('template-tokenization', function () {
    $data = json_decode(file_get_contents(__DIR__.'/dataset-templates.json'), true);

    foreach ($data as $tokenizerId => $tests) {
        foreach ($tests as $test) {
            $printableKeys = ['add_generation_prompt', 'tokenize'];
            $label = json_encode(array_intersect_key($test, array_flip($printableKeys)));
            yield "$tokenizerId: $label" => fn () => [
                'tokenizerId' => $tokenizerId,
                'test' => $test
            ];
        }
    }
});

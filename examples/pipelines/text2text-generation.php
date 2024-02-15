<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use function Codewithkyrian\Transformers\pipeline;

$generator = pipeline('text2text-generation', 'Xenova/flan-t5-small');

$output = $generator('how can I become more healthy?', max_new_tokens: 100);

dd($output);

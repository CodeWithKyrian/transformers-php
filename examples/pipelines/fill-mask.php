<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use function Codewithkyrian\Transformers\pipeline;

$pipeline = pipeline('fill-mask', 'Xenova/bert-base-uncased');

$result = $pipeline('The quick brown [MASK] jumps over the lazy dog.');

dd($result);


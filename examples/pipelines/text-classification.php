<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use function Codewithkyrian\Transformers\pipeline;

$classifier = pipeline('text-classification', 'Xenova/toxic-bert');

$result = $classifier("I hate you! You gave me life but in misery", topk: -1);
//$result2 = $classifier("She's so beautiful! I can't stop looking", topk: -1);

dd($result);




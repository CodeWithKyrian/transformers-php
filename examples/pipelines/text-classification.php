<?php

declare(strict_types=1);

use function Codewithkyrian\Transformers\Pipelines\pipeline;

require_once './bootstrap.php';


$classifier = pipeline('text-classification', 'Xenova/toxic-bert');
//
//$result = $classifier("I hate you! You gave me life but in misery", topK: -1);


// $classifier = pipeline('text-classification', 'Xenova/distilbert-base-uncased-mnli');

$result = $classifier('I want to beat him to pulp', topK: -1);

dd($result);

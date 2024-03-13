<?php

declare(strict_types=1);

use function Codewithkyrian\Transformers\Pipelines\pipeline;

require_once './vendor/autoload.php';


$classifier = pipeline('sentiment-analysis', 'Xenova/distilbert-base-uncased-finetuned-sst-2-english');

$result1 = $classifier(['I love transformers!', 'I hate transformers!']);
//$result2 = $classifier("I hate him, he's very terrible.");

dd($result1);


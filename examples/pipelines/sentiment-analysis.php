<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use function Codewithkyrian\Transformers\pipeline;

$classifier = pipeline('sentiment-analysis', 'Xenova/distilbert-base-uncased-finetuned-sst-2-english');

$result1 = $classifier("I love her, she's a great friend.");
$result2 = $classifier("I hate him, he's very terrible.");

dd($result1, $result2);


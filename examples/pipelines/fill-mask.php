<?php

declare(strict_types=1);

use function Codewithkyrian\Transformers\Pipelines\pipeline;

require_once './vendor/autoload.php';


//$pipeline = pipeline('fill-mask', 'Xenova/bert-base-uncased');
//$result = $pipeline('My name is Kyrian and I am a [MASK] developer.');


$pipeline = pipeline('fill-mask', 'Xenova/roberta-base');
$result = $pipeline('My name is Kyrian and I am a <mask> developer.');


dd($result);


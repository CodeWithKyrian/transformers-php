<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use function Codewithkyrian\Transformers\pipeline;

$question = "What is a good example of a question answering dataset?";

$context = "Extractive Question Answering is the task of extracting an answer from a text given a question. An example
of a question answering dataset is the SQuAD dataset, which is entirely based on that task. If you would like to
fine-tune a model on a SQuAD task, you may leverage the examples/pytorch/question-answering/run_squad.py script.";

$pipeline = pipeline('question-answering', 'Xenova/distilbert-base-cased-distilled-squad');

$result = $pipeline($question, $context);

dd($result);



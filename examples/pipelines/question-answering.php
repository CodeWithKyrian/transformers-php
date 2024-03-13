<?php

declare(strict_types=1);

use function Codewithkyrian\Transformers\Pipelines\pipeline;

require_once './bootstrap.php';


//$question = "What is a good example of a question answering dataset?";
//
//$context = "Extractive Question Answering is the task of extracting an answer from a text given a question. An example
//of a question answering dataset is the SQuAD dataset, which is entirely based on that task. If you would like to
//fine-tune a model on a SQuAD task, you may leverage the examples/pytorch/question-answering/run_squad.py script.";
//
//$pipeline = pipeline('question-answering', 'Xenova/distilbert-base-cased-distilled-squad');

$question = "Who is known as the father of computers?";

$context = "The history of computing is longer than the history of computing hardware and modern computing technology 
and includes the history of methods intended for pen and paper or for chalk and slate, with or without the aid of tables. 
Charles Babbage is often regarded as one of the fathers of computing because of his contributions to the basic design of 
the computer through his analytical engine.";

$pipeline = pipeline('question-answering', 'Xenova/distilbert-base-cased-distilled-squad');

$result = $pipeline($question, $context, topK: 3);

dd($result);



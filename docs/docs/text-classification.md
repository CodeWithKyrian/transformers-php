---
outline: deep
---

# Text Classification/Sentiment Analysis

Text Classification, including sentiment analysis, is a fundamental task in natural language processing (NLP) where a
label or class is assigned to a given text. This classification depends on the labels defined during the model's
training phase, meaning the final output is contingent upon the specific model used. The features can be anything from
the words
themselves to the context in which they are used.

## Task ID

- `text-classification`
- `sentiment-analysis`

## Default Model

- `Xenova/distilbert-base-uncased-finetuned-sst-2-english`.

## Use Cases

Text Classification can be applied in various scenarios, including but not limited to:

- **Sentiment Analysis:** Determining the emotional tone behind a series of words to gain an understanding of the
  attitudes,
  opinions, and emotions expressed.
- **Natural Language Inference:** Identifying if a given hypothesis is true (entailment), false (contradiction), or
  undetermined (neutral) based on a given premise.
- **Assessing Grammatical Correctness:** Evaluating the grammatical accuracy of text.
- **Customer Feedback Analysis:** Analyzing customer reviews and feedback for sentiment to gauge overall customer
  satisfaction and identify areas for improvement.

## Running an Inference Session

Here's how to perform text classification or sentiment analysis using the pipeline:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$classifier = pipeline('sentiment-analysis');
$result = $classifier("I love Transformers PHP!");
```

::: details Click to view output
```php
['label' => 'POSITIVE',  'score' => 0.9995358059835]
```
:::


## Pipeline Input Options

When running the `text-classification` pipeline, you can the following options:

- ### `texts` *(string|array)*
  The sentence(s) to classify. It's the first argument so there's no need to pass it as a named argument. You can pass a
  single string or an array of strings. When passing an array, the pipeline will return predictions for each sentence in
  the array.

  ```php
  $result = $classifier(['I love Transformers PHP!', 'I hate Transformers PHP!']);
  ```

- ### `topK` *(int)*
  [Optional] The number of classification labels to return. By default, it returns the best classification. Set to a specific
  number to receive that many top classifications, or use -1 to obtain all classifications from the model.

  ```php
  $result = $classifier("I love Transformers PHP!", topK: 3);
  ```

  ::: details Click to view output
  ```php
  [
     ['label' => 'POSITIVE',  'score' => 0.9995358059835],
     ['label' => 'NEGATIVE',  'score' => 0.0004641940165],
     ['label' => 'NEUTRAL',  'score' => 0.0000000000000],
   ]
  ```
  :::


## Pipeline Outputs

The output of the pipeline is an array containing the classification label and the confidence score. The confidence
score
is a value between 0 and 1, with 1 being the highest confidence. Since the actual labels depend on the model, it's
crucial
to consult the model's documentation for the specific labels it uses. Here are examples demonstrating how outputs might
differ:

### Natural Language Inference (NLI)

Natural Language Inference (NLI) is a task that involves determining the logical relationship between two sentences. The
relationship can be one of three types: entailment, contradiction, or neutral. Here's how to perform NLI using the
pipeline:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$nli = pipeline('text-classification', 'Xenova/roberta-large-mnli');

$result = $nli('A person is eating, there is no food left');
```

::: details Click to view output
```php
['label' => 'contradiction',  'score' => 0.9595358059835]
```
:::


### Question Natural Language Inference (QNLI)

Similar to NLI, Question Natural Language Inference (QNLI) involves determining the logical relationship between two
sentences. However, QNLI is specifically designed for question-answering tasks. Here's how to perform QNLI using the
pipeline:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$qnli = pipeline('text-classification', 'Xenova/qnli-electra-base');

$result = $qnli('Where is the capital of Nigeria?', 'The capital of Nigeria is Abuja.');
```

::: details Click to view output
```php
['label' => 'entailment',  'score' => 0.9995358059835]
```
:::

### Sentiment Analysis

Sentiment Analysis involves determining the emotional tone behind a series of words to gain an understanding of the
attitudes, opinions, and emotions expressed. Here's how to perform sentiment analysis using the pipeline:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$sentiment = pipeline('sentiment-analysis');

$result = $sentiment('I love Transformers PHP!');
```

::: details Click to view output
```php
['label' => 'POSITIVE',  'score' => 0.9995358059835]
```
:::

### Grammatical Correctness

Grammatical Correctness involves evaluating the grammatical accuracy of text. The labels for grammatical correctness
are typically binary, with the options being `acceptable` and `unacceptable`. Here's how to perform grammatical
correctness
analysis using the pipeline:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$grammar = pipeline('text-classification', 'codewithkyrian/bert-base-uncased-rotten-tomatoes'); // convert to ONNX

$result = $grammar('I will not be able to attended the meeting because I am sick.');
```

::: details Click to view output
```php
['label' => 'unacceptable',  'score' => 0.9995358059835]
```
:::








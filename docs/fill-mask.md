---
outline: deep
---

# Fill Mask

The fill-mask task is a task where the model is given a sentence with a masked word and is expected to predict the
masked word. This task is also known as cloze test. The fill-mask task is a zero-shot task, meaning that the model is
not trained on the specific masked word, but is expected to predict it based on the context of the sentence.

## Task ID

- `fill-mask`

## Default Model

- `Xenova/bert-base-uncased`

## Use Cases

The fill-mask task can be applied in various scenarios, including but not limited to:

- **Language Learning:** Creating exercises where learners guess missing words in sentences.
- **Content Generation:** Assisting writers by suggesting words to complete sentences.
- **Text Data Augmentation:**  Generating new sentences for training datasets by masking and predicting different parts
  of existing sentences.

## Running an Inference Session

To use the Fill-Mask pipeline, specify the task as fill-mask and optionally choose a model. Here’s how to run an
inference session:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$fillMask = pipeline('fill-mask');

$result = $fillMask('The quick brown <mask> jumps over the lazy dog.');
```

## Pipeline Input Options

When running the `fill-mask` pipeline, you can the following options:

- ### `texts` *(string|array)*
  The sentence(s) with the masked word. It's the first argument so there's no need to pass it as a named argument. You
  can pass a single string or an array of strings. When passing an array, the pipeline will return predictions for each
  sentence in the array. The mask token included in the input must match the mask token used by the model. For instance,
  RoBERTa-based models use `<mask>`, while BERT-based models use [MASK]. An incorrect or missing mask token will result
  in an exception.

  ```php
  $result = $fillMask(['My name is Kyrian and I am a <mask> developer.', 'I am a <mask> developer.']);
  ```

- ### `topK` *(int)*
  [Optional] The number of predictions to return. This default to 5 when not specified. You can set it to a specific number to
  receive that many top predictions, or use -1 to obtain all predictions from the model.

  ```php
  $result = $fillMask('My name is Kyrian and I am a <mask> developer.', topK: 2);
  ```

  ::: details Click to view output
  ```php
  [
        ['sequence' => 'my name is kyrian and i am a software developer.', 'score' => 0.9995354418754578, 'token' => 50264, 'token_str' => 'software'],
        ['sequence' => 'my name is kyrian and i am a web developer.', 'score' => 0.9604645581245422368, 'token' => 4773, 'token_str' => 'web'],
  ]
  ```
  :::

## Pipeline Outputs

The output of the pipeline is an array containing the predicted word, the confidence score, the token ID of the
predicted word, and the full sequence with the predicted word. The confidence score is a value between 0 and 1, with 1
being the highest confidence. The token ID is the unique identifier of the predicted word in the model's vocabulary.

```php
[
      ['sequence' => 'my name is kyrian and i am a software developer.', 'score' => 0.9995354418754578, 'token' => 50264, 'token_str' => 'software'],
      ['sequence' => 'my name is kyrian and i am a web developer.', 'score' => 0.9604645581245422368, 'token' => 4773, 'token_str' => 'web'],
      // ... additional sequences depending on the topK
]
```

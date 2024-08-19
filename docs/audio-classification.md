---
outline: deep
---

# Audio Classification <Badge type="tip" text="^0.5.0" />

Audio classification involves assigning a label or class to an audio input. It can be used to recognize commands,
identify speakers, or detect emotions in speech. The model processes the audio and returns a classification label with a
corresponding confidence score.

## Task ID

- `audio-classification`

## Default Model

- `Xenova/wav2vec2-base-superb-ks`

## Use Cases

Audio classification models have a wide range of applications, including:

- **Command Recognition:** Classifying utterances into a predefined set of commands, often done on-device for fast
  response times.
- **Language Identification:** Detecting the language spoken in the audio.
- **Emotion Recognition:** Analyzing speech to identify the emotion expressed by the speaker.
- **Speaker Identification:** Determining the identity of the speaker from a set of known voices.

## Running an Inference Session

Here's how to perform audio classification using the pipeline:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$classifier = pipeline('audio-classification', 'Xenova/ast-finetuned-audioset-10-10-0.4593');

$audioUrl = __DIR__ . '/../sounds/cat_meow.wav';

$output = $classifier($audioUrl, topK: 4);
```

::: details Click to view output

```php
[
    ['label' => 'Cat Meow',  'score' => 0.8456],
    ['label' => 'Domestic Animal',  'score' => 0.1234],
    ['label' => 'Pet',  'score' => 0.0987],
    ['label' => 'Mammal',  'score' => 0.0567]
]
```

:::

## Pipeline Input Options

When running the `audio-classification` pipeline, you can use the following options:

- ### `inputs` *(string)*
  The audio file(s) to classify. It can be a local file path, a file resource, a URL to an audio file (local or remote),
  or an array of these inputs. It's the first argument, so there's no need to pass it as a named argument.

  ```php
      $output = $classifier('https://example.com/audio.wav');
  ```

- ### `topK` *(int)*
  The number of top labels to return. The default is `1`.

  ```php
      $output = $classifier('https://example.com/audio.wav', topK: 4);
  ```

  ::: details Click to view output

  ```php
  [
    ['label' => 'Cat Meow',  'score' => 0.8456],
    ['label' => 'Domestic Animal',  'score' => 0.1234],
    ['label' => 'Pet',  'score' => 0.0987],
    ['label' => 'Mammal',  'score' => 0.0567]
  ]
  ```

  :::

## Pipeline Outputs

The output of the pipeline is an array containing the classification label and the confidence score. The confidence
score is a value between 0 and 1, with 1 being the highest confidence.

Since the actual labels depend on the model, it's crucial to consult the model's documentation for the specific labels
it uses. Here are examples demonstrating how outputs might differ:

For a single audio file:

```php
['label' => 'Dog Barking',  'score' => 0.9321]
```

For multiple audio files:

```php
[
    ['label' => 'Dog Barking',  'score' => 0.9321],
    ['label' => 'Car Horn',  'score' => 0.8234],
    ['label' => 'Siren',  'score' => 0.7123]
]
```

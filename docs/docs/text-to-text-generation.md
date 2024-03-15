---
outline: deep
---

# Text-to-Text Generation

Text-to-Text Generation, or Sequence-to-Sequence Modeling, transforms one piece of text into another through an
encoder-decoder architecture. It's versatile, supporting tasks like language translation and summarization, making it
foundational for many NLP applications.

## Task ID

- `text2text-generation`

## Default Model

- `Xenova/flan-t5-small`

## Use Cases

Text-to-Text Generation is applicable in various scenarios, including but not limited to:

- **Language Translation:** Translating text from one language to another (it's recommended to use the [translation](/docs/translation)
  pipeline for this task though).
- **Summarization:** Condensing long documents into shorter versions while retaining the essential information (it's
  recommended to use the [summarization](/docs/summarization) pipeline for this task though).
- **Content Generation:** Creating new content based on given prompts or rewriting existing text.

## Running a Pipeline Session

To use the Text-to-Text Generation pipeline, you'll need to provide a piece of text. Here's an example:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$generator = pipeline('text2text-generation', 'Xenova/flan-t5-small');

$output = $generator('Please let me know your thoughts on the given place and why you think it deserves to be visited: \n"Barcelona, Spain"'');
```

## Pipeline Input Options

When running the `text2text-generation` pipeline, you can the following options:

- ### `texts` *(string|array)*
  The input text to transform. It's the first argument so there's no need to pass it as a named argument. You can pass a
  single string or an array of strings. When passing an array, the pipeline will return predictions for each sentence in
  the array.

  ```php
  $output = $generator(['Translate this text to French: "Hello, how are you?"', 'What is the capital of Nigeria?']);
  ```

- ### `streamer` *(Streamer)*
  [Optional] This is an instance of the `Streamer` class and is used to stream the output of the pipeline. It's useful
  when you want to process the output in real-time or when the output is too large to fit into memory. Visit the
  [Streamers](/docs/generation#streamers) documentation for more information on how to use streamers.

    ```php
    use Codewithkyrian\Transformers\Generation\Streamers\StdOutStreamer;
    use function Codewithkyrian\Transformers\Pipelines\pipeline;
  
    $generator = pipeline('text2text-generation', 'Xenova/LaMini-Flan-T5-783M');
  
    $streamer = StdOutStreamer::make($generator->tokenizer);
  
    $output = $generator('What is the capital of Nigeria?', streamer: $streamer);
    ```

- ### `maxNewTokens` *(int)*
  Sets the maximum number of tokens to generate, irrespective of the prompt's length.

- ### `doSample` *(bool)*
  Toggles between sampling (true) and greedy decoding (false) for generating tokens. For more information on sampling
  strategies,
  check out this article from HuggingFace on [how to generate](https://huggingface.co/blog/how-to-generate).

- ### `numBeams` *(bool)*
  Determines the beam search size. A setting of 1 disables beam search.

- ### `temperature` *(float)*
  Adjusts the probability distribution of the next token to make generation more deterministic or more random. A
  temperature
  of 1.0 makes the generation more random, while a temperature of 0.0 makes the generation more deterministic.

- ### `repetitionPenalty` *(float)*
  Penalizes repeating the same token, where 1.0 implies no penalty. This can be used to prevent the model from repeating
  the same token in its output and is especially useful for beam search.

`maxNewTokens`, `doSample`, `numBeams`, `temperature`, and `repetitionPenalty` are few out of the many possible
arguments used to either control the length of the output, or the generation strategy used, or the manipulation process
for the output logits, or the nature of the output, or the special tokens to be used. They are only valid for pipelines
that use the `generate`,  For a complete list of all possible arguments, refer to the [generation documentation](/docs/generation).

```php
$output = $generator(
    "What is the capital of Nigeria?",
    maxNewTokens: 256, 
    doSample: true, 
    repetitionPenalty: 1.6
);
```

## Pipeline Outputs

The output is an array where each element corresponds to an input text and contains a key `generated_text` with the generated content. Here’s how the output looks:

```php
[
    ['generated_text' => 'The capital of Nigeria is Abuja']
]
```

For batched inputs or scenarios requiring simultaneous processing of multiple prompts, the output array will contain one entry per input.

  

  
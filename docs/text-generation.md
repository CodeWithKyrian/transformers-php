---
outline: deep
---

# Text Generation

Text Generation involves creating new text based on a given input. This task is versatile and can be used in various
applications, such as filling in incomplete text, generating stories, code generation, and even chat-based interactions.
The model can generate coherent and contextually appropriate text based on the prompts provided.

## Task ID

- `text-generation`

## Default Model

- `Xenova/gpt2`

## Use Cases

Text Generation is applicable in various scenarios, including but not limited to:

- **Instruction Models:** Generating detailed instructions or responses based on user input.
- **Code Generation:** Writing code snippets or entire functions from a given description or partial code.
- **Story Generation:** Crafting creative narratives or continuing a story from a starting sentence.
- **Completion:** Filling in incomplete sentences, paragraphs, or documents.

## Running a Pipeline Session

To use the Text Generation pipeline, you'll need to provide a piece of text. Here's an example:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$generator = pipeline('text-generation', 'Xenova/TinyLlama-1.1B-Chat-v1.0');

$messages = [
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'What is diffusion?'],
];

$input = $generator->tokenizer->applyChatTemplate($messages, addGenerationPrompt: true, tokenize: false);

$output = $generator($input, maxNewTokens: 256, returnFullText: false);
```

## Pipeline Input Options

When running the `text-generation` pipeline, you can use the following options:

- ### `inputs` *(string|array)*
  The text input for text generation can be provided as either a simple string or a chat-like structure.

    - **Simple String:** You can provide a plain text prompt, such as `"Once upon a time, there was a..."`.
    - **Chat-like Structure:** For models that support chat-based interactions, you can provide a structured array with
      role-based inputs similar to the OpenAI API (e.g., `system`, `user`, `assistant`).

  To use chat-based inputs, you need to convert the array into a string using a template before passing it to the model.
  TransformersPHP provides a method called `applyChatTemplate` to handle this conversion.

  ```php
  $messages = [
      ['role' => 'system', 'content' => 'You are a helpful assistant.'],
      ['role' => 'user', 'content' => 'What is diffusion?'],
  ];
  
  $input = $generator->tokenizer->applyChatTemplate($messages, addGenerationPrompt: true, tokenize: false);
  ```

  If a model has its own specific template in the config, it'll be used. Otherwise, TransformersPHP applies this default
  chat template:

  ```jinja
  {% for message in messages %}{{'' + message['role'] + '\\n' + message['content'] + '' + '\\n'}}{% endfor %}{% if add_generation_prompt %}{{ 'assistant\\n' }}{% endif %}
  ```

  ::: warning
  Not all models are suitable for using chat templates. Please check the model’s documentation and use cases before
  applying a chat template to ensure compatibility.
  :::

- ### `maxNewTokens` *(int)*
  Sets the maximum numbers of tokens to generate, ignoring the number of tokens in the prompt.

- ### `doSample` *(bool)*
  Toggles between sampling (true) and greedy decoding (false) for generating tokens. Sampling introduces randomness in
  the generation, making it possible to get different outputs each time for the same input.

- ### `numBeams` *(int)*
  Determines the beam search size. A setting of 1 disables beam search, resulting in faster but potentially less
  accurate results.

- ### `temperature` *(float)*
  Adjusts the probability distribution of the next token to make generation more deterministic or more random. A
  temperature of 1.0 maintains randomness, while a temperature of 0.0 makes the output more deterministic.

- ### `repetitionPenalty` *(float)*
  Penalizes the repetition of the same token. A setting of 1.0 implies no penalty. This helps in generating more diverse
  outputs by reducing the likelihood of repetitive sequences.

- ### `streamer` *(Streamer)*
  [Optional] This is an instance of the `Streamer` class and is used to stream the output of the pipeline. It's useful
  when you want to process the output in real-time or when the output is too large to fit into memory. Visit
  the [Streamers](/utils/generation#streamers) documentation for more information on how to use streamers.

  ```php
  use Codewithkyrian\Transformers\Generation\Streamers\TextStreamer;
  
  $streamer = TextStreamer::make($generator->tokenizer);
  $output = $generator($inputs, streamer: $streamer);
  ```

`maxNewTokens`, `doSample`, `numBeams`, `temperature`, and `repetitionPenalty` are a few of the many possible arguments
used to either control the length of the output, the generation strategy used, or the nature of the output. They are
only valid for pipelines that use the `generate` function. For a complete list of all possible arguments, refer to
the [generation documentation](/utils/generation).

## Pipeline Outputs

The output is an array where each element corresponds to an input text and contains a key `generated_text` with the
generated content. Here’s an example of the output:

```php
[
    ['generated_text' => 'Once upon a time, there was a curious child who wanted to explore the world. She packed her bag and set off on an adventure that would take her to the most amazing places...']
]
```

For chat-based inputs, you can append the generated response to the previous messages array and continue the
conversation.

```php
$output = $generator($input, maxNewTokens: 256, returnFullText: false);

$generatedMessage = $output[0]['generated_text'];

$messages[] = ['role' => 'assistant', 'content' => $generatedMessage];
```

For batched inputs or scenarios requiring simultaneous processing of multiple prompts, the output array will contain one
entry per input.
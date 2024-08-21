---
outline: deep
---

# Automatic Speech Recognition <Badge type="tip" text="^0.5.0" />

Automatic Speech Recognition (ASR), also known as Speech to Text (STT), is the task of transcribing audio into text. It
has various applications, such as voice user interfaces, caption generation, and virtual assistants.

## Task ID

- `automatic-speech-recognition`
- `asr`

## Default Model

- `Xenova/whisper-tiny.en`

## Use Cases

Automatic Speech Recognition is widely used in several domains, including:

- **Caption Generation:** Automatically generates captions for live-streamed or recorded videos, enhancing accessibility
  and aiding in content interpretation for non-native language speakers.
- **Virtual Speech Assistants:** Embedded in devices to recognize voice commands, facilitating tasks like dialing a
  phone number, answering general questions, or scheduling meetings.
- **Multilingual ASR:** Converts audio inputs in multiple languages into transcripts, often with language identification
  for improved performance. Examples include models like Whisper.

## Running an Inference Session

Here's how to perform automatic speech recognition using the pipeline:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$transcriber = pipeline('automatic-speech-recognition', 'onnx-community/whisper-tiny.en');

$audioUrl = __DIR__ . '/preamble.wav';
$output = $transcriber($audioUrl, maxNewTokens: 256);
```

## Pipeline Input Options

When running the `automatic-speech-recognition` pipeline, you can use the following options:

- ### `inputs` *(string)*

  The audio file to transcribe. It can be a local file path, a file resource, or a URL to an audio file (local or
  remote). It's the first argument, so there's no need to pass it as a named argument.

  ```php
  $output = $transcriber('https://example.com/audio.wav');
  ```

- ### `returnTimestamps` *(bool|string)*

  Determines whether to return timestamps with the transcribed text.
    - If set to `true`, the model will return the start and end timestamps for each chunk of text, with the chunks
      determined by the model itself.
    - If set to `'word'`, the model will return timestamps for individual words. Note that word-level timestamps require
      models exported with `output_attentions=True`.

- ### `chunkLengthSecs` *(int)*

  The length of audio chunks to process in seconds. This is essential for models like Whisper that can only process a
  maximum of 30 seconds at a time. Setting this option will chunk the audio, process each chunk individually, and then
  merge the results into a single output.

- ### `strideLengthSecs` *(int)*

  The length of overlap between consecutive audio chunks in seconds. If not provided, this defaults
  to `chunkLengthSecs / 6`. Overlapping ensures smoother transitions and more accurate transcriptions, especially for
  longer audio segments.

- ### `forceFullSequences` *(bool)*

  Whether to force the output to be in full sequences. This is set to `false` by default.

- ### `language` *(string)*

  The source language of the audio. By default, this is `null`, meaning the language will be auto-detected. Specifying
  the language can improve performance if the source language is known.

- ### `task` *(string)*

  The specific task to perform. By default, this is `null`, meaning it will be auto-detected. Possible values
  are `'transcribe'` for transcription and `'translate'` for translating the audio content.

Please note that using the streamer option with this task is not yet supported.

## Pipeline Outputs

The output of the pipeline is an array containing the transcribed text and, optionally, the timestamps. The timestamps
can be provided either at the chunk level or word level, depending on the `returnTimestamps` setting.

- **Default Output (without timestamps):**

  ```php
  [
    "text" => "We, the people of the United States, in order to form a more perfect union, establish justice, ensure domestic tranquility, provide for the common defense, promote the general welfare, and secure the blessings of liberty to ourselves and our posterity, to ordain and establish this constitution for the United States of America."
  ]
  ```

- **Output with Chunk-Level Timestamps:**

  ```php
  [
    "text" => "We, the people of the United States, in order to form a more perfect union...",
    "chunks" => [
      [
        "timestamp" => [0.0, 5.12],
        "text" => "We, the people of the United States, in order to form a more perfect union, establish"
      ],
      [
        "timestamp" => [5.12, 10.4],
        "text" => " justice, ensure domestic tranquility, provide for the common defense, promote the general"
      ],
      [
        "timestamp" => [10.4, 15.2],
        "text" => " welfare, and secure the blessings of liberty to ourselves and our posterity, to ordain"
      ],
      ...
    ]
  ]
  ```

- **Output with Word-Level Timestamps:**

  ```php
  [
    "text" => "...",
    "chunks" => [
      ["text" => "We,", "timestamp" => [0.6, 0.94]],
      ["text" => "the", "timestamp" => [0.94, 1.3]],
      ["text" => "people", "timestamp" => [1.3, 1.52]],
      ["text" => "of", "timestamp" => [1.52, 1.62]],
      ["text" => "the", "timestamp" => [1.62, 1.82]],
      ["text" => "United", "timestamp" => [1.82, 2.52]],
      ["text" => "States", "timestamp" => [2.52, 2.72]],
      ["text" => "in", "timestamp" => [2.72, 2.88]],
      ["text" => "order", "timestamp" => [2.88, 3.1]],
      ...
    ]
  ]
  ```
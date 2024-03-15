---
outline: deep
---

# Translation

Translation, the process of converting text from one language to another, is a critical task in natural language
processing. There are over a thousand translation models on the Hugging Face Model Hub, just make sure to convert these
models to the ONNX format before usage. Alternatively, pre-trained multilingual models like mBART can be fine-tuned on
specific datasets using PyTorch or another deep learning framework and then converted to ONNX for enhanced accuracy and
speed.

## Task ID

- `translation`

## Default Model

- `Xenova/t5-small`

## Use Cases

Translation can be applied in various scenarios, including but not limited to:

- **Multilingual Chatbots:** Enabling chatbots to communicate with users in their preferred language.
- **Content Localization:** Adapting content to suit the language and cultural preferences of a specific audience.
- **Language Learning Tools:** Developing educational software that helps learners understand sentence structure and
  word usage.
- **Cross-Lingual Information Retrieval:** Enabling users to search for content in one language and retrieve results in
  another.

Fine-tuned models, in particular, demonstrate remarkable performance by adapting to the nuances of specific language
pairs, domains, or styles, thereby providing highly accurate translations.

## Running a Pipeline Session

To perform translation, specify your input text along with the target language. Here’s an example:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$translator = pipeline('translation', 'Xenova/m2m100_418M');

$output = $translator('संयुक्त राष्ट्र के प्रमुख का कहना है कि सीरिया में कोई सैन्य समाधान नहीं है', tgtLang: 'fr', maxNewTokens: 256);
```

## Pipeline Input Options

In addition to the standard [Text-To-Text generation](/docs/text-to-text-generation#pipeline-input-options) inputs, the translation pipeline includes:

- ### `texts` *(string|array)*
  The input text to translate. It's the first argument so there's no need to pass it as a named argument. You can pass a
  single string or an array of strings. When passing an array, the pipeline will return translations for each sentence
  in
  the array.

  ```php
  $output = $translator(['"Hello, how are you?"', 'What is the capital of Nigeria?'], tgtLang: 'fr');
  ```

- ### `tgtLang` *(string)*
  The target language to translate the input text to. *This argument is required* as it specifies the language to which
  the input text will be translated. The language code should be in the format of ISO 639-1 (e.g., `en` for
  English, `fr` for French, `es` for Spanish, etc.) (It's not always the case, check the model card for the language
  codes supported by the model)

- ### `srcLang` *(string)*
  [Optional] The source language of the input text. While models can often infer this automatically, specifying it can
  improve accuracy. The language code should be in the format of ISO 639-1 (e.g., `en` for English, `fr` for French,
  `es` for Spanish, etc.) (It's not always the case, check the model card for the language codes supported by the model)

  ```php
  $output = $translator('Je suis un développeur', srcLang: 'fr', tgtLang: 'en');
  ```
  
All other options are the same as the ones in the [text2text-generation](/docs/text-to-text-generation#pipeline-input-options) pipeline.


## Pipeline Outputs

The output is an array where each element corresponds to an input text and contains a key `translated_text` with the 
translated text. For instance, for a single input text, you'll receive an array containing one element:

```php
[
  [
    'translated_text' => 'The United Nations chief says there is no military solution in Syria'
  ]
]
```

The number of elements in the output array corresponds to the number of input texts.
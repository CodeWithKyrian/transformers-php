---
outline: deep
---

# Token Classification

Token classification is a task in natural language processing (NLP) where individual tokens (words or subwords) within a
text are assigned a label. It helps understanding the structure and meaning of sentences. Common examples include Named
Entity Recognition (NER), where the goal is to find and label entities like dates, names, and locations in text, and
Part-of-Speech (PoS) tagging, which involves identifying whether words are nouns, verbs, adjectives, etc.

## Task ID

- `token-classification`
- `ner`

## Default Model

- `Xenova/bert-base-multilingual-cased-ner-hrlh`

## Use Cases

Token classification can be applied in various scenarios, including but not limited to:

- **Information Extraction from Invoices:** Extracting specific entities like dates, company names, and amounts from
  scanned invoice documents.
- **Content Organization and Discovery:** Enhancing search functionality by tagging content with entities like
  locations, person names, or dates.
- **Automated Content Tagging:** Assigning relevant tags to articles or products for better categorization or
  recommendation.
- **Language Learning Tools:** Developing educational software that helps learners understand sentence structure and
  word usage.

## Running a Pipeline Session

To use the Token Classification pipeline, you'll need to provide a piece of text. Here's an example:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$ner = pipeline('token-classification', 'Xenova/bert-base-NER');

$output = $ner('My name is Kyrian and I live in Onitsha');
```

## Pipeline Input Options

When running the `token-classification` pipeline, you can the following options:

- ### `texts` *(string|array)*
  The sentence(s) to classify. It's the first argument so there's no need to pass it as a named argument. You can pass a
  single string or an array of strings. When passing an array, the pipeline will return predictions for each sentence in
  the array.

  ```php
  $output = $ner(['My name is Kyrian and I live in Onitsha', 'I am a developer']);
  ```

- ### `ignoreLabels` *(string[])*
  [Optional] An array of labels to ignore. This is useful when you want to exclude certain labels from the model's
  predictions. The default value is `['O']`, which means the model will ignore the 'O' label(s) (i.e., tokens that are
  not
  part of any named entity) will be ignored. You can pass an empty array to include all labels.

  ```php
  $output = $ner('My name is Kyrian and I live in Onitsha', ignoreLabels: []);
  ```

- ### `aggregationStrategy` *(string)*
  [Optional] Determines how tokens that have been split (subword tokenization) and belong to the same entity are grouped
  in the output. The default strategy is NONE, which means no aggregation is performed. It is passed as a PHP enum
  and the possible values are `AggregationStrategy::NONE`, `AggregationStrategy::FIRST`, `AggregationStrategy::AVERAGE`,
  and `AggregationStrategy::MAX`. You can also pass the string representation of the enum value.

  ```php
  $output = $ner('My name is Kyrian and I live in Onitsha', aggregationStrategy: 'none');
  ```
  Because tokenization can split words into subwords or characters, aggregation strategies help to group these tokens
  meaningfully. Take the word "Onitsha" for example. It might be split into "On", "##it", "##sha" by the tokenizer. Also
  take the word "United States of America". It might be split into "United", "States", "of", "America". Here's how each
  strategy would handle these tokens:

    - `AggregationStrategy::NONE`: No aggregation is performed. The output will contain the individual tokens and their
      labels.
      ```php
        // For "Onitsha"
        [
          // ...
            ['entity' => 'B-LOC', 'word' => 'On', 'score' => 0.9980088367015,],
            ['entity' => 'I-LOC', 'word' => '##its', 'score' => 0.57264213459144,],
            ['entity' => 'I-LOC', 'word' => '##ha', 'score' => 0.99585163659008,]
        ]
      
        // For "United States of America"
        [
            // ...
            ['entity' => 'B-LOC', 'word' => 'United', 'score' => 0.99959621338567,],
            ['entity' => 'I-LOC', 'word' => 'States', 'score' => 0.99930135657091,],
            ['entity' => 'I-LOC', 'word' => 'of', 'score' => 0.99910850633584,],
            ['entity' => 'I-LOC', 'word' => 'America', 'score' => 0.99851260224595,]
        ]
      ```
    - `AggregationStrategy::FIRST`: The subwords will be grouped together as well as tokens with similar entities (
      with the first being a B- tag and the rest being I- tags). The score assigned to the grouped token will be the
      score of the first token in the group. Note the change in the `entity` key to
      `entity_group`.
      ```php
      // For "Onitsha"
      [
        // ...
          ['entity_group' => 'LOC', 'word' => 'Onitsha', 'score' => 0.9980088367015,],
      ]
      
      // For "United States of America"
      [
          // ...
          ['entity_group' => 'LOC', 'word' => 'United States of America', 'score' => 0.99959621338567,],
      ]
      ```
    - `AggregationStrategy::AVERAGE`: Similar to `AggregationStrategy::FIRST`, but the score assigned to the grouped
      token
      will be the average of the scores of the tokens in the group.
      ```php
      // For "Onitsha"
      [
        // ...
          ['entity_group' => 'LOC', 'word' => 'Onitsha', 'score' => 0.85583420296134,],
      ]
      
      // For "United States of America"
      [
          // ...
          ['entity_group' => 'LOC', 'word' => 'United States of America', 'score' => 0.99910416963452,],
      ]
      ```
    - `AggregationStrategy::MAX`: Similar to `AggregationStrategy::FIRST`, but the score assigned to the grouped token
      will be the maximum of the scores of the tokens in the group.
        ```php
        // For "Onitsha"
        [
            // ...
            ['entity_group' => 'LOC', 'word' => 'Onitsha', 'score' => 0.9980088367015,],
        ]
        
        // For "United States of America"
        [
            // ...
            ['entity_group' => 'LOC', 'word' => 'United States of America', 'score' => 0.99959621338567,],
        ]
        ```

## Pipeline Outputs

The output of the pipeline is an array containing the predicted entity, the word, the confidence score, and optionally
the index of the word. THe entity labels themselves vary depending on the model used.

### Named Entity Recognition (NER)

NER can be used to identify things like names of people, locations, organizations, dates, and more. The typical labels
include

| Abbreviation | Description                                                    |
|--------------|----------------------------------------------------------------|
| O            | Outside of a named entity                                      |
| B-MISC       | Beginning of a miscellaneous entity right after another entity |
| I-MISC       | Miscellaneous entity within the same entity group              |
| B-PER        | Beginning of a person’s name right after another person's name |
| I-PER        | Person’s name within the same entity group                     |
| B-ORG        | Beginning of an organization right after another organization  |
| I-ORG        | Organization name within the same entity group                 |
| B-LOC        | Beginning of a location right after another location           |
| I-LOC        | Location name within the same entity group                     |

```php
$ner = pipeline('token-classification', 'Xenova/bert-base-NER');

$output = $ner('My name is Kyrian and I live in Onitsha', aggregationStrategy: 'max');
```

::: details Click to view output
```php
[
  ["entity_group" => "PER", "score" => 0.99431570686513, "word" => "Kyrian"]
  ["entity_group" => "LOC", "score" => 0.9980088367015, "word" => "Onitsha"]
]

```
:::

### Part-of-Speech (PoS) Tagging

PoS models are trained to identify parts of speech, such as nouns, pronouns, verbs,adjectives, etc., in a given text.
The typical labels include:

| Abbreviation | Description                                |
|--------------|--------------------------------------------|
| NOUN         | 	Noun                                      |
| AUX          | 	Auxiliary verb                            |
| PROPN	       | Proper noun                                |
| PRON	        | Pronoun                                    |
| VERB	        | Verb                                       |
| ADP          | 	Apposition (preposition and postposition) |
| CONJ         | 	Conjunction                               |

```php
$ner = pipeline('token-classification', 'codewithkyrian/bert-english-uncased-finetuned-pos');

$output = $ner('My name is Kyrian and I live in Onitsha', aggregationStrategy: 'max');
```

::: details Click to view output
```php
[
    ["entity_group" => "PRON", "word" => "my", "score" => 0.99482086393966],
    ["entity_group" => "NOUN", "word" => "name", "score" => 0.95769686675798],
    ["entity_group" => "AUX", "word" => "is", "score" => 0.97602109098715],
    ["entity_group" => "PROPN", "word" => "kyrian", "score" => 0.96583783664597],
    ["entity_group" => "CCONJ", "word" => "and", "score" => 0.98444884455349],
    ["entity_group" => "PRON", "word" => "i", "score" => 0.99566682068677],
    ["entity_group" => "VERB", "word" => "live", "score" => 0.98391136480035],
    ["entity_group" => "ADP", "word" => "in", "score" => 0.99580186695928],
    ["entity_group" => "PROPN", "word" => "onitsha", "score" => 0.91250281394515],
]
```
:::


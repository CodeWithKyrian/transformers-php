---
outline: deep
---

# Zero-Shot Classification

Zero-shot classification stands out in natural language processing (NLP) by allowing models to classify
text into categories they haven't explicitly been trained on. Unlike traditional text classification, zero-shot
classification leverages natural language understanding, enabling models to intuitively categorize texts based on a set
of candidate labels provided at runtime.

## Task ID

- `zero-shot-classification`

## Default Model

- `Xenova/distilbert-base-uncased-mnli`

## Use Cases

Zero-shot classification's versatility opens up a broad spectrum of applications, including but not limited to:

- **Content Categorization:** Automatically categorizing articles or posts into themes or genres not seen during
  training.
- **Intent Detection:** Identifying the intent behind user queries in chatbots or search engines, even for new, unseen
  intents.
- **Product Tagging:** Classifying products or services into categories or tags that were not available at the time of
  model training.
- **Sentiment Analysis:** Determining sentiments or opinions in texts towards entities or topics that were not
  predefined.

## Running a Pipeline Session

The zero-shot classification pipeline requires two primary inputs: the text to classify and an array of candidate
labels. Here's an example:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$classifier = pipeline('zero-shot-classification', 'Xenova/mobilebert-uncased-mnli');
$result = $classifier('Who are you voting for in 2020?', ['politics', 'public health', 'economics', 'elections']);
```

## Pipeline Input Options

When running the `zero-shot-classification` pipeline, you can the following options:

-  ### `texts` *(string)*

The piece of text you want to categorize. There's no need to provide it as a named argument. It's
always going to be the first input

- ### `candidateLabels` *(string[])*
  An array of strings representing the labels among which the model will classify the text. There's also no need to
  provide it as a named argument. It's always going to be the second argument, and it's required.

- ### `multiLabel` *(bool)*
  [Optional] Indicates whether the text can belong to multiple labels simultaneously. Defaults to `false`, where the
  model assigns
  the highest score to the most likely label.

  ```php
  $classifier = pipeline('zero-shot-classification', 'Xenova/mobilebert-uncased-mnli');
  $result = $classifier(
    'I have a problem with my iphone that needs to be resolved asap!',
    ['urgent', 'not urgent', 'phone', 'tablet', 'computer'],
    multiLabel: true
  );
  ```

- ### `hypothesisTemplate` *(string)*
  A template used to frame the labels for the model before inference, defaulting to `"This example is {}."`. Adjust this
  if the default template does not fit your use case.

  ```php
  $classifier = pipeline('zero-shot-classification', 'Xenova/mobilebert-uncased-mnli');
  $result = $classifier(
    'My favorite fruit is the apple.',
    ['fruit', 'vegetable', 'meat'],
    hypothesisTemplate: "The topic of this text is about {}."
  );
  ```

## Pipeline Outputs

The output includes the original text (`sequence`), an array of labels, and their corresponding confidence
scores (`scores`). These scores indicate the model's confidence in associating each label with the text, with a range
from 0 to 1.

The model's ability to assign multiple labels (when `multiLabel` is true) significantly enhances its flexibility, allowing
for more nuanced text classification. This feature is particularly useful in scenarios where texts inherently belong to
multiple categories. Let's compare the outputs of the earlier example

Without `multiLabel`:

```php
[
  "sequence" => "I have a problem with my iphone that needs to be resolved asap!",
  "labels" => ["urgent", "phone", "computer", "tablet", "not urgent"],
  "scores" => [0.9992283160322, 0.042726408774879,  0.022445628181903, 0.0039340428157268, 0.0016656041952894]
]
```

With `multiLabel`:

```php
[
  "sequence" => "I have a problem with my iphone that needs to be resolved asap!",
  "labels" => ["urgent", "phone", "computer", "tablet", "not urgent"],
  "scores" => [0.99588709563603, 0.9923963400697,  0.0023335396113424, 0.0015134149376, 0.0010699384208377]
]
```

As you can see, it was able to correctly pick up the phone category as well. 
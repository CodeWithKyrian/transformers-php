---
outline: deep
---

# Summarization

Summarization is the process of shortening a set of data computationally, to create a subset (a summary) that represents
the most important or relevant information within the original content. This task is particularly beneficial in
situations where quick understanding of large volumes of text is required, such as news articles, legal documents, or
scientific papers. Models used for summarization can either extract key sentences from the original text (
extraction-based) or generate new text that summarizes the content (abstraction-based).

## Task ID

- `summarization`

## Default Model

- `Xenova/distilbart-cnn-6-6`

## Use Cases

Summarization can be applied in various scenarios, including but not limited to:

- **Content Curation:** Automatically generating summaries of news articles, blog posts, or research papers to help
  users
  decide whether to read the full content. Eg. Book summaries, news articles, etc.
- **Research Paper Summarization:** Quickly grasping the main findings and significance of academic papers without
  reading them in full.
- **Email Filtering:** Summarizing emails to provide a quick overview of their content, helping users prioritize their
  responses.
- **Executive Briefs:** Summarizing reports or proposals for executives and decision-makers who need to digest
  information rapidly.

Fine-tuned models, in particular, demonstrate remarkable performance by adapting to the nuances of specific domains or
styles, thereby providing highly accurate summaries. Eg. CNN fine-tuned models for news articles, etc.

## Running a Pipeline Session

To use the Summarization pipeline, you'll need to provide a piece of text. Here's an example:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$summarizer = pipeline('summarization', 'Xenova/distilbart-cnn-6-6');

$article = 'The Amazon rainforest, known as the "lungs of the Earth," is the largest tropical rainforest in the world, ' .
    'covering over 5.5 million square kilometers. It is home to millions of species of flora and fauna, many of which are ' .
    'still undiscovered. The rainforest plays a crucial role in regulating the planet\'s climate by absorbing large ' .
    'amounts of carbon dioxide. However, it is under threat from deforestation due to logging, mining, and farming, ' .
    'leading to a loss of biodiversity and contributing to global warming. Conservation efforts are underway to protect ' .
    'this vital ecosystem, including initiatives to promote sustainable land use and reduce human impact.';

$summary = $summarizer($article, maxNewTokens: 512, temperature: 0.7);
```

## Pipeline Input Options

All options available for the standard [Text-To-Text generation](/text-to-text-generation#pipeline-input-options)
are
also available for the summarization pipeline.

## Pipeline Outputs

The output is an array where each element corresponds to an input text and contains a key `summarized_text` with the
generated summary. For a single input text, you'll receive an array containing one element: a string with the summary.

```php
[
    "summarized_text" => "The Amazon rainforest is the largest tropical rainforest, crucial for regulating the climate by absorbing CO2. Despite its importance, it faces threats from deforestation, impacting biodiversity and global warming. Conservation efforts are essential for its protection."
]
```

The number of elements in the output array corresponds to the number of input texts.

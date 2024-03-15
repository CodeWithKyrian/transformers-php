---
outline: deep
---

# Feature Extraction

Feature extraction transforms raw data into a set of numerical features that can be processed by machine learning
algorithms while preserving the essential information from the original data. This process is crucial in NLP, computer
vision, and audio processing for enhancing machine learning models' performance without working directly with the raw
data.

## Task ID

- `feature-extraction`
- `embeddings`

## Default Model

- `Xenova/all-MiniLM-L6-v2`

## Use Cases

Feature extraction is pivotal in numerous applications:

- **Text Embeddings for Retrieval-Augmented Generation (RAG):** Generates dense vector representations of text, enabling
  models to retrieve relevant information from a corpus or database based on semantic similarity.

- **Substitute Memory for Large Language Models:** Helps in compressing information into dense representations, serving
  as a memory substitute for large models, thus optimizing performance and resource usage.

- **Transfer Learning:** By extracting features from one task and applying them to another, models can leverage learned
  information, facilitating quicker adaptation to new tasks with less data.

- **Sentence Similarity and Semantic Searching:** Computes embeddings for sentences to measure similarity or perform
  semantic searches, enabling systems to find related content based on meaning rather than exact word matches.

## Running a Pipeline Session

To extract features, you specify the text(s) from which you want to generate embeddings:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$extractor = pipeline('embeddings', 'Xenova/all-MiniLM-L6-v2');
$embeddings = $extractor('The quick brown fox jumps over the lazy dog.', normalize: true, pooling: 'mean');
```

## Pipeline Input Options

When running the `feature-extraction` pipeline, you can the following options:

- ### `texts` *(string|array)*
  The raw text input from which embeddings are generated. You can pass a single string or an array of strings for batch
  processing. It's required and is the first argument, so there's no need to pass it as a named argument

- ### `normalize` *(bool)*
  When set to `true`, it normalizes the embedding vectors to have a unit length (1). Normalization can be beneficial
  when measuring distances or similarities between embeddings, as it ensures that the magnitude of the vectors have a
  similar scale thus preventing distortion while performing comparison. This option default to `false` if not provided.

- ### `pooling` *(string)*
  Specifies the strategy for combining token embeddings into a single embedding vector for texts with multiple tokens.
  The options are:

    - `none` *(default)*:  No pooling; the output is the raw token embeddings.
    - `mean`: Calculates the mean of all token embeddings, providing a single vector that represents the entire input.
    - `cls`

## Pipeline Outputs

The output is an array of embeddings. For a single input text, you'll receive an array containing one element: a vector
of dimensions dependent on the model. For instance, for `Xenova/all-MiniLM-L6-v2`, each embedding will be a
384-dimensional array for mean pooling and a 12 * 384 array for no pooling.

```php
[
    // For a single input
    [ 0.52769871583829, -0.32486832886934, ...,  -0.053648692245285, 0.24839715644096] // 384-dimensional vector for 'Xenova/all-MiniLM-L6-v2'
]
```

When normalize is enabled, these vectors are scaled to have a unit norm, making them suitable for similarity
calculations. Pooling strategies like mean or using the cls token can affect the shape of the embeddings and how they
are calculated, either by averaging the embeddings of all tokens or focusing on a specific token's embedding for the
whole sentence representation.
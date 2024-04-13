---
outline: deep
---

# Image Classification <Badge type="tip" text="^0.3.0" />

Image classification is a computer vision task that involves assigning a label or class to an image. An image
is expected to have only one label in this task. The labels to be selected from are predefined by the model.
This task accepts image inputs and returns the classification label and the confidence score.

## Task ID

- `image-classification`

## Default Model

- `Xenova/vit-base-patch16-224`.

## Use Cases

Image classification models find application in various scenarios, including:

- **Stock Photography Keywording:** Assigning keywords to images in stock photography databases.
- **Image Search:** Organizing and categorizing photo galleries on devices or in the cloud based on multiple keywords or
  tags.
- **Content Filtering:** Filtering and categorizing images for content moderation purposes.
- **Medical Imaging:** Assisting in the diagnosis and classification of medical images such as X-rays and MRI scans.

## Running an Inference Session

Here's how to perform image classification using the pipeline:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$classifier = pipeline('image-classification');

$result = $classifier('path/to/image.jpg');
```

::: details Click to view output

```php
['label' => 'tiger, Panthera tigris',  'score' => 0.63534494664876]
```

:::

## Pipeline Input Options

When running the `image-classification` pipeline, you can the following options:

- ### `texts` *(string)*
  The image(s) to classify. It can be a local file path, a file resource, a URL to an image (local or remote), or an
  array of these inputs. It's the first argument so there's no need to pass it as a named argument.
  ```php
      $result = $classifier('https://example.com/image.jpg');
  ```

- ### `topK` *(int)*
  The number of top labels to return. The default is `1`.
  ```php
      $result = $classifier('https://example.com/image.jpg', topK: 3);
  ```
  ::: details Click to view output

  ```php
  [
    ['label' => 'tiger, Panthera tigris',  'score' => 0.63534494664876],
    ['label' => 'zebra',  'score' => 0.123456789],
    ['label' => 'lion, Panthera leo',  'score' => 0.098765432]
  ]
  ```
  :::

## Pipeline Outputs

The output of the pipeline is an array containing the classification label and the confidence score. The confidence
score is a value between 0 and 1, with 1 being the highest confidence.

Since the actual labels depend on the model, it's crucial to consult the model's documentation for the specific labels
it uses. Here are examples demonstrating how outputs might differ:

For a single image:

```php
['label' => 'tiger, Panthera tigris',  'score' => 0.63534494664876]
```

For multiple images:

```php
[
    ['label' => 'tiger, Panthera tigris',  'score' => 0.63534494664876],
    ['label' => 'cat',  'score' => 0.987654321],
    ['label' => 'dog',  'score' => 0.87654321]
]
```


  
---
outline: deep
---

# Zero-Shot Image Classification <Badge type="tip" text="^0.3.0" />

Zero-shot image classification extends the concept of zero-shot learning to the computer vision domain, allowing
models to classify images into categories they haven't explicitly been trained on. Unlike traditional image
classification, zero-shot image classification leverages natural language understanding, enabling models to intuitively
categorize images based on a set of predefined labels.

## Task ID

- `zero-shot-image-classification`

## Default Model

- `Xenova/clip-vit-base-patch32`

## Use Cases

Zero-shot image classification can be applied in various scenarios, including but not limited to:

- **Unsupervised Object Recognition:** Identifying objects in images without the need for labeled training data for each
  specific object.
- **Cross Domain Image Classification:** Classifying images across different domains or datasets without fine-tuning on
  each dataset individually.
- **Product Recognition:** Identifying products or objects in images for e-commerce applications or inventory
  management.
- **Automated Image Tagging:** Automatically assigning relevant tags or labels to images based on their content, even
  for previously unseen categories.

## Running a Pipeline Session

The zero-shot image classification pipeline requires two primary inputs: the image to classify and an array of candidate
labels.

Here's an example:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$classifier = pipeline('zero-shot-image-classification');
$result = $classifier('path/to/image.jpg', ['zebra', 'elephant', 'giraffe']);
```

## Pipeline Input Options

When running the `zero-shot-image-classification` pipeline, you can the following options:

- ### `texts` *(string)*

  The image(s) to classify. It can be a local file path, a file resource, a URL to an image (local or remote), or an
  array of these inputs. It's the first argument so there's no need to pass it as a named argument.

    ```php
    use function Codewithkyrian\Transformers\Pipelines\pipeline;
  
    $classifier = pipeline('zero-shot-image-classification');
    $result = $classifier('path/to/image.jpg');
    ```

  ::: details Click to view output
  ```php
    [
       ['label' => 'zebra',  'score' => 0.83534494664876],
       ['label' => 'elephant',  'score' => 0.03534494664876],
       ['label' => 'giraffe',  'score' => 0.00534494664876]
    ]
  ```
  :::

- ### `candidateLabels` *(string[])*

  An array of strings representing the labels among which the model will classify the image. There's also no need to
  provide it as a named argument. It's always going to be the second argument, and it's required.

  ```php
  $result = $classifier('path/to/image.jpg', ['zebra', 'elephant', 'giraffe']);
  ```

  ::: details Click to view output
  ```php
  ['label' => 'zebra',  'score' => 0.63534494664876]
  ```
  :::

## Pipeline Outputs

The output of the pipeline is an array containing the classification label and the confidence score. The confidence
score is a value between 0 and 1, with 1 being the highest confidence.

```php
[
   ['label' => 'zebra',  'score' => 0.83534494664876],
   ['label' => 'elephant',  'score' => 0.03534494664876],
   ['label' => 'giraffe',  'score' => 0.00534494664876]
]
```


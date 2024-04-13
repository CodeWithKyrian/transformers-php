---
outline: deep
---

# Zero-Shot Object Detection <Badge type="tip" text="^0.3.0" />

Zero-shot object detection is an extension of the object detection task that allows models to detect objects in images
without being explicitly trained on the target classes. This approach leverages natural language understanding to
categorize objects based on a set of predefined labels, enabling models to detect objects they haven't seen during
training.

## Task ID

- `zero-shot-object-detection`

## Default Model

- `Xenova/owlvit-base-patch32`

## Use Cases

Zero-shot object detection can be applied in various scenarios, including but not limited to:

- **Unsupervised Object Detection:** Identifying objects in images without the need for labeled training data for each
  specific object.
- **Cross-Domain Object Detection:** Detecting objects across different domains or datasets without fine-tuning on each
- **Safety and Security:** Enhancing safety and security measures by detecting and monitoring objects of interest in
  surveillance footage or satellite imagery without the need for prior training data.

## Running a Pipeline Session

The zero-shot object detection pipeline requires two primary inputs: the image to analyze and an array of candidate
labels.

Here's an example:

```php  
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$detector = pipeline('zero-shot-object-detection');

$result = $detector('path/to/image.jpg', ['person', 'car', 'traffic light']);
```

::: details Click to view output

```php
[
  [
    "score" => 0.99796805983403,
    "label" => "car",
    "box" => [ "xmin" => 29, "ymin" => 65, "xmax" => 188, "ymax" => 122 ]
  ],
  // Additional detected objects with their scores, labels, and bounding boxes
]
```

:::

## Pipeline Input Options

When running the `zero-shot-object-detection` pipeline, you can the following options:

- ### `texts` *(string)*

  The image(s) to classify. It can be a local file path, a file resource, a URL to a remote image, or an
  array of these inputs. It's the first argument so there's no need to pass it as a named argument.

    ```php
    use function Codewithkyrian\Transformers\Pipelines\pipeline;
  
    $detector = pipeline('zero-shot-object-detection');
    $result = $detector('https://example.com/image.jpg');
    ```

- ### `candidateLabels` *(array)*

  An array of candidate labels to consider when detecting objects in the image. The pipeline will return the top
  predictions based on these labels. It's always the second argument, so there's no need to pass it as a named argument.

  ```php
    use function Codewithkyrian\Transformers\Pipelines\pipeline;

    $detector = pipeline('zero-shot-object-detection');
    $result = $detector('path/to/image.jpg', ['person', 'car', 'traffic light']);
  ```

- ### `threshold` *(float)*

  [Optional] The minimum confidence score required for an object to be considered a valid detection. The default value
  is `0.1`. The threshold value is lower than the default object detection pipeline because zero-shot object detection
  models may not be as confident when detecting unseen objects. Lowering the threshold may increase the number of
  detected objects but can also lead to more false positives, so it's essential to find a balance based on the model and
  the specific use case.

  ```php
  $result = $detector('path/to/image.jpg', ['person', 'car', 'traffic light'], threshold: 0.05);
  ```

- ### `percentage` *(bool)*

  [Optional] Whether to return the bounding box coordinates as percentages of the image dimensions (from 0 to 1) . By
  default, the coordinates are returned as absolute pixel values. Setting this option to `true` can be useful when
  working with images of different sizes or aspect ratios.

    ```php
    $result = $detector('path/to/image.jpg', ['person', 'car', 'traffic light'], percentage: true);
    ```

- ### `topK` *(int)*

  [Optional] The number of top objects to return. By default, it returns all detected objects that pass the threshold.
  Set
  to a specific number to receive that many top detections.

  ```php
  $result = $detector('path/to/image.jpg', ['person', 'car', 'traffic light'], topK: 3);
  ```

## Pipeline Outputs

The output of the pipeline is an array of objects, where each object contains the following information for each
detected object:

- `label` *(string)*: The predicted label of the detected object.
- `score` *(float)*: The confidence score of the prediction, ranging from 0 to 1, with 1 being the highest confidence.
- `box` *(array)*: An associative array containing the bounding box coordinates of the detected object. The coordinates
  are
  represented as values in the format `["xmin" => x1, "ymin" => y1, "xmax" => x2, "ymax" => y2]`. Depending on the
  `percentage` option, the values can be either absolute pixel values or percentages of the image dimensions.

Here's an example of the output format:

For `percentage: false`:

```php
[
  [
    "score" => 0.99796805983403,
    "label" => "car",
    "box" => [ "xmin" => 29, "ymin" => 65, "xmax" => 188, "ymax" => 122 ]
  ],
  // Additional detected objects with their scores, labels, and bounding boxes
]
```

For `percentage: true`:

```php
[
  [
    "score" => 0.99796805983403,
    "label" => "car",
    "box" => [ "xmin" => 0.05, "ymin" => 0.15, "xmax" => 0.25, "ymax" => 0.30 ]
  ],
  // Additional detected objects with their scores, labels, and bounding boxes
]
```

## Additional Notes

- Zero-shot object detection models may not perform as well as models trained on specific object classes. The accuracy
  of
  the detections may vary based on the candidate labels provided and the model's training data.
- It's essential to choose candidate labels that are relevant to the objects you expect to detect in the image.
  Including
  too many irrelevant labels may reduce the accuracy of the detections.
- Checkout the [Object Detection](/object-detection#visualizing-object-detection-results) documentation page to learn
  how to visualize the detected objects in an image.

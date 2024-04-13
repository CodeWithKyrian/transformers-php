---
outline: deep
---

# Object Detection <Badge type="tip" text="^0.3.0" />

Object detection is a computer vision task that involves identifying and locating objects within images. Here, the model
detects multiple objects in an image and provides bounding boxes around them. Object detection models are trained to
recognize objects of predefined classes, such as cars, people, animals, and more. The task accepts image inputs and
returns
the detected objects along with their bounding boxes.

## Task ID

- `object-detection`

## Default Model

- `Xenova/detr-resnet-50`

## Use Cases

Object detection models find application in various scenarios, including:

- **Object Counting:** Counting instances of objects in images for various purposes, such as inventory management in
  warehouses or crowd management at events.
- **Retail Analytics:** Tracking customer behavior and product placement in stores.
- **Security and Surveillance:** Monitoring public spaces for security threats and suspicious activities.
- **Industrial Automation:** Inspecting products on assembly lines for quality control.

## Running an Inference Session

Here's how to perform object detection using the pipeline:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$detector = pipeline('object-detection');

$result = $detector('path/to/image.jpg');
```

::: details Click to view output

```php
[
  [
    "score" => 0.99796805983403,
    "label" => "remote",
    "box" => [ "xmin" => 29, "ymin" => 65, "xmax" => 188, "ymax" => 122 ]
  ],
  // Additional detected objects with their scores, labels, and bounding boxes
]

```

:::

## Pipeline Input Options

When running the `object-detection` pipeline, you can the following options:

- ### `texts` *(string)*

  The image(s) to classify. It can be a local file path, a URL to a remote image, or an
  array of these inputs. It's the first argument so there's no need to pass it as a named argument.

    ```php
    use function Codewithkyrian\Transformers\Pipelines\pipeline;
  
    $detector = pipeline('object-detection');
  
    $result = $detector('path/to/image.jpg');
    ```
- ### `threshold` *(float)*

  [Optional] The minimum confidence score required for an object to be considered a valid detection. The default value
  is `0.9`. Lowering the threshold may increase the number of detected objects but can also lead to more false
  positives, so it's essential to find a balance based on the mode and the specific use case.

  ```php
  $result = $detector('path/to/image.jpg', threshold: 0.7);
  ```

- ### `percentage` *(bool)*

  [Optional] Whether to return the bounding box coordinates as percentages of the image dimensions (from 0 to 1) . By
  default, the coordinates are returned as absolute pixel values. Setting this option to `true` can be useful when
  working with images of different sizes or aspect ratios.

  ```php
  $result = $detector('path/to/image.jpg', percentage: true);
  ```

## Pipeline Outputs

The output of the pipeline is an array containing the detected objects, each represented as an associative array with
the following keys:

- `score`: The confidence score of the detection, ranging from 0 to 1, with 1 being the highest confidence.
- `label`: The class label of the detected object.
- `box`: An associative array containing the bounding box coordinates of the detected object. The coordinates are
  represented as values in the format `["xmin" => x1, "ymin" => y1, "xmax" => x2, "ymax" => y2]`. Depending on the
  `percentage` option, the values can be either absolute pixel values or percentages of the image dimensions.

Here's an example of the output format:

For `percentage: false`:

```php
[
  [
    "score" => 0.99796805983403,
    "label" => "remote",
    "box" => [ "xmin" => 29, "ymin" => 65, "xmax" => 188, "ymax" => 122 ]
  ],
  [
    "score" => 0.99609794521754
    "label" => "couch"
    "box" => array:4 [ "xmin" => 2, "ymin" => 0, "xmax" => 636, "ymax" => 472]
  ],
  [
    "score" => 0.99781166261151
    "label" => "cat"
    "box" => array:4 [ "xmin" => 5, "ymin" => 54, "xmax" => 323, "ymax" => 467 ]
  ],
  // Additional detected objects with their scores, labels, and bounding boxes
]
```

For `percentage: true`:

```php
[
  [
    "score" => 0.99796805983403,
    "label" => "remote",
    "box" => [ "xmin" => 0.046750202775002, "ymin" => 0.1370929479599, "xmax" => 0.29410694539547, "ymax" => 0.25422710180283 ]
  ],
  [
    "score" => 0.99609794521754
    "label" => "couch"
    "box" => array:4 [ "xmin" => -0.0045824944972992, "ymin" => 0.00046494603157043, "xmax" => 0.99463525414467, "ymax" => 0.98414328694344]
  ],
  [
    "score" => 0.99781166261151
    "label" => "cat"
    "box" => array:4 [ "xmin" => 0.0078403949737549, "ymin" => 0.11353152990341, "xmax" => 0.50537252426147, "ymax" => 0.9735626578331 ]
  ],
  // Additional detected objects with their scores, labels, and bounding boxes
]
```

The actual labels and scores may vary based on the model and the input image. It's essential to consult the model's
documentation for specific details on the labels and their corresponding scores.

## Visualizing Object Detection Results

You could use the bounding box coordinates to draw the boxes around the detected objects in the image for visualization
purposes. TransformersPHP provides an Image utility class that it uses internally for image processing tasks, and you
could use it too to draw the bounding boxes on the image.

```php
use Codewithkyrian\Transformers\Utils\Image;

$image = Image::read('path/to/image.jpg');

foreach ($result as $object) {
    $box = $object['box'];
    $image->drawRectangle($box['xmin'], $box['ymin'], $box['xmax'], $box['ymax'], '0099FF', thickness: 2);
    $image->drawText($object['label'], $box['xmin'], max($box['ymin'] - 5, 0), 'path/to/font.ttf', 12, '0099FF');
}

$image->save('path/to/output.jpg');
```

Example output:

![image](/images/detection-example.jpg)

For more details on how to use the Image class, please check the [Image documentation](/utils/image) page.
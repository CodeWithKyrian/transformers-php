---
outline: deep
---

# Image To Text <Badge type="tip" text="^0.3.0" />

Image to text is a computer vision task that involves extracting text from images. The task accepts image inputs and
returns a text related to the content of the image. The most common applications of image to text are in Image
Captioning and Optical Character Recognition (OCR).

## Task ID

- `image-to-text`

## Default Model

- `Xenova/vit-gpt2-image-captioning`

## Use Cases

Image to text models find application in various scenarios, including:

- **Image Captioning:** Generating textual descriptions of images for visually impaired users or for content
  generation.
- **Optical Character Recognition (OCR):** Extracting text from images of documents, receipts, and other printed
  materials for digitization and indexing.
- **Content Moderation:** Analyzing images for text content to filter out inappropriate or harmful content.

## Running an Inference Session

Here's how to perform image to text using the pipeline:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$captioner = pipeline('image-to-text');

$result = $captioner('path/to/image.jpg');
```

::: details Click to view output

```php
[
  "text" => "A close up of a cat sitting on a bed"
]
```

:::

The task being performed here is image captioning, determined by the model used. If you want to perform OCR, you can use
a model specifically trained for that task eg. `Xenova/trocr-small-handwritten`, and then pass in an image of a single
line of handwritten text.

## Pipeline Input Options

When running the `image-to-text` pipeline, you can the following options:

- ### `texts` *(string)*
  The image(s) to extract text from. It can be a local file path, a file resource, a URL to an image (local or remote),
  or an array of these inputs. It's the first argument so there's no need to pass it as a named argument.
  ```php
      $result = $captioner('https://example.com/image.jpg');
  ```

All other options are the same as the ones in
the [text2text-generation](/text-to-text-generation#pipeline-input-options) pipeline, including the `streamer`.

## Pipeline Output

The output is an array where each element corresponds to an input text and contains a key `generated_text` with the
detected text in the image.

```php
[
  [
    "generated_text" => "A close up of a cat sitting on a bed"
  ]
]
```

The number of elements in the output array corresponds to the number of input images.

## Additional Notes

- The accuracy of the extracted text depends on the quality of the input image and the model's training data.
- The model may not be able to detect text in images with complex backgrounds, low resolution, or unusual fonts.
- Use non-quantized models for better text extraction results.


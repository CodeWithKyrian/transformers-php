---
outline: deep
---

# Image To Image <Badge type="tip" text="^0.3.0" />

Image-to-image translation is a computer vision task that involves converting an image from one domain to another. The
task is also known as image-to-image synthesis or image-to-image transformation. The model takes an image as input and
generates a corresponding image in a different domain. Any image manipulation task, such as colorization,
super-resolution, style transfer, and image inpainting, falls under this category. Of course, the particular image
manipulation task depends on the model and the dataset it was trained on.

## Task ID

- `image-to-image`

## Default Model

- `Xenova/swin2SR-classical-sr-x2-64`

## Use Cases

Image-to-image translation models find application in various scenarios, including:

- **Colorization:** Converting old or black-and-white images to color.
- **Super-Resolution:** Increasing the resolution of low-quality images for better clarity or printing.
- **Style Transfer:** Applying the style of one image to another.
- **Image Inpainting:** Filling in missing parts of an image or removing unwanted objects.
- **Image Restoration:** Restoring old or damaged images to improve their quality.

> [!NOTE]
> There's not a lot of Image to Image models built for the transformers architecture in the HuggingFace Hub. Most
> of the Image to Image models are built to work with the [🧨Diffusers](https://huggingface.co/docs/diffusers/en/index)
> Library instead. So before using this task, make sure the model you want to use is compatible with the transformers
> architecture (ie can be used with the original transformers library).

## Running an Inference Session

Here's how to perform image-to-image translation using the pipeline:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

$upscaler = pipeline('image-to-image', 'Xenova/swin2SR-classical-sr-x2-64');

$result = $upscaler('path/to/image.jpg', saveTo: 'path/to/super-resolved-image.jpg');
```

::: details Click to view output

```php
[
  'path' => 'path/to/super-resolved-image.jpg',
  'width' => 512,
  'height' => 512,
  'channels' => 3
]
```

:::

## Pipeline Input Options

When running the `image-to-image` pipeline, you can use the following options:

- ### `texts` *(string)*
  The image(s) to translate. It can be a local file path, a file resource, a URL to an image (local or remote), or an
  array of these inputs. It's the first argument so there's no need to pass it as a named argument.
  ```php
  $result = $upscaler('https://example.com/image.jpg');
  ```

- ### `saveTo` *(string)*
  The path to save the translated image. It is compulsory and an exception will be thrown if it is not provided. If the
  input texts are an array of images, the `saveTo` should also be an array of the same length. The `saveTo` path(s)
  should include the file extension (e.g., `.jpg`, `.png`, `.bmp`, etc.).
    ```php
    $result = $upscaler('https://example.com/image.jpg', saveTo: 'path/to/super-resolved-image.jpg');
    ```

## Pipeline Output

The pipeline returns an array containing the following keys:

- `path` *(string)*: The path to the translated image.
- `width` *(int)*: The width of the translated image.
- `height` *(int)*: The height of the translated image.
- `channels` *(int)*: The number of channels in the translated image (e.g., 1 for grayscale, 3 for RGB).

If the input is one image(non batched), the output will directly contain the keys `path`, `width`, `height`,
and `channels`. If the input is an array of images, the output will be an array of the above keys for each image.

E.g., for a single image:

```php
[
  'path' => 'path/to/super-resolved-image.jpg',
  'width' => 512,
  'height' => 512,
  'channels' => 3
]
```

For multiple images:

```php
[
  [
    'path' => 'path/to/super-resolved-image1.jpg',
    'width' => 512,
    'height' => 512,
    'channels' => 3
  ],
  [
    'path' => 'path/to/super-resolved-image2.jpg',
    'width' => 512,
    'height' => 512,
    'channels' => 3
  ],
  // Additional translated images
]
```

## Additional Notes

- The pipeline can handle images of different sizes and aspect ratios. The translated images will have the same
  aspect ratio as the input images.
- Make sure to consult the model card for the specific use case and limitations of the model you are using.
- The `saveTo` path should be writable by the PHP process running the script.


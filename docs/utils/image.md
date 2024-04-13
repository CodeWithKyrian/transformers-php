---
outline: deep
---

# Image Processing <Badge type="tip" text="^0.3.0" />

The `Image` utility class in TransformerPHP provides a set of tools for image processing tasks within the library. While
primarily designed for internal use, it is also accessible for end-users to perform basic image processing tasks. This
page provides an overview of the functionality and usage of the `Image` utility class.

## Image Drivers

The `Image` class is built to work with multiple image processing backends.

- **IMAGICK:** The default image driver used by TransformersPHP. It provides a wide range of image processing functions
  and is the recommended driver for most use cases.
    - Pros: Powerful and feature-rich, with support for a wide range of image formats and operations.
    - Cons: Requires the IMAGICK PHP extension to be installed, which may not be available in all environments.
- **GD:** A simpler image processing driver that is available by default in most PHP installations. It is less powerful
  than IMAGICK but is a good alternative for users who do not have the IMAGICK extension installed.
    - Pros: Widely available and easy to use.
    - Cons: May lack advanced features and performance compared to Vips and Imagick.
- **VIPS:** A high-performance image processing library that is not available by default in PHP installations. It is
  recommended for users who require high-speed image processing and have the VIPS extension installed.
    - Pros: Known for its speed and efficiency, especially for large images.
    - Cons: May require additional installation steps but provides excellent performance.

The image driver can be set using the `setImageDriver()` method in the `Transformers` class. The default driver is
IMAGICK, but you can change it to GD or VIPS based on your requirements.

```php
use Codewithkyrian\Transformers\Transformers;

Transformers::setup()
    ->setImageDriver(ImageDriver::GD)
    ->apply();
```

## Image Processing Operations

The `Image` utility class provides a range of image processing operations that can be performed on images. These
operations include:

- ### `read(string $input, array $options = [])`
  Reads an image from a file path, URL, or resource and returns an image object.

  Parameters:
    - `$input` (string) The path to the image file, a URL to an image, or a file resource.
    - `$options` (array) An array of options to customize the image reading process.

  Returns:
    - An image object representing the input image.

  Example:
  ```php
  $image = Image::read('path/to/image.jpg');
  ```

- ### `fromTensor(Tensor $tensor, string $channelFormat = 'CHW')`
  Creates an image from a tensor (containing the pixel data of the image).

  Parameters:
    - `$tensor` (Tensor) The tensor object representing the image data.
    - `$channelFormat` (string) The channel format of the tensor data. Default is 'CHW'.

  Returns:
    - An image object representing the tensor data.

  Example:
  ```php
  $image = Image::fromTensor($tensor);
  ```

- ### `toTensor(string $channelFormat = 'CHW')`
  Converts the image to a tensor object containing the pixel data of the image.

  Parameters:
    - `$channelFormat` (string) The channel format of the tensor data. Default is 'CHW'.

  Returns:
    - A tensor object representing the pixel data of the image.

  Example:
  ```php
  $tensor = $image->toTensor();
  ```

- ### `resize(int $width, int $height, int|Resample $resample = 2)`
  Resizes an image to the specified width and height, using the specified resampling method. This operation affects the
  instance it's called on, but still returns that instance for method chaining.

  Parameters:
    - `$width` (int) The target width of the resized image.
    - `$height` (int) The target height of the resized image.
    - `$resample` (int|Resample) The resampling method to use. Default is `Resample::BICUBIC`.

  Returns:
    - An image object representing the resized image.

  Example:
  ```php
  $resizedImage = $image->resize(300, 200);
  ```

- ### `crop(int $xMin, int $yMin, int $xMax, int $yMax)`
  Crops the image to the specified bounding box defined by the top-left and bottom-right coordinates. This operation
  affects the instance it's called on, but still returns that instance for method chaining.

  Parameters:
    - `$xMin` (int) The x-coordinate of the top-left corner of the bounding box.
    - `$yMin` (int) The y-coordinate of the top-left corner of the bounding box.
    - `$xMax` (int) The x-coordinate of the bottom-right corner of the bounding box.
    - `$yMax` (int) The y-coordinate of the bottom-right corner of the bounding box.

  Returns:
    - An image object representing the cropped image.

  Example:
  ```php
  $croppedImage = $image->crop(100, 100, 300, 200);
  ```

- ### `centerCrop(int $width, int $height)`
  Crops the image to the specified width and height by centering the crop around the image's center. This operation
  affects the instance it's called on, but still returns that instance for method chaining.

  Parameters:
    - `$width` (int) The target width of the cropped image.
    - `$height` (int) The target height of the cropped image.

  Returns:
    - An image object representing the cropped image.

  Example:
  ```php
  $croppedImage = $image->centerCrop(200, 200);
  ```

- ### `pad(int $left, int $right, int $top, int $bottom)`
  Pads the image with the specified number of pixels on each side. This operation affects the instance it's called on,
  but still returns that instance for method chaining.

  Parameters:
    - `$left` (int) The number of pixels to add to the left side.
    - `$right` (int) The number of pixels to add to the right side.
    - `$top` (int) The number of pixels to add to the top side.
    - `$bottom` (int) The number of pixels to add to the bottom side.

  Returns:
    - An image object representing the padded image.

  Example:
  ```php
  $paddedImage = $image->pad(10, 10, 10, 10);
  ```

- ### `grayscale()`
  Converts the image to grayscale. This operation affects the instance it's called on, but still returns that instance
  for method chaining.

  Returns:
    - An image object representing the grayscale image.

  Example:
  ```php
  $grayscaleImage = $image->grayscale();
  ```

- ### `rgb()`
  Converts the image to RGB color space. This operation affects the instance it's called on, but still returns that
  instance for method chaining.

  Returns:
    - An image object representing the RGB image.

  Example:
  ```php
  $rgbImage = $image->rgb();
  ```

- ### `rgba()`
  Converts the image to RGBA color space. This operation affects the instance it's called on, but still returns that
  instance for method chaining.

  Returns:
    - An image object representing the RGBA image.

  Example:
  ```php
  $rgbaImage = $image->rgba();
  ```

- ### `drawRectangle(int $xMin, int $yMin, int $xMax, int $yMax, string $color = 'FFF', $fill = false, float $thickness = 1)`
  Draws a rectangle on the image with the specified coordinates, color, and thickness. This operation affects the
  instance it's called on, but still returns that instance for method chaining.

  Parameters:
    - `$xMin` (int) The x-coordinate of the top-left corner of the rectangle.
    - `$yMin` (int) The y-coordinate of the top-left corner of the rectangle.
    - `$xMax` (int) The x-coordinate of the bottom-right corner of the rectangle.
    - `$yMax` (int) The y-coordinate of the bottom-right corner of the rectangle.
    - `$color` (string) The color of the rectangle in hexadecimal format. Default is 'FFF'.
    - `$fill` (bool) Whether to fill the rectangle with color. Default is false.
    - `$thickness` (float) The thickness of the rectangle border. Default is 1.

  Returns:
    - An image object with the rectangle drawn.

  Example:
  ```php
  $rectangleImage = $image->drawRectangle(100, 100, 200, 200, 'FF0000', fill: true, thickness: 2);
  ```

- ### `drawText(string $text, int $xPos, int $yPos, string $fontFile, int $fontSize = 16, string $color = 'FFF')`

  Draws text on the image at the specified position with the specified font, size, and color. This operation affects the
  instance it's called on, but still returns that instance for method chaining.

  Parameters:
    - `$text` (string) The text to draw on the image.
    - `$xPos` (int) The x-coordinate of the text position.
    - `$yPos` (int) The y-coordinate of the text position.
    - `$fontFile` (string) The path to the font file to use.
    - `$fontSize` (int) The font size. Default is 16.
    - `$color` (string) The color of the text in hexadecimal format. Default is 'FFF'.

  Returns:
    - An image object with the text drawn.

  Example:
    ```php
    $textImage = $image->drawText('Hello, World!', 100, 100, 'path/to/font.ttf', 24, 'FF0000');
    ```

- ### `save(string $path)`
  Saves the image to the specified file path. The image format is determined by the file extension.

  Parameters:
    - `$path` (string) The path to save the image to.

  Example:
    ```php
    $image->save('path/to/output.jpg');
    ```

## Performance considerations

When using the image class for image processing tasks, consider the following performance tips:

- **Use the appropriate image driver:** Choose the image driver that best suits your requirements. IMAGICK is the
  default
  driver and provides a wide range of features, but GD and VIPS may be more suitable for certain use cases. Test
  different drivers to find the one that offers the best performance for your specific tasks.
- **Optimize image processing operations:** Image processing can be computationally intensive, especially for large
  images or complex operations. Optimize your code by minimizing unnecessary operations and using efficient algorithms
  where possible.

  Here are some results for `toTensor` and `fromTensor` operations on a 256x256 image:
  | Operation | IMAGICK | GD | VIPS |
  |---------------|---------|---------|---------|
  | toTensor | 0.0883s | 0.0955s | 0.1271s |
  | fromTensor | 0.0673s | 0.2078s | 0.0683s |

## Interoperability

The Image class seamlessly integrates with other components of the TransformersPHP framework, such as the `Tensor`. You
can leverage this interoperability to perform advanced image processing tasks within your applications.

```php
use Codewithkyrian\Transformers\Utils\Image;
use Codewithkyrian\Transformers\Utils\Tensor;

// Read an image
$image = Image::read('path/to/image.jpg');

// Convert the image to a tensor
$tensor = $image->toTensor();

// Perform tensor operations
// ...

// Convert the tensor back to an image
$newImage = Image::fromTensor($tensor);

// Save the new image
$newImage->save('path/to/new_image.jpg');
```





  

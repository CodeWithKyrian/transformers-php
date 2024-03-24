<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use Imagine\Image\AbstractImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;

/**
 * Helper file for Image Processing.
 *
 * This class is only used internally,  meaning an end-user shouldn't need to access anything here.
 */
class Image
{
    public static AbstractImagine $imagine;

    public function __construct(public ImageInterface $image, public int $channels = 4)
    {
    }

    public static function read(string $input): static
    {
        if (filter_var($input, FILTER_VALIDATE_URL)) {
            // get from a remote url
        }

        $image = self::$imagine->open($input);

        return new self($image);
    }

    public function height(): int
    {
        return $this->image->getSize()->getHeight();
    }

    public function width(): int
    {
        return $this->image->getSize()->getWidth();
    }

    /**
     * Returns the size of the image (width, height).
     * @return array{int, int}
     */
    public function size(): array
    {
        $size = $this->image->getSize();

        return [$size->getWidth(), $size->getHeight()];
    }

    /**
     * Clone the image.
     * @return Image The cloned image.
     */
    public function clone(): static
    {
        return clone $this;
    }

    /**
     * Convert the image to grayscale format.
     * @return $this
     */
    public function grayscale(): static
    {
        if ($this->channels === 1) {
            return $this;
        }

        $this->image->effects()->grayscale();
        $this->channels = 1;

        return $this;
    }

    /**
     * Convert the image to RGB format.
     * @return $this
     */
    public function rgb(): static
    {
        if ($this->channels === 3) {
            return $this;
        }

        $this->channels = 3;

        return $this;
    }

    /**
     * Convert the image to RGBA format.
     * @return $this
     */
    public function rgba(): static
    {
        if ($this->channels === 4) {
            return $this;
        }

        $this->channels = 4;

        return $this;
    }

    /**
     * Resize the image to the given dimensions.
     */
    public function resize(int $width, int $height, int $resample = 2): static
    {
        $resampleMethod = Resample::tryFrom($resample) ?? Resample::NEAREST;

        $this->image = $this->image->resize(new Box($width, $height), $resampleMethod->toString());

        return $this;
    }

    public function pad(int $left, int $right, int $top, int $bottom): static
    {
        if ($left === 0 && $right === 0 && $top === 0 && $bottom === 0) {
            return $this;
        }

        $originalWidth = $this->image->getSize()->getWidth();
        $originalHeight = $this->image->getSize()->getHeight();

        $newWidth = $originalWidth + $left + $right;
        $newHeight = $originalHeight + $top + $bottom;

        $palette = new RGB();

        $canvas = self::$imagine->create(new Box($newWidth, $newHeight), $palette->color('FFFFFF', 100));

        $canvas->paste($this->image, new Point($left, $top));

        $this->image = $canvas;

        return $this;
    }

    public function crop(int $xMin, int $yMin, int $xMax, int $yMax): static
    {
        $originalWidth = $this->image->getSize()->getWidth();
        $originalHeight = $this->image->getSize()->getHeight();

        if ($xMin === 0 && $yMin === 0 && $xMax === $originalWidth && $yMax === $originalHeight) {
            return $this;
        }
        // Ensure crop bounds are within the image
        $xMin = max($xMin, 0);
        $yMin = max($yMin, 0);
        $xMax = max($xMax, $originalWidth - 1);
        $yMax = max($yMax, $originalHeight - 1);

        $cropWidth = $xMax - $xMin + 1;
        $cropHeight = $yMax - $yMin + 1;

        $croppedImage = $this->image->crop(new Point($xMin, $yMin), new Box($cropWidth, $cropHeight));

        $this->image = $croppedImage;

        return $this;
    }

    public function centerCrop(int $cropWidth, int $cropHeight): static
    {
        $originalWidth = $this->image->getSize()->getWidth();
        $originalHeight = $this->image->getSize()->getHeight();

        if ($originalWidth === $cropWidth && $originalHeight === $cropHeight) {
            return $this;
        }

        // Calculate the coordinates for center cropping
        $xOffset = max(0, ($originalWidth - $cropWidth) / 2);
        $yOffset = max(0, ($originalHeight - $cropHeight) / 2);

        $croppedImage = $this->image->crop(new Point($xOffset, $yOffset), new Box($cropWidth, $cropHeight));

        $this->image = $croppedImage;

        return $this;
    }

    public function toTensor(string $channelFormat = 'CHW'): Tensor
    {
        $width = $this->image->getSize()->getWidth();
        $height = $this->image->getSize()->getHeight();

        $pixels = $this->pixelData();

        $tensor = new Tensor($pixels, shape: [$width, $height, $this->channels]);

        if ($channelFormat === 'HWC') {
            // Do nothing
        } else if ($channelFormat === 'CHW') { // hwc -> chw
            $tensor = $tensor->permute(2, 0, 1);
        } else {
            throw new \Exception("Unsupported channel format: $channelFormat");
        }

        return $tensor;
    }

    public function save(string $path): void
    {
        $this->image->save($path);
    }

    /**
     * @return array
     */
    public function pixelData(): array
    {
        $width = $this->image->getSize()->getWidth();
        $height = $this->image->getSize()->getHeight();

        // Initialize an array to store pixel values
        $pixels = [];

        // Iterate over each pixel in the image
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                // Get the color of the pixel
                $color = $this->image->getColorAt(new Point($x, $y));

                // Extract the color components based on the number of channels
                if ($this->channels >= 1) {
                    $pixels[] = $color->getRed();
                }
                if ($this->channels >= 2) {
                    $pixels[] = $color->getGreen();
                }
                if ($this->channels >= 3) {
                    $pixels[] = $color->getBlue();
                }
                if ($this->channels >= 4) {
                    $pixels[] = $color->getAlpha();
                }
            }
        }
        return $pixels;
    }
}
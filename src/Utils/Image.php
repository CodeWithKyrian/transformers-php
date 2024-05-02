<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

use Exception;
use Imagick;
use Imagine\Image\AbstractImagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
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
        if ($this->image instanceof \Imagine\Vips\Image) {
            $this->channels = $this->image->getVips()->bands;
        }
    }

    public static function read(string $input, array $options = []): static
    {
        $image = self::$imagine->open($input, $options);

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
     * Resize the image to the given dimensions.
     */
    public function resize(int $width, int $height, int|Resample $resample = 2): static
    {
        $resampleMethod = $resample instanceof Resample ? $resample : Resample::from($resample) ?? Resample::NEAREST;

        $this->image = $this->image->resize(new Box($width, $height), $resampleMethod->toString());

        return $this;
    }

    /**
     * Resize the image to make a thumbnail.
     */
    public function thumbnail(int $width, int $height, int|Resample $resample = 2): static
    {
        $inputHeight = $this->height();
        $inputWidth = $this->width();


        // We always resize to the smallest of either the input or output size.
        $height = min($inputHeight, $height);
        $width = min($inputWidth, $width);

        if ($height === $inputHeight && $width === $inputWidth) {
            return $this;
        }

        if ($inputHeight > $inputWidth) {
            $width = floor($inputWidth * $height / $inputHeight);
        } elseif ($inputWidth > $inputHeight) {
            $height = floor($inputHeight * $width / $inputWidth);
        }

        $this->resize($width, $height, $resample);

        return $this;
    }

    /**
     * Convert the image to grayscale format.
     * @return $this
     */
    public function grayscale(bool $force = false): static
    {
        if ($this->channels === 1 && !$force) {
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
    public function rgb(bool $force = false): static
    {
        // If the image is already in RGB format, return it as is
        if ($this->channels === 3 && !$force) {
            return $this;
        }

        $this->channels = 3;

        // If it's a Vips image, we can extract the RGB channels
        if ($this->image instanceof \Imagine\Vips\Image) {
            $this->channels = 3;

            $vipImage = $this->image->getVips()->extract_band(0, ['n' => 3]);

            $this->image = $this->image->setVips($vipImage);

            return $this;
        }

        return $this;
    }

    /**
     * Convert the image to RGBA format.
     * @return $this
     */
    public function rgba(bool $force = false): static
    {
        // If the image is already in RGBA format, return it as is
        if ($this->channels === 4 && !$force) {
            return $this;
        }

        $this->channels = 4;

        // If it's a Vips image, we can handle the RGBA channels
        if ($this->image instanceof \Imagine\Vips\Image) {
            $this->channels = 4;

            $vipImage = $this->image->getVips();

            $vipImage = $vipImage->hasAlpha() ? $vipImage->extract_band(0, ['n' => 4]) : $vipImage->bandjoin([255]);

            $this->image = $this->image->setVips($vipImage);

            return $this;
        }

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

    public function pad(int $left, int $right, int $top, int $bottom): static
    {
        if ($left === 0 && $right === 0 && $top === 0 && $bottom === 0) {
            return $this;
        }

        $originalWidth = $this->image->getSize()->getWidth();
        $originalHeight = $this->image->getSize()->getHeight();

        $newWidth = $originalWidth + $left + $right;
        $newHeight = $originalHeight + $top + $bottom;

        $canvas = self::$imagine->create(new Box($newWidth, $newHeight));

        $canvas->paste($this->image, new Point($left, $top));

        $this->image = $canvas;

        // Convert to the same format as the original image
        if ($this->channels === 1) {
            $this->grayscale(true);
        } elseif ($this->channels === 3) {
            $this->rgb(true);
        } elseif ($this->channels === 4) {
            $this->rgba(true);
        }

        return $this;
    }

    public static function fromTensor(Tensor $tensor, string $channelFormat = 'CHW'): static
    {
        $tensor = $channelFormat === 'CHW' ? $tensor->permute(1, 2, 0) : $tensor;

        [$width, $height, $channels] = $tensor->shape();

        $image = self::$imagine->create(new Box($width, $height));

        if ($image instanceof \Imagine\Vips\Image) {
            $data = pack('C*', ...$tensor->buffer()->toArray());

            $vipImage = $image->getVips()::newFromMemory($data, $width, $height, $channels, 'uchar');

            $image->setVips($vipImage, true);

            return new self($image, $channels);
        }

        if ($image instanceof \Imagine\Imagick\Image) {
            $map = match ($channels) {
                1 => 'I',
                2 => 'RG',
                3 => 'RGB',
                4 => 'RGBA',
                default => throw new Exception("Unsupported number of channels: $channels"),
            };

            $bufferArray = [];
            for ($i = 0; $i < $tensor->size(); $i++) {
                $bufferArray[] = $tensor->buffer()[$i];
            }

            $image->getImagick()->importImagePixels(0, 0, $width, $height, $map, Imagick::PIXEL_CHAR, $bufferArray);

            return new self($image, $channels);
        }

        $pixels = $tensor->reshape([$width * $height, $channels])->toArray();

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $index = $y * $width + $x;

                $color = $channels === 1 ? $pixels[$index][0] : $pixels[$index];

                $color = $image->palette()->color([$color[0], $color[1], $color[2]], $color[3] ?? null);

                $image->draw()->dot(new Point($x, $y), $color);
            }
        }

        return new self($image, $channels);
    }

    public function toTensor(string $channelFormat = 'CHW'): Tensor
    {
        $width = $this->image->getSize()->getWidth();
        $height = $this->image->getSize()->getHeight();

        $pixels = $this->pixelData();

        $tensor = new Tensor($pixels, Tensor::float32,  [$height, $width, $this->channels]);

        if ($channelFormat === 'HWC') {
            // Do nothing
        } else if ($channelFormat === 'CHW') { // hwc -> chw
            $tensor = $tensor->permute(2, 0, 1);
        } else {
            throw new Exception("Unsupported channel format: $channelFormat");
        }

        return $tensor;
    }

    /**
     * @return array
     */
    public function pixelData(): array
    {
        $width = $this->image->getSize()->getWidth();
        $height = $this->image->getSize()->getHeight();

        // If it's a Vips image, we can extract the pixel data directly
        if ($this->image instanceof \Imagine\Vips\Image) {
            return $this->image->getVips()->writeToArray();
        }

        // If it's an Imagick image, we can export the pixel data directly
        if ($this->image instanceof \Imagine\Imagick\Image) {
            $map = match ($this->channels) {
                1 => 'I',
                2 => 'RG',
                3 => 'RGB',
                4 => 'RGBA',
                default => throw new Exception("Unsupported number of channels: $this->channels"),
            };

            return $this->image->getImagick()->exportImagePixels(0, 0, $width, $height, $map, Imagick::PIXEL_CHAR);
        }

        // I didn't find an in-built method to extract pixel data from a GD image, so I'm using this ugly
        // brute-force method, suggested by @DewiMorgan on StackOverflow: https://stackoverflow.com/a/30136602/11209184.
        // It's faster than other methods I tried, and rivals the speed of the Imagick method so I'll keep it for now.
        $alphaLookup = [
            0x00000000 => "\xff", 0x01000000 => "\xfd", 0x02000000 => "\xfb", 0x03000000 => "\xf9",
            0x04000000 => "\xf7", 0x05000000 => "\xf5", 0x06000000 => "\xf3", 0x07000000 => "\xf1",
            0x08000000 => "\xef", 0x09000000 => "\xed", 0x0a000000 => "\xeb", 0x0b000000 => "\xe9",
            0x0c000000 => "\xe7", 0x0d000000 => "\xe5", 0x0e000000 => "\xe3", 0x0f000000 => "\xe1",
            0x10000000 => "\xdf", 0x11000000 => "\xdd", 0x12000000 => "\xdb", 0x13000000 => "\xd9",
            0x14000000 => "\xd7", 0x15000000 => "\xd5", 0x16000000 => "\xd3", 0x17000000 => "\xd1",
            0x18000000 => "\xcf", 0x19000000 => "\xcd", 0x1a000000 => "\xcb", 0x1b000000 => "\xc9",
            0x1c000000 => "\xc7", 0x1d000000 => "\xc5", 0x1e000000 => "\xc3", 0x1f000000 => "\xc1",
            0x20000000 => "\xbf", 0x21000000 => "\xbd", 0x22000000 => "\xbb", 0x23000000 => "\xb9",
            0x24000000 => "\xb7", 0x25000000 => "\xb5", 0x26000000 => "\xb3", 0x27000000 => "\xb1",
            0x28000000 => "\xaf", 0x29000000 => "\xad", 0x2a000000 => "\xab", 0x2b000000 => "\xa9",
            0x2c000000 => "\xa7", 0x2d000000 => "\xa5", 0x2e000000 => "\xa3", 0x2f000000 => "\xa1",
            0x30000000 => "\x9f", 0x31000000 => "\x9d", 0x32000000 => "\x9b", 0x33000000 => "\x99",
            0x34000000 => "\x97", 0x35000000 => "\x95", 0x36000000 => "\x93", 0x37000000 => "\x91",
            0x38000000 => "\x8f", 0x39000000 => "\x8d", 0x3a000000 => "\x8b", 0x3b000000 => "\x89",
            0x3c000000 => "\x87", 0x3d000000 => "\x85", 0x3e000000 => "\x83", 0x3f000000 => "\x81",
            0x40000000 => "\x7f", 0x41000000 => "\x7d", 0x42000000 => "\x7b", 0x43000000 => "\x79",
            0x44000000 => "\x77", 0x45000000 => "\x75", 0x46000000 => "\x73", 0x47000000 => "\x71",
            0x48000000 => "\x6f", 0x49000000 => "\x6d", 0x4a000000 => "\x6b", 0x4b000000 => "\x69",
            0x4c000000 => "\x67", 0x4d000000 => "\x65", 0x4e000000 => "\x63", 0x4f000000 => "\x61",
            0x50000000 => "\x5f", 0x51000000 => "\x5d", 0x52000000 => "\x5b", 0x53000000 => "\x59",
            0x54000000 => "\x57", 0x55000000 => "\x55", 0x56000000 => "\x53", 0x57000000 => "\x51",
            0x58000000 => "\x4f", 0x59000000 => "\x4d", 0x5a000000 => "\x4b", 0x5b000000 => "\x49",
            0x5c000000 => "\x47", 0x5d000000 => "\x45", 0x5e000000 => "\x43", 0x5f000000 => "\x41",
            0x60000000 => "\x3f", 0x61000000 => "\x3d", 0x62000000 => "\x3b", 0x63000000 => "\x39",
            0x64000000 => "\x37", 0x65000000 => "\x35", 0x66000000 => "\x33", 0x67000000 => "\x31",
            0x68000000 => "\x2f", 0x69000000 => "\x2d", 0x6a000000 => "\x2b", 0x6b000000 => "\x29",
            0x6c000000 => "\x27", 0x6d000000 => "\x25", 0x6e000000 => "\x23", 0x6f000000 => "\x21",
            0x70000000 => "\x1f", 0x71000000 => "\x1d", 0x72000000 => "\x1b", 0x73000000 => "\x19",
            0x74000000 => "\x17", 0x75000000 => "\x15", 0x76000000 => "\x13", 0x77000000 => "\x11",
            0x78000000 => "\x0f", 0x79000000 => "\x0d", 0x7a000000 => "\x0b", 0x7b000000 => "\x09",
            0x7c000000 => "\x07", 0x7d000000 => "\x05", 0x7e000000 => "\x03", 0x7f000000 => "\x00"
        ]; // Lookup table for chr(255-(($x >> 23) & 0x7f)).

        $chr = [
            "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", "\x09", "\x0A", "\x0B", "\x0C", "\x0D", "\x0E", "\x0F",
            "\x10", "\x11", "\x12", "\x13", "\x14", "\x15", "\x16", "\x17", "\x18", "\x19", "\x1A", "\x1B", "\x1C", "\x1D", "\x1E", "\x1F",
            "\x20", "\x21", "\x22", "\x23", "\x24", "\x25", "\x26", "\x27", "\x28", "\x29", "\x2A", "\x2B", "\x2C", "\x2D", "\x2E", "\x2F",
            "\x30", "\x31", "\x32", "\x33", "\x34", "\x35", "\x36", "\x37", "\x38", "\x39", "\x3A", "\x3B", "\x3C", "\x3D", "\x3E", "\x3F",
            "\x40", "\x41", "\x42", "\x43", "\x44", "\x45", "\x46", "\x47", "\x48", "\x49", "\x4A", "\x4B", "\x4C", "\x4D", "\x4E", "\x4F",
            "\x50", "\x51", "\x52", "\x53", "\x54", "\x55", "\x56", "\x57", "\x58", "\x59", "\x5A", "\x5B", "\x5C", "\x5D", "\x5E", "\x5F",
            "\x60", "\x61", "\x62", "\x63", "\x64", "\x65", "\x66", "\x67", "\x68", "\x69", "\x6A", "\x6B", "\x6C", "\x6D", "\x6E", "\x6F",
            "\x70", "\x71", "\x72", "\x73", "\x74", "\x75", "\x76", "\x77", "\x78", "\x79", "\x7A", "\x7B", "\x7C", "\x7D", "\x7E", "\x7F",
            "\x80", "\x81", "\x82", "\x83", "\x84", "\x85", "\x86", "\x87", "\x88", "\x89", "\x8A", "\x8B", "\x8C", "\x8D", "\x8E", "\x8F",
            "\x90", "\x91", "\x92", "\x93", "\x94", "\x95", "\x96", "\x97", "\x98", "\x99", "\x9A", "\x9B", "\x9C", "\x9D", "\x9E", "\x9F",
            "\xA0", "\xA1", "\xA2", "\xA3", "\xA4", "\xA5", "\xA6", "\xA7", "\xA8", "\xA9", "\xAA", "\xAB", "\xAC", "\xAD", "\xAE", "\xAF",
            "\xB0", "\xB1", "\xB2", "\xB3", "\xB4", "\xB5", "\xB6", "\xB7", "\xB8", "\xB9", "\xBA", "\xBB", "\xBC", "\xBD", "\xBE", "\xBF",
            "\xC0", "\xC1", "\xC2", "\xC3", "\xC4", "\xC5", "\xC6", "\xC7", "\xC8", "\xC9", "\xCA", "\xCB", "\xCC", "\xCD", "\xCE", "\xCF",
            "\xD0", "\xD1", "\xD2", "\xD3", "\xD4", "\xD5", "\xD6", "\xD7", "\xD8", "\xD9", "\xDA", "\xDB", "\xDC", "\xDD", "\xDE", "\xDF",
            "\xE0", "\xE1", "\xE2", "\xE3", "\xE4", "\xE5", "\xE6", "\xE7", "\xE8", "\xE9", "\xEA", "\xEB", "\xEC", "\xED", "\xEE", "\xEF",
            "\xF0", "\xF1", "\xF2", "\xF3", "\xF4", "\xF5", "\xF6", "\xF7", "\xF8", "\xF9", "\xFA", "\xFB", "\xFC", "\xFD", "\xFE", "\xFF",
        ]; // Lookup for chr($x): much faster.

        $imageData = match ($this->channels) {
            1 => str_repeat("\x00", $width * $height),
            2 => str_repeat("\x00\x00", $width * $height),
            3 => str_repeat("\x00\x00\x00", $width * $height),
            4 => str_repeat("\x00\x00\x00\x00", $width * $height),
            default => throw new Exception("Unsupported number of channels: $this->channels"),
        };


        // Loop over each single pixel.
        $j = 0;
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                // Grab the pixel data.
                $argb = imagecolorat($this->image->getGdResource(), $x, $y);

                if ($this->channels >= 1) {
                    $imageData[$j++] = $chr[($argb >> 16) & 0xFF]; // R
                }
                if ($this->channels >= 2) {
                    $imageData[$j++] = $chr[($argb >> 8) & 0xFF]; // G
                }
                if ($this->channels >= 3) {
                    $imageData[$j++] = $chr[$argb & 0xFF]; // B
                }
                if ($this->channels >= 4) {
                    $imageData[$j++] = $alphaLookup[$argb & 0x7f000000]; // A
                }
            }
        }

        $data = unpack('C*', $imageData);

        return array_values($data);
    }

    public function save(string $path): void
    {
        $this->image->save($path);
    }

    public function drawRectangle(int $xMin, int $yMin, int $xMax, int $yMax, string $color = 'FFF', $fill = false, float $thickness = 1): void
    {
        $this->image->draw()->rectangle(
            new Point($xMin, $yMin),
            new Point($xMax, $yMax),
            $this->image->palette()->color($color),
            $fill,
            $thickness
        );
    }

    public function drawText(string $text, int $xPos, int $yPos, string $fontFile, int $fontSize = 16, string $color = 'FFF'): void
    {
        $font = self::$imagine->font($fontFile, $fontSize, $this->image->palette()->color($color));

        $position = new Point($xPos, $yPos);

        $this->image->draw()->text($text, $font, $position);
    }
}
<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\FeatureExtractors;

use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Utils\Image;
use Exception;

class ImageFeatureExtractor extends FeatureExtractor
{
    /** The mean values for image normalization. */
    protected int|array|null $imageMean;

    /** The standard deviation values for image normalization. */
    protected int|array|null $imageStd;

    /* What method to use for resampling. */
    protected int $resample;

    /** Whether to rescale the image pixel values to the [0,1] range. */
    protected bool $doRescale;

    /** The factor to use for rescaling the image pixel values. */
    protected float $rescaleFactor;

    /** Whether to normalize the image pixel values. */
    protected ?bool $doNormalize;

    /**  Whether to resize the image. */
    protected ?bool $doResize;

    /**  The size to resize the image to. */
    protected ?bool $doThumbnail;

    /**  The size to resize the image to. */
    protected ?array $size;
    protected mixed $sizeDivisibility;
    protected ?bool $doCenterCrop;
    protected array|int|null $cropSize;
    protected ?bool $doConvertRGB;
    protected ?bool $doCropMargin;
    protected array|int|null $padSize;
    protected ?bool $doPad;

    public function __construct(public array $config)
    {
        parent::__construct($config);

        $this->imageMean = $config['image_mean'] ?? $config['mean'] ?? null;
        $this->imageStd = $config['image_std'] ?? $config['std'] ?? null;

        $this->resample = $config['resample'] ?? 2; // 2 => bilinear
        $this->doRescale = $config['do_rescale'] ?? true;
        $this->rescaleFactor = $config['rescale_factor'] ?? (1 / 255);
        $this->doNormalize = $config['do_normalize'] ?? null;

        $this->doResize = $config['do_resize'] ?? null;
        $this->doThumbnail = $config['do_thumbnail'] ?? null;
        $this->size = $config['size'] ?? null;
        $this->sizeDivisibility = $config['size_divisibility'] ?? $config['size_divisor'] ?? null;

        $this->doCenterCrop = $config['do_center_crop'] ?? null;
        $this->cropSize = $config['crop_size'] ?? null;
        $this->doConvertRGB = $config['do_convert_rgb'] ?? true;
        $this->doCropMargin = $config['do_crop_margin'] ?? null;

        $this->padSize = $config['pad_size'] ?? null;
        $this->doPad = $config['do_pad'] ?? null;

        if ($this->doPad && !$this->padSize && $this->size && isset($this->size['width']) && isset($this->size['height'])) {
            // Should pad, but no pad size specified
            // We infer the pad size from the resize size
            $this->padSize = $this->size;
        }
    }

    /**
     * Pad the image by a certain amount.
     *
     * @param Tensor $imageTensor The pixel data to pad.
     * @param int[]|int $padSize The dimensions of the padded image.
     * @param string $mode The type of padding to add.
     * @param bool $center Whether to center the image.
     * @param int $constantValues The constant value to use for padding.
     *
     * @return Tensor The padded pixel data and image dimensions.
     * @throws Exception
     */
    public function padImage(
        Tensor    $imageTensor,
        int|array $padSize,
        string    $tensorFormat = 'CHW', // 'HWC' or 'CHW
        string    $mode = 'constant',
        bool      $center = false,
        int       $constantValues = 0
    ): Tensor {
        if ($tensorFormat === 'CHW') {
            [$imageChannels, $imageHeight, $imageWidth] = $imageTensor->shape();
        } else {
            [$imageHeight, $imageWidth, $imageChannels] = $imageTensor->shape();
        }

        if (is_array($padSize)) {
            $paddedImageWidth = $padSize['width'];
            $paddedImageHeight = $padSize['height'];
        } else {
            $paddedImageWidth = $padSize;
            $paddedImageHeight = $padSize;
        }

        // Only add padding if there is a difference in size
        if ($paddedImageWidth !== $imageWidth || $paddedImageHeight !== $imageHeight) {
            $paddedShape = [$paddedImageWidth, $paddedImageHeight, $imageChannels];

            if (is_array($constantValues)) {
                $paddedPixelData = Tensor::fill($paddedShape, 0);

                // Fill with constant values, cycling through the array
                $constantValuesLength = count($constantValues);
                for ($i = 0; $i < $paddedPixelData->size(); ++$i) {
                    $paddedPixelData->buffer()[$i] = $constantValues[$i % $constantValuesLength];
                }
            } else {
                $paddedPixelData = Tensor::fill($paddedShape, $constantValues);
            }

            [$left, $top] = $center ?
                [floor(($paddedImageWidth - $imageWidth) / 2), floor(($paddedImageHeight - $imageHeight) / 2)] :
                [0, 0];

            // Copy the original image into the padded image
            for ($i = 0; $i < $imageHeight; ++$i) {
                $a = ($i + $top) * $paddedImageWidth;
                $b = $i * $imageWidth;

                for ($j = 0; $j < $imageWidth; ++$j) {
                    $c = ($a + $j + $left) * $imageChannels;
                    $d = ($b + $j) * $imageChannels;

                    for ($k = 0; $k < $imageChannels; ++$k) {
                        $paddedPixelData->buffer()[$c + $k] = $imageTensor->buffer()[$d + $k];
                    }
                }
            }

            if ($mode === 'symmetric') {
                if ($center) {
                    throw new Exception('`center` padding is not supported when `mode` is set to `symmetric`.');
                    // TODO: Implement this
                }
                $h1 = $imageHeight - 1;
                $w1 = $imageWidth - 1;
                for ($i = 0; $i < $paddedImageHeight; ++$i) {
                    $a = $i * $paddedImageWidth;
                    $b = $this->calculateReflectOffset($i, $h1) * $imageWidth;

                    for ($j = 0; $j < $paddedImageWidth; ++$j) {
                        if ($i < $imageHeight && $j < $imageWidth) continue; // Do not overwrite original image

                        $c = ($a + $j) * $imageChannels;
                        $d = ($b + $this->calculateReflectOffset($j, $w1)) * $imageChannels;

                        // Copy channel-wise
                        for ($k = 0; $k < $imageChannels; ++$k) {
                            $paddedPixelData->buffer()[$c + $k] = $imageTensor->buffer()[$d + $k];
                        }
                    }
                }
            }

            // Update pixel data and image dimensions
            $imageTensor = $paddedPixelData;
        }

        return $imageTensor;
    }

    private function calculateReflectOffset(int $val, int $max): int
    {
        $mod = $val % ($max * 2);
        return $mod > $max ? $max - ($mod - $max) : $mod;
    }


    /**
     * Find the target (width, height) dimension of the output image after
     * resizing given the input image and the desired size.
     *
     * @param Image $image The image to be resized.
     * @param int|array|null $size The size to use for resizing the image.
     *
     * @return array The target (width, height) dimension of the output image after resizing.
     * @throws Exception
     */
    public function getResizeOutputImageSize(Image $image, int|array|null $size): array
    {
        [$srcWidth, $srcHeight] = $image->size();

        $longestEdge = $shortestEdge = null;

        if ($this->doThumbnail) {
            // Custom logic for Donut models
            $shortestEdge = min($size['height'], $size['width']);
        } elseif (is_int($size)) {
            // Backward compatibility with integer size
            $shortestEdge = $size;
            $longestEdge = $this->config['max_size'] ?? $shortestEdge;
        } elseif ($size != null) {
            // Extract known properties from size
            $shortestEdge = $size['shortest_edge'] ?? null;
            $longestEdge = $size['longest_edge'] ?? null;
        }


        // If `$longestEdge` and `$shortestEdge` are set, maintain aspect ratio and resize to `$shortestEdge`
        // while keeping the largest dimension <= `$shortestEdge`
        if ($shortestEdge != null || $longestEdge != null) {
            // Try resize so that shortest edge is shortestEdge (target)
            $shortResizeFactor = $shortestEdge !== null
                ? max($shortestEdge / $srcWidth, $shortestEdge / $srcHeight)
                : 1;

            $newWidth = $srcWidth * $shortResizeFactor;
            $newHeight = $srcHeight * $shortResizeFactor;

            // The new width and height might be greater than `longest_edge`, so
            // we downscale to ensure the largest dimension is longestEdge
            $longResizeFactor = $longestEdge !== null
                ? min($longestEdge / $newWidth, $longestEdge / $newHeight)
                : 1;

            // Round to avoid floating point precision issues
            $finalWidth = (int)floor(round($srcWidth * $longResizeFactor, 2));
            $finalHeight = (int)floor(round($srcHeight * $longResizeFactor, 2));

            if ($this->sizeDivisibility !== null) {
                [$finalWidth, $finalHeight] = $this->enforceSizeDivisibility([$finalWidth, $finalHeight], $this->sizeDivisibility);
            }

            return [$finalWidth, $finalHeight];
        } elseif (isset($size['width'], $size['height'])) {
            // Resize to the specified dimensions
            $newWidth = $size['width'];
            $newHeight = $size['height'];

            // Custom logic for DPT models
            if ($this->config['keep_aspect_ratio'] ?? null && $this->config['ensure_multiple_of'] ?? null) {
                $scaleHeight = $newHeight / $srcHeight;
                $scaleWidth = $newWidth / $srcWidth;

                if (abs(1 - $scaleWidth) < abs(1 - $scaleHeight)) {
                    // Fit width
                    $scaleHeight = $scaleWidth;
                } else {
                    // Fit height
                    $scaleWidth = $scaleHeight;
                }

                $newHeight = $this->constraintToMultipleOf($scaleHeight * $srcHeight, $this->config['ensure_multiple_of']);
                $newWidth = $this->constraintToMultipleOf($scaleWidth * $srcWidth, $this->config['ensure_multiple_of']);
            }

            return [$newWidth, $newHeight];
        } elseif ($this->sizeDivisibility != null) {
            return $this->enforceSizeDivisibility([$srcWidth, $srcHeight], $this->sizeDivisibility);
        } else {
            throw new Exception("Could not resize image due to unsupported 'size' parameter passed: " . json_encode($size));
        }
    }


    /**
     * Preprocesses the given image.
     *
     * @param Image $image The image to preprocess.
     * @param ?bool $doNormalize Whether to normalize the image.
     * @param ?bool $doPad Whether to pad the image.
     * @param ?bool $doConvertRGB Whether to convert the image to RGB.
     * @param ?bool $doConvertGrayscale Whether to convert the image to grayscale.
     *
     * @return array The preprocessed image.
     * @throws Exception
     */
    public function preprocess(
        Image $image,
        ?bool $doNormalize = null,
        ?bool $doPad = null,
        ?bool $doConvertRGB = null,
        ?bool $doConvertGrayscale = null
    ): array {
        if ($this->doCropMargin) {
            // Specific to nougat processors. This is done before resizing,
            // and can be interpreted as a pre-preprocessing step.
            $image = $image->cropMargin();
        }

        $originalInputSize = $image->size(); // original image size

        // Convert image to RGB if specified in config.
        if ($doConvertRGB ?? $this->doConvertRGB) {
            $image = $image->rgb();
        } elseif ($doConvertGrayscale) {
            $image = $image->grayscale();
        }

        // Resize if specified in config.
        if ($this->doResize) {
            [$newWidth, $newHeight] = $this->getResizeOutputImageSize($image, $this->size);

            $image = $image->resize($newWidth, $newHeight, $this->resample);
        }

        // Resize the image using thumbnail method.
        if ($this->doThumbnail) {
            $image = $image->thumbnail($this->size['width'], $this->size['height'], $this->resample);
        }

        if ($this->doCenterCrop) {

            if (is_int($this->cropSize)) {
                $cropWidth = $this->cropSize;
                $cropHeight = $this->cropSize;
            } else {
                $cropWidth = $this->cropSize['width'];
                $cropHeight = $this->cropSize['height'];
            }

            $image = $image->centerCrop($cropWidth, $cropHeight);
        }

        $reshapedInputSize = $image->size();

        $imageTensor = $image->toTensor();

        if ($this->doRescale) {
            $imageTensor = $imageTensor->multiply($this->rescaleFactor);
        }

        if ($doNormalize ?? $this->doNormalize) {
            if (is_array($this->imageMean)) {
                // Negate the mean values to add instead of subtract
                $negatedMean = array_map(fn($mean) => -$mean, $this->imageMean);
                $imageMean = Tensor::repeat($negatedMean, $image->height() * $image->width(), 1);
            } else {
                $imageMean = Tensor::fill([$image->channels * $image->height() * $image->width()], -$this->imageMean);
            }


            if (is_array($this->imageStd)) {
                // Inverse the standard deviation values to multiple instead of divide
                $inversedStd = array_map(fn($std) => 1 / $std, $this->imageStd);
                $imageStd = Tensor::repeat($inversedStd, $image->height() * $image->width(), 1);
            } else {
                $imageStd = Tensor::fill([$image->channels * $image->height() * $image->width()], 1 / $this->imageStd);
            }


            // Reshape mean and std to match the image tensor shape
            $imageMean = $imageMean->reshape($imageTensor->shape());
            $imageStd = $imageStd->reshape($imageTensor->shape());

            if (count($imageMean) !== $image->channels || count($imageStd) !== $image->channels) {
                throw new Exception("When set to arrays, the length of `imageMean` (" . count($imageMean) . ") and `imageStd` (" . count($imageStd) . ") must match the number of channels in the image ({$image->channels}).");
            }

            // Normalize pixel data
            $imageTensor = $imageTensor->add($imageMean)->multiply($imageStd);
        }

        // Perform padding after rescaling/normalizing
        if ($doPad ?? $this->doPad) {
            if ($this->padSize !== null) {
                $imageTensor = $this->padImage($imageTensor, $this->padSize);
            } elseif ($this->sizeDivisibility !== null) {
                [$paddedWidth, $paddedHeight] = $this->enforceSizeDivisibility([$imageTensor->shape()[1], $imageTensor->shape()[0]], $this->sizeDivisibility);
                $imageTensor = $this->padImage($imageTensor, ['width' => $paddedWidth, 'height' => $paddedHeight]);
            }
        }

        return [
            'original_size' => $originalInputSize,
            'reshaped_input_size' => $reshapedInputSize,
            'pixel_values' => $imageTensor,
        ];
    }

    /**
     * Calls the feature extraction process on an array of images,
     * preprocesses each image, and concatenates the resulting
     * features into a single Tensor.
     *
     * @param Image|Image[] $input The image(s) to extract features from.
     * @param mixed ...$args Additional arguments.
     *
     * @return array An object containing the concatenated pixel values (and other metadata) of the preprocessed images.
     */
    public function __invoke($input, ...$args): array
    {
        $images = is_array($input) ? $input : [$input];

        if (count($images) === 0) {
            throw new Exception('No images provided');
        }

        $imageData = array_map([$this, 'preprocess'], $images);

        $pixelValues = array_column($imageData, 'pixel_values');
        $originalSizes = array_column($imageData, 'original_size');
        $reshapedInputSizes = array_column($imageData, 'reshaped_input_size');

        return [
            'pixel_values' => Tensor::stack($pixelValues),
            'original_sizes' => $originalSizes,
            'reshaped_input_sizes' => $reshapedInputSizes
        ];
    }

    /**
     * Rounds the height and width down to the closest multiple of size_divisibility
     *
     * @param array{int, int} $size The size of the image
     * @param int $divisor The divisor to use.
     *
     * @return array{int, int} The rounded size.
     */
    private function enforceSizeDivisibility(array $size, int $divisor): array
    {
        [$width, $height] = $size;

        $newWidth = max(intdiv($width, $divisor), 1) * $divisor;
        $newHeight = max(intdiv($height, $divisor), 1) * $divisor;

        return [$newWidth, $newHeight];
    }

    /**
     *  Constrain a value to be a multiple of a number.
     *
     * @param int $val The value to constrain.
     * @param int $multiple The number to constrain to.
     * @param int $minVal The minimum value to constrain to.
     * @param int|null $maxVal The maximum value to constrain to.
     *
     * @return int
     */
    private function constraintToMultipleOf(int $val, int $multiple, int $minVal = 0, ?int $maxVal = null): int
    {
        $a = $val / $multiple;

        $x = round($a, 0, PHP_ROUND_HALF_EVEN) * $multiple;

        if ($maxVal !== null && $x > $maxVal) {
            $x = floor($a) * $multiple;
        }

        if ($x < $minVal) {
            $x = ceil($a) * $multiple;
        }

        return (int)$x;
    }
}

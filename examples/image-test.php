<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\Transformers\Utils\Image;
use Codewithkyrian\Transformers\Utils\ImageDriver;
use Codewithkyrian\Transformers\Utils\Tensor;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

function toTensorTest(ImageDriver $imageDriver): Tensor
{
    timeUsage();

    Transformers::setup()
        ->setImageDriver($imageDriver)
        ->apply();

    $image = Image::read('images/butterfly.jpg');

    $image->rgb();

    $tensor =  $image->toTensor();

    dump("$imageDriver->name (toTensor) : ". timeUsage(true));

    return $tensor;
}

function fromTensorTest(ImageDriver $imageDriver, Tensor $tensor) : Image
{
    Transformers::setup()
        ->setImageDriver($imageDriver)
        ->apply();

    $image =  Image::fromTensor($tensor);

    dump("$imageDriver->name (fromTensor) : ". timeUsage(true));

    return $image;
}


// Run the test
dump("------------ toTensor ------------");
$tensor = toTensorTest(ImageDriver::IMAGICK);
$tensor = toTensorTest(ImageDriver::GD);
//$tensor = toTensorTest(ImageDriver::VIPS);


dump("------------ fromTensor ------------");
$image = fromTensorTest(ImageDriver::IMAGICK, $tensor);
$image = fromTensorTest(ImageDriver::GD, $tensor);
//$image = fromTensorTest(ImageDriver::VIPS, $tensor);

// Save the image
$image->save('images/butterfly-converted.jpg');

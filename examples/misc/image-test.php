<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\Transformers\Utils\Image;
use Codewithkyrian\Transformers\Utils\ImageDriver;
use function Codewithkyrian\Transformers\Utils\timeUsage;

require_once './bootstrap.php';

ini_set('memory_limit', '2048M');

function toTensorTest(ImageDriver $imageDriver): Tensor
{
    timeUsage();

    Transformers::setup()
        ->setImageDriver($imageDriver)
        ->apply();

    $url = __DIR__.'/../images/kyrian-cartoon.jpeg';
    $tensor = Image::read($url)
        ->rgb()
        ->thumbnail(101, 101)
        ->toTensor();

    dump("$imageDriver->name (toTensor) : ".timeUsage(true));

    return $tensor;
}

function fromTensorTest(ImageDriver $imageDriver, Tensor $tensor): Image
{
    Transformers::setup()
        ->setImageDriver($imageDriver)
        ->apply();

    $image = Image::fromTensor($tensor);

    dump("$imageDriver->name (fromTensor) : ".timeUsage(true));

    return $image;
}


// Run the test
dump("------------ toTensor ------------");
$tensor = toTensorTest(ImageDriver::IMAGICK);
$tensor = toTensorTest(ImageDriver::GD);
$tensor = toTensorTest(ImageDriver::VIPS);


dump("------------ fromTensor ------------");
$image = fromTensorTest(ImageDriver::IMAGICK, $tensor);
$image = fromTensorTest(ImageDriver::GD, $tensor);
$image = fromTensorTest(ImageDriver::VIPS, $tensor);

// Save the image
//$image->save('images/images/kyrian-cartoon-converted.jpeg');

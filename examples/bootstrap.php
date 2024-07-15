<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\Transformers\Utils\ImageDriver;

require_once './vendor/autoload.php';

Transformers::setup()
    ->setImageDriver(ImageDriver::IMAGICK)
    ->apply();


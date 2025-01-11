<?php

declare(strict_types=1);

use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\Transformers\Utils\ImageDriver;
use Codewithkyrian\Transformers\Utils\StreamLogger;

require_once './vendor/autoload.php';

Transformers::setup()
    ->setCacheDir('/Users/Kyrian/.transformers')
    ->setImageDriver(ImageDriver::VIPS)
    ->setLogger(new StreamLogger(STDOUT));

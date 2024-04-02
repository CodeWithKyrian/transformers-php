<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

enum ImageDriver
{
    case IMAGICK;
    case GD;
    case VIPS;
}

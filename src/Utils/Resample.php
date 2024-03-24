<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

// Defined here: https://github.com/python-pillow/Pillow/blob/a405e8406b83f8bfb8916e93971edc7407b8b1ff/src/libImaging/Imaging.h#L262-L268
enum Resample: int
{
    case NEAREST = 0;
    case LANCZOS = 1;
    case BILINEAR = 2;
    case BICUBIC = 3;
    case BOX = 4;
    case HAMMING = 5;

    public function toString(): string
    {
        return match ($this) {
            self::NEAREST => 'undefined',
            self::LANCZOS => 'lanczos',
            self::BILINEAR => 'point',
            self::BICUBIC => 'cubic',
            self::BOX => 'box',
            self::HAMMING => 'hamming',
        };
    }

}
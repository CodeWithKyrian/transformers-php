<?php

use Codewithkyrian\Transformers\FFI\TransformersUtils;
use Codewithkyrian\Transformers\FFI\Libc;
use Codewithkyrian\Transformers\FFI\OnnxRuntime;
use Codewithkyrian\Transformers\FFI\Samplerate;
use Codewithkyrian\Transformers\FFI\Sndfile;
use Codewithkyrian\Transformers\Tensor\Tensor;

include __DIR__.'/../vendor/autoload.php';

OnnxRuntime::version();
dd(\Codewithkyrian\Transformers\Utils\timeUsage(true));

$x = Tensor::fromArray([
    [1, 2, 3],
    [4, 5, 6],
    [7, 8, 9],
]);

dd($x->toArray());

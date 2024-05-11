<?php

/**
 * This file is a modified version of the original file from the onnxruntime-php repository.
 *
 * Original source: https://github.com/ankane/onnxruntime-php/blob/master/src/ExecutionMode.php
 * The original file is licensed under the MIT License.
 */

declare(strict_types=1);

namespace Codewithkyrian\Transformers\OnnxRuntime;

enum ExecutionMode: int
{
    case Sequential = 0;
    case Parallel = 1;
}

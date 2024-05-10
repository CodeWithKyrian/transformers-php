<?php

/**
 * This file is a modified version of the original file from the onnxruntime-php repository.
 *
 * Original source: https://github.com/ankane/onnxruntime-php/blob/master/src/GraphOptimizationLevel.php
 * The original file is licensed under the MIT License.
 */

declare(strict_types=1);

namespace Codewithkyrian\Transformers\OnnxRuntime;

enum GraphOptimizationLevel: int
{
    case None = 0;
    case Basic = 1;
    case Extended = 2;
    case All = 99;
}

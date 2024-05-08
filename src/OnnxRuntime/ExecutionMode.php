<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\OnnxRuntime;

enum ExecutionMode: int
{
    case Sequential = 0;
    case Parallel = 1;
}

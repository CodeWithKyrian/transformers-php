<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Tensor;

use Interop\Polite\Math\Matrix\NDArray;
use Rindow\Math\Matrix\NDArrayPhp;

class MatrixOperator extends \Rindow\Math\Matrix\MatrixOperator
{
    protected function alloc(mixed $array, ?int $dtype = null, ?array $shape = null): NDArray
    {
        if ($dtype === null) {
            //$dtype = $this->resolveDtype($array);
            $dtype = $this->defaultFloatType;
        }
        return new Tensor($array, $dtype, $shape);
    }
}

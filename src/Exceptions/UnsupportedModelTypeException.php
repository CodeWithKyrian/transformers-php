<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Exceptions;

class UnsupportedModelTypeException extends \Exception implements TransformersException
{
    public static function make(string $modelType): self
    {
        return new self("The model type `$modelType` is not supported for this task. Please check for typos or refer to the documentation for the list of supported model types.");
    }
}
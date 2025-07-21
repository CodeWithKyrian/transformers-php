<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Exceptions;

class ModelExecutionException extends \Exception implements TransformersException
{
    public static function make(string $message, ?\Throwable $previous = null): self
    {
        return new self("An error occurred during model execution: $message", 0, $previous);
    }
}

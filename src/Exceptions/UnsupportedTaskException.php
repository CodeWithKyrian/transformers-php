<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Exceptions;

class UnsupportedTaskException extends \Exception implements TransformersException
{
    public static function make(string $task): self
    {
        return new self("The task `$task` is not supported. Please check for typos or refer to the documentation for the list of supported tasks.");
    }
}
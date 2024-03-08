<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Exceptions;

class MissingModelInputException extends \Exception implements TransformersException
{
    public static function make(array $missingInputs): self
    {
        $inputs = implode("\n", $missingInputs);
        return new self("The following model inputs are missing:\n$inputs");
    }

}
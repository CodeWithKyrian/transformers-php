<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Generation\StoppingCriteria;

/**
 * This class stops generation whenever the user interrupts the process.
 */
class InterruptableStoppingCriteria extends StoppingCriteria
{
    private bool $interrupted = false;

    public function interrupt(): void
    {
        $this->interrupted = true;
    }

    public function reset(): void
    {
        $this->interrupted = false;
    }

    public function __invoke(array $inputIds, array $scores): array
    {
        return array_fill(0, count($inputIds), $this->interrupted);
    }
}

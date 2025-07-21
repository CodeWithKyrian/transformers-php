<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Tensor\Tensor;
use Traversable;

class LogitsProcessorList implements \IteratorAggregate
{
    /** @var LogitsProcessor[] Array of logits processor functions */
    private array $processors = [];

    /**
     * Adds a new logits processor to the list.
     *
     * @param LogitsProcessor $item The logits processor function to add.
     */
    public function push(LogitsProcessor $item): void
    {
        $this->processors[] = $item;
    }

    /**
     * Adds multiple logits processors to the list.
     *
     * @param LogitsProcessor[] $items The logits processor functions to add.
     */
    public function extend(Traversable $items): void
    {
        foreach ($items as $item) {
            $this->processors[] = $item;
        }
    }

    /**
     * Applies all logits processors in the list to a batch of logits, modifying them in-place.
     *
     * @param array $inputIds The input IDs for the language model.
     * @param Tensor $batchedLogits A 2D array of logits, where each row corresponds to a single input sequence.
     */
    public function __invoke(array $inputIds, Tensor &$batchedLogits): Tensor
    {
        $toReturn = $batchedLogits;

        foreach ($this->processors as $processor) {
            $toReturn = $processor($inputIds, $toReturn); // Some apply processors in-place
        }

        return $toReturn;
    }

    /**
     * Allows iteration over the processors.
     *
     * @return Traversable An iterator over the processors.
     */
    public function getIterator(): Traversable
    {
        yield from $this->processors;
    }
}

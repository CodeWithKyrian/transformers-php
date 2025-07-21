<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Generation\StoppingCriteria;

use Traversable;

class StoppingCriteriaList implements \IteratorAggregate
{
    /**
     * @var StoppingCriteria[] Array of StoppingCriteria functions
     */
    private array $criteria = [];

    public function push(StoppingCriteria $criteria): void
    {
        $this->criteria[] = $criteria;
    }

    public function extend(Traversable $items): void
    {
        foreach ($items as $item) {
            $this->criteria[] = $item;
        }
    }

    public function __invoke(array $inputIds, array $scores): array
    {
        $isDone = array_fill(0, count($inputIds), false);

        foreach ($this->criteria as $criterion) {
            $criterionDone = $criterion($inputIds, $scores);

            for ($i = 0; $i < count($isDone); ++$i) {
                $isDone[$i] = $isDone[$i] || $criterionDone[$i];
            }
        }

        return $isDone;
    }

    public function getIterator(): Traversable
    {
        yield from $this->criteria;
    }
}

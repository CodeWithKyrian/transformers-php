<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Tensor\Tensor;

class ForceTokensLogitsProcessor extends LogitsProcessor
{

    /** @var array Mapping of input lengths to forced token IDs */
    protected array $forceTokenMap;

    public function __construct(array $forcedDecoderIds)
    {
        $this->forceTokenMap = array_fill_keys(array_keys($forcedDecoderIds), 0);
    }

    /**
     * Apply the processor to the input logits.
     *
     * @param Tensor[] $inputIds The input IDs.
     * @param Tensor $logits The logits to process.
     * @return Tensor The processed logits.
     */
    public function __invoke(array $inputIds, Tensor $logits): Tensor
    {
        $map = $this->forceTokenMap[count($inputIds) ?? 0]; // Access length from inputIds

        if ($map) {
            Tensor::mo()->la()->fill(-INF, $logits);

            $logits->buffer()[$map] = 0;
        }

        return $logits;
    }
}
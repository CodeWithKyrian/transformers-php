<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Output;

use Codewithkyrian\Transformers\Tensor\Tensor;

class DetrSegmentationOutput implements ModelOutput
{
    /**
     * These values are normalized in [0, 1], relative to the size of each individual image in the batch (disregarding possible padding).
     *
     * @param Tensor $logits Classification logits (including no-object) for all queries.
     * @param Tensor $predBoxes Normalized boxes coordinates for all queries, represented as (center_x, center_y, width, height).
     * @param Tensor $predMasks Segmentation masks for all queries.
     */
    public function __construct(public readonly Tensor $logits, public readonly Tensor $predBoxes, public readonly Tensor $predMasks)
    {
    }


    public static function fromOutput(array $array): self
    {
        return new self($array['logits'], $array['pred_boxes'], $array['pred_masks']);
    }
}
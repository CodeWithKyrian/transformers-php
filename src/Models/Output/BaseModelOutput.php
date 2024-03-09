<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Output;

use Codewithkyrian\Transformers\Utils\Tensor;

/**
 * Base class for model's outputs, with potential hidden states and attentions.
 */
class BaseModelOutput implements ModelOutput
{
    /**
     * @param Tensor $lastHiddenState Sequence of hidden-states at the output of the last layer of the model.
     * @param Tensor|null $hiddenStates Hidden-states of the model at the output of each layer plus the optional initial embedding outputs.
     * @param Tensor|null $attentions Attentions weights after the attention softmax, used to compute the weighted average in the self-attention heads.
     */
    public function __construct(
        public readonly Tensor  $lastHiddenState,
        public readonly ?Tensor $hiddenStates = null,
        public readonly ?Tensor $attentions = null
    )
    {
    }

    public static function fromOutput(array $array): self
    {
        return new self(
            $array['last_hidden_state'],
            isset($array['hidden_states']) ? Tensor::fromArray($array['hidden_states']) : null,
            isset($array['attentions']) ? Tensor::fromArray($array['attentions']) : null
        );
    }
}
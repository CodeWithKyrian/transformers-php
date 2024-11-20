<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Generation\StoppingCriteria;

/**
 * This class stops generation whenever the "end-of-sequence" token is generated.
 */
class EosTokenCriteria extends StoppingCriteria
{
    private array $eosTokenIds;

    /**
     * @param int|int[] $eosTokenId The id of the *end-of-sequence* token.
     */
    public function __construct(int|array $eosTokenId)
    {
        $this->eosTokenIds = is_array($eosTokenId) ? $eosTokenId : [$eosTokenId];
    }

    public function __invoke(array $inputIds, array $scores): array
    {
        return array_map(function ($ids) {
            $lastToken = end($ids);
            return in_array($lastToken, $this->eosTokenIds, true);
        }, $inputIds);
    }
}

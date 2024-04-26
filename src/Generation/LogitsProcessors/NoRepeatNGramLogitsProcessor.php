<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Utils\Tensor;
use Rindow\Math\Matrix\NDArrayPhp;

/**
 * A logits processor that disallows ngrams of a certain size to be repeated.
 */
class NoRepeatNGramLogitsProcessor extends LogitsProcessor
{

    public function __construct(protected int $noRepeatNgramSize)
    {
    }

    /**
     * Generate n-grams from a sequence of token ids.
     * @param array $prevInputIds
     * @return array
     */
    private function getNgrams(array $prevInputIds): array
    {
        $curLen = count($prevInputIds);

        $ngrams = [];
        for ($j = 0; $j <= $curLen - $this->noRepeatNgramSize; ++$j) {
            $ngram = array_slice($prevInputIds, $j, $this->noRepeatNgramSize);
            $ngrams[] = $ngram;
        }

        $generatedNgram = [];
        foreach ($ngrams as $ngram) {
            $prevNgram = array_slice($ngram, 0, -1);
            $prevNgramKey = json_encode($prevNgram);
            if (!array_key_exists($prevNgramKey, $generatedNgram)) {
                $generatedNgram[$prevNgramKey] = [];
            }
            $generatedNgram[$prevNgramKey][] = end($ngram);
        }
        return $generatedNgram;
    }

    /** Generate n-grams from a sequence of token ids.
     * @param array $bannedNgrams
     * @param array $prevInputIds
     * @return array
     */
    private function getGeneratedNgrams(array $bannedNgrams, array $prevInputIds): array
    {
        $ngramIdx = array_slice($prevInputIds, -($this->noRepeatNgramSize - 1));
        return $bannedNgrams[json_encode($ngramIdx)] ?? [];
    }

    /**
     * Calculate banned n-gram tokens
     * @param array $prevInputIds List of previous input ids
     * @return array List of banned tokens
     */
    private function calcBannedNgramTokens(array $prevInputIds): array
    {
        $bannedTokens = [];
        if (count($prevInputIds) + 1 >= $this->noRepeatNgramSize) {
            $generatedNgrams = $this->getNgrams($prevInputIds);
            $bannedTokens = $this->getGeneratedNgrams($generatedNgrams, $prevInputIds);
        }
        return $bannedTokens;
    }

    /**
     * Apply the no-repeat-ngram processor to the logits.
     * @param array $inputIds The input IDs.
     * @param Tensor|NDArrayPhp $logits The logits to process.
     * @return Tensor|NDArrayPhp The processed logits.
     */
    public function __invoke(array $inputIds, Tensor $logits): Tensor
    {
        $bannedTokens = $this->calcBannedNgramTokens($inputIds);

        foreach ($bannedTokens as $token) {
            $logits->buffer()[$token] = -INF;
        }

        return $logits;
    }
}
<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Generation\LogitsProcessors;

use Codewithkyrian\Transformers\Configs\GenerationConfig;
use Codewithkyrian\Transformers\Tensor\Tensor;
use function Codewithkyrian\Transformers\Utils\timeUsage;

class WhisperTimeStampLogitsProcessor extends LogitsProcessor
{
    /**
     * @var int|mixed The ID of the end-of-sequence token.
     */
    protected int $eosTokenId;

    /**
     * @var int The ID of the token used to indicate that a token should not have a timestamp.
     */
    protected int $noTimestampsTokenId;

    /**
     * @var int The ID at which timestamps begin.
     */
    protected int $timestampBegin;

    /**
     * @var int The index at which the first token can have a timestamp.
     */
    protected int $beginIndex;

    /**
     * @var ?int The maximum index at which an initial timestamp can appear.
     */
    protected ?int $maxInitialTimestampIndex;

    /**
     * Constructs a new WhisperTimeStampLogitsProcessor.
     */
    public function __construct(GenerationConfig $generateConfig)
    {
        $this->eosTokenId = $generateConfig['eos_token_id'];
        $this->noTimestampsTokenId = $generateConfig['no_timestamps_token_id'];
        $this->timestampBegin = $this->noTimestampsTokenId + 1;

        $this->beginIndex = count($generateConfig['forced_decoder_ids'] ?? []) + 2;

        $forcedDecoderIds = $generateConfig['forced_decoder_ids'] ?? [];
        if (count($forcedDecoderIds) > 0 && end($forcedDecoderIds)[1] === $this->noTimestampsTokenId) {
            $this->beginIndex -= 1;
        }

        $this->maxInitialTimestampIndex = $generateConfig['max_initial_timestamp_index'] ?? null;
    }

    /**
     * Modify the logits to handle timestamp tokens.
     * @param array $inputIds The input sequence of tokens.
     * @param Tensor $logits The logits output by the model.
     * @return Tensor The modified logits.
     */
    public function __invoke(array $inputIds, Tensor $logits): Tensor
    {
        // suppress which is handled by without_timestamps
        $logits->buffer()[$this->noTimestampsTokenId] = -INF;

        if (count($inputIds) === $this->beginIndex - 1) {
            Tensor::mo()->la()->fill(-INF, $logits);
            $logits->buffer()[$this->timestampBegin] = 0;
            return $logits;
        }

        // timestamps have to appear in pairs, except directly before eos_token; mask logits accordingly
        $seq = array_slice($inputIds, $this->beginIndex);
        $lastWasTimestamp = count($seq) >= 1 && $seq[count($seq) - 1] >= $this->timestampBegin;
        $penultimateWasTimestamp = count($seq) < 2 || $seq[count($seq) - 2] >= $this->timestampBegin;

        if ($lastWasTimestamp) {
            if ($penultimateWasTimestamp) { // has to be non-timestamp
                for ($i = $this->timestampBegin; $i < $logits->size(); $i++) {
                    $logits->buffer()[$i] = -INF;
                }
            } else { // cannot be normal text tokens
                for ($i = 0; $i < $this->eosTokenId; $i++) {
                    $logits->buffer()[$i] = -INF;
                }
            }
        }

        // apply the `max_initial_timestamp` option
        if (count($inputIds) === $this->beginIndex && $this->maxInitialTimestampIndex !== null) {
            $lastAllowed = $this->timestampBegin + $this->maxInitialTimestampIndex;
            for ($i = $lastAllowed + 1; $i < $logits->size(); $i++) {
                $logits->buffer()[$i] = -INF;
            }
        }

        // if sum of probability over timestamps is above any other token, sample timestamp
        $logProbs = $logits->softmax()->log();
        $timestampProbs = $logProbs->sliceWithBounds([0, $this->timestampBegin], [1, $logProbs->size() - $this->timestampBegin]);
        $timestampLogProb = log($timestampProbs->exp()->sum());
        $maxTextTokenLogProb = $logProbs->sliceWithBounds([0, 0], [1, $this->timestampBegin])->max();

        if ($timestampLogProb > $maxTextTokenLogProb) {
            for ($i = 0; $i < $this->timestampBegin; $i++) {
                $logits->buffer()[$i] = -INF;
            }
        }

        return $logits;
    }
}

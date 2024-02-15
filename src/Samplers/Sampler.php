<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Samplers;

use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\Tensor;

/**
 * Sampler is a base class for all sampling methods used for text generation.
 */
abstract class Sampler
{
    public function __construct(protected GenerationConfig $generationConfig)
    {
    }

    /**
     * Executes the sampler, using the specified logits.
     * @param Tensor $logits
     * @param int $index
     *
     * @return array
     */
    public function __invoke(Tensor $logits, int $index = -1): array
    {
        // Sample from logits, of dims [batch, sequence_length, vocab_size].
        // If index is specified, sample from [batch, index, vocab_size].
        return $this->sample($logits, $index);
    }

    /**
     * Abstract method for sampling the logits.
     * @param Tensor $logits
     * @param int $index
     */
    abstract public function sample(Tensor $logits, int $index);

    /**
     * Returns the specified logits as an array, with temperature applied.
     * @param Tensor $logits
     * @param int $index
     * @return array
     */
    public function getLogits(Tensor $logits, int $index): array
    {
        $vocabSize = $logits->shape()[count($logits->shape()) - 1];
        $logs = $logits->buffer()->toArray();

        if ($index === -1) {
            $logs = array_slice($logs, -$vocabSize);
        } else {
            $startIndex = $index * $vocabSize;
            $logs = array_slice($logs, $startIndex, $startIndex + $vocabSize);
        }

        // add temperature
        if ($this->generationConfig->temperature > 0) {
            $logs = array_map(fn($x) => $x / $this->generationConfig->temperature, $logs);
        }

        return $logs;
    }

    /**
     * Selects an item randomly based on the specified probabilities.
     * @param array $probabilities An array of probabilities to use for selection.
     * @return int The index of the selected item.
     */
    public function randomSelect(array $probabilities): int
    {
        // Return index of chosen item
        $sumProbabilities = array_reduce($probabilities, fn($acc, $curr) => $acc + $curr, 0);

        $r = mt_rand() * $sumProbabilities;

        foreach ($probabilities as $i => $probability) {
            $r -= $probability;
            if ($r <= 0) {
                return $i;
            }
        }
        return 0; // return first (most probable) as a fallback
    }

    /**
     * Returns a Sampler object based on the specified options.
     * @param GenerationConfig $generationConfig
     * @return Sampler A Sampler object.
     */
    public static function getSampler(GenerationConfig $generationConfig): Sampler
    {
        // - *greedy decoding*: `num_beams=1` and `do_sample=False`
        // - *contrastive search*: `penalty_alpha>0` and `top_k>1`
        // - *multinomial sampling*: `num_beams=1` and `do_sample=True`
        // - *beam-search decoding*: `num_beams>1` and `do_sample=False`
        // - *beam-search multinomial sampling*: `num_beams>1` and `do_sample=True`
        // - *diverse beam-search decoding*: `num_beams>1` and `num_beam_groups>1`
        // - *constrained beam-search decoding*: `constraints!=None` or `force_words_ids!=None`

        // NOTE: beam search is implemented directly into the generation function
        if ($generationConfig->do_sample) {
            return new MultinomialSampler($generationConfig);
        } elseif ($generationConfig->num_beams > 1) {
            return new BeamSearchSampler($generationConfig);
        } else {
            if ($generationConfig->num_return_sequences > 1) {
                throw new \Error("num_return_sequences has to be 1 when doing greedy search, but is {$generationConfig->num_return_sequences}.");
            }
            return new GreedySampler($generationConfig);
        }
    }
}
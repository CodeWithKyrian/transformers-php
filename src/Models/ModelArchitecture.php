<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models;

use Codewithkyrian\Transformers\Models\Pretrained\PretrainedModel;
use Codewithkyrian\Transformers\Tensor\Tensor;

use function Codewithkyrian\Transformers\Utils\array_pick;
use function Codewithkyrian\Transformers\Utils\array_pop_key;

enum ModelArchitecture: string
{
    case EncoderDecoder = 'EncoderDecoder';
    case EncoderOnly = 'EncoderOnly';
    case DecoderOnly = 'DecoderOnly';
    case Seq2SeqLM = 'Seq2SeqLM';
    case Vision2Seq = 'Vision2Seq';
    case MaskGeneration = 'MaskGeneration';


    public function canGenerate(): bool
    {
        return match ($this) {
            self::EncoderOnly, self::EncoderDecoder => false,
            default => true,
        };
    }

    public function prepareInputsForGeneration(PretrainedModel $model, $inputIds, array $modelInputs): array
    {
        return match ($this) {
            self::DecoderOnly => $this->decoderPrepareInputsForGeneration($model, $inputIds, $modelInputs),
            self::Seq2SeqLM, self::Vision2Seq => $this->encoderDecoderPrepareInputsForGeneration($model, $inputIds, $modelInputs),
        };
    }

    public function forward(PretrainedModel $model, array $modelInputs): array
    {
        return match ($this) {
            self::EncoderOnly => $this->encoderForward($model, $modelInputs),
            self::DecoderOnly => $this->decoderForward($model, $modelInputs),
            self::Seq2SeqLM, self::Vision2Seq, self::EncoderDecoder => $this->seq2seqForward($model, $modelInputs),
            default => throw new \Error('This model type does not have a forward method'),
        };
    }

    function encoderDecoderPrepareInputsForGeneration(PretrainedModel $model, $inputIds, array $modelInputs): array
    {
        if (isset($modelInputs['past_key_values'])) {
            $inputIds = array_map(fn($x) => [array_slice($x, -1)[0]], $inputIds);
        }

        return array_merge(
            $modelInputs,
            ['decoder_input_ids' => Tensor::fromArray($inputIds)]
        );
    }

    public function encoderForward(PretrainedModel $model, array $modelInputs): array
    {
        $inputNames = array_column($model->sessions['encoder']->inputs(), 'name');

        $encoderFeeds = array_pick($modelInputs, $inputNames);

        if (in_array('inputs_embeds', $inputNames) && !isset($encoderFeeds['inputs_embeds'])) {
            if (!isset($modelInputs['input_ids'])) {
                throw new \Exception('Both `input_ids` and `inputs_embeds` are missing in the model inputs.');
            }

            $encoderFeeds['inputs_embeds'] = $model->encodeText(['input_ids' => $modelInputs['input_ids']]);
        }

        if (in_array('token_type_ids', $inputNames)) {
            // Assign default `token_type_ids` (all zeroes) to the `encoderFeeds` if the model expects it,
            // but they weren't created by the tokenizer.
            $encoderFeeds['token_type_ids'] ??= Tensor::zerosLike($encoderFeeds['input_ids']);
        }

        return $model->runSession($model->sessions['encoder'], $encoderFeeds);
    }

    function decoderPrepareInputsForGeneration(PretrainedModel $model, $inputIds, array $modelInputs): array
    {
        if (isset($modelInputs['past_key_values'])) {
            $pastKeyValues = $modelInputs['past_key_values'];
            $pkvShape = array_values($pastKeyValues)[0]->shape();
            $pastLength = $pkvShape[count($pkvShape) - 2];
            $inputIds = $modelInputs['input_ids'];
            $attentionMask = $modelInputs['attention_mask'] ?? null;

            // Case 1: Attention mask is longer than input IDs
            if ($attentionMask && $attentionMask->shape()[1] > $inputIds->shape()[1]) {
                // This is not required for this implementation as the tokens passed are already handled.
            } // Case 2: Past length < Input IDs
            elseif ($pastLength < $inputIds->shape()[1]) {
                // Only keep the unprocessed tokens
                $modelInputs['input_ids'] = $inputIds->slice(null, [$pastLength, null]);
            } // Case 3: Past length >= Input IDs
            else {
                if (
                    isset($model->config['image_token_index']) &&
                    in_array($model->config['image_token_index'], $inputIds->toArray())
                ) {
                    // Support for multiple image tokens
                    $numImageTokens = $model->config['num_image_tokens'] ?? null;
                    if (!$numImageTokens) {
                        throw new \Exception('`num_image_tokens` is missing in the model configuration.');
                    }

                    $numNewTokens = $inputIds->shape()[1] - ($pastLength - $numImageTokens);
                    $modelInputs['input_ids'] = $inputIds->slice(null, [-$numNewTokens, null]);

                    // Create the attention mask from scratch
                    $modelInputs['attention_mask'] = Tensor::ones([1, $pastLength + $numNewTokens], Tensor::int64);
                }
            }
        }

        return $modelInputs;
    }

    protected function decoderForward(PretrainedModel $model, array $modelInputs): array
    {
        $session = $model->sessions['decoder'];

        $inputNames = array_column($session->inputs(), 'name');

        $pastKeyValues = array_pop_key($modelInputs, 'past_key_values');

        if (in_array('use_cache_branch', $inputNames)) {
            $modelInputs['use_cache_branch'] = new Tensor([!empty($pastKeyValues)], Tensor::bool, [1]);
        }

        if (
            in_array('position_ids', $inputNames) &&
            isset($modelInputs['attention_mask']) &&
            !isset($modelInputs['position_ids'])
        ) {
            $modelInputs['position_ids'] = $this->createPositionIds($modelInputs, $pastKeyValues);
        }

        $model->addPastKeyValues($modelInputs, $pastKeyValues);

        $decoderFeeds = array_pick($modelInputs, $inputNames);

        return $model->runSession($session, $decoderFeeds);
    }

    protected function seq2seqForward(PretrainedModel $model, array $modelInputs): array
    {
        $decoderFeeds = $modelInputs;
        $encoderOutputs = array_pop_key($decoderFeeds, 'encoder_outputs');
        $decoderInputIds = array_pop_key($decoderFeeds, 'decoder_input_ids');

        if (!$encoderOutputs) {
            $inputNames = array_column($model->sessions['encoder']->inputs(), 'name');
            $encoderInputs = array_pick($modelInputs, $inputNames);
            $encoderOutputs = $this->encoderForward($model, $encoderInputs)['last_hidden_state'];
        }

        $decoderFeeds['input_ids'] = $decoderInputIds;
        $decoderFeeds['encoder_hidden_states'] = $encoderOutputs;

        $inputNames = array_column($model->sessions['decoder']->inputs(), 'name');

        if (in_array('encoder_attention_mask', $inputNames)) {
            $decoderFeeds['encoder_attention_mask'] = $modelInputs['attention_mask'];
        }

        return $this->decoderForward($model, $decoderFeeds);
    }

    /**
     * Create position IDs based on the attention mask.
     * 
     * @param array{input_ids: Tensor, inputs_embeds: Tensor, attention_mask: Tensor} $modelInputs
     * @param array|null $pastKeyValues
     * @return Tensor
     */
    protected function createPositionIds(array $modelInputs, ?array $pastKeyValues = null): Tensor
    {
        $inputIds = $modelInputs['input_ids'] ?? null;
        $inputsEmbeds = $modelInputs['inputs_embeds'] ?? null;
        $attentionMask = $modelInputs['attention_mask'];

        [$batchSize, $seqLen] = $attentionMask->shape();

        $data = array_fill(0, $attentionMask->size(), 0);

        for ($i = 0; $i < $batchSize; ++$i) {
            $start = $i * $seqLen;
            $sum = 0;

            for ($j = 0; $j < $seqLen; ++$j) {
                $index = $start + $j;
                if ($attentionMask->buffer()[$index] === 0) {
                    $data[$index] = 1;
                } else { // === 1
                    $data[$index] = $sum;
                    $sum += $attentionMask->buffer()[$index];
                }
            }
        }

        $positionIds = new Tensor($data, Tensor::int64, $attentionMask->shape());

        if ($pastKeyValues) {
            $offset = - (($inputIds ?? $inputsEmbeds)->shape()[1]);
            $positionIds = $positionIds->slice(null, [$offset, null]); //  position_ids[:, -input_ids.shape[1] :]
        }

        return $positionIds;
    }
}

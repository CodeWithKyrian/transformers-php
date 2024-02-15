<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models;

use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\Tensor;

enum ModelType: string
{
    case EncoderOnly = 'EncoderOnly';
    case EncoderDecoder = 'EncoderDecoder';
    case DecoderOnly = 'DecoderOnly';
    case Seq2Seq = 'Seq2Seq';
    case Vision2Seq = 'Vision2Seq';
    case MaskGeneration = 'MaskGeneration';


    public function canGenerate(): bool
    {
        return match ($this) {
            self::EncoderOnly, self::EncoderDecoder => false,
            default => true,
        };
    }

    public function runBeam(PreTrainedModel $model, array &$beam): array
    {
        return match ($this) {
            self::DecoderOnly => $this->decoderRunBeam($model, $beam),
            self::Seq2Seq, self::Vision2Seq => $this->seq2seqRunBeam($model, $beam),
            default => throw new \Error('This model type does not support beam search'),
        };
    }

    public function startBeams(
        PreTrainedModel  $model,
        Tensor           $inputTokenIds,
        GenerationConfig $generationConfig,
        int              $numOutputTokens,
        Tensor           $inputsAttentionMask = null
    ): array
    {
        return match ($this) {
            self::DecoderOnly => $this->decoderStartBeams($model, $inputTokenIds, $generationConfig, $numOutputTokens, $inputsAttentionMask),
            self::Seq2Seq,
            self::Vision2Seq => $this->seq2seqStartBeams($model, $inputTokenIds, $generationConfig, $numOutputTokens, $inputsAttentionMask),
            default => throw new \Error('This model type does not support beam search'),
        };
    }

    public function updateBeam(PreTrainedModel  $model, array $beam, int $newTokenId): array
    {
        return match ($this) {
            self::DecoderOnly => $this->decoderUpdatebeam($model, $beam, $newTokenId),
            self::Seq2Seq, self::Vision2Seq => $this->seq2seqUpdatebeam($model, $beam, $newTokenId),
            default => throw new \Error('This model type does not support beam search'),
        };
    }

    public function forward(PreTrainedModel $model, array $modelInputs): array
    {
        return match ($this) {
            self::EncoderOnly => $this->encoderForward($model, $modelInputs),
            self::DecoderOnly => $this->decoderForward($model, $modelInputs),
            self::Seq2Seq, self::Vision2Seq => $this->seq2seqForward($model, $modelInputs),
            default => throw new \Error('This model type does not have a forward method'),
        };
    }

    /**
     * Forward pass of an encoder model.
     *
     * @param PreTrainedModel $model The encoder model to use for the forward pass.
     * @param array{input_ids: Tensor, token_type_ids: Tensor} $modelInputs The input data to be used for the forward pass.
     *
     * @return array{logits: Tensor, hidden_states: Tensor, attentions: Tensor}
     */
    protected function encoderForward(PreTrainedModel $model, array $modelInputs): array
    {
        $encoderFeeds = [];

        foreach ($model->session->inputs as ['name' => $inputName]) {
            $encoderFeeds[$inputName] = $modelInputs[$inputName];
        }

        $hasTokenTypeIds = in_array('token_type_ids', array_column($model->session->inputs, 'name'));

        if ($hasTokenTypeIds) {
            // Assign default `token_type_ids` (all zeroes) to the `encoderFeeds` if the model expects it,
            // but they weren't created by the tokenizer.
            $encoderFeeds['token_type_ids'] ??= Tensor::zerosLike($encoderFeeds['input_ids']);
        }

        return $model->runSession($model->session, $encoderFeeds);
    }

    protected function decoderRunBeam(PreTrainedModel $model, array &$beam): array
    {
        return [];
    }

    protected function decoderStartBeams(
        PreTrainedModel  $model,
        Tensor           $inputTokenIds,
        GenerationConfig $generationConfig,
        int              $numOutputTokens,
        Tensor           $inputsAttentionMask = null): array
    {
        return [];
    }

    protected function decoderUpdatebeam(PreTrainedModel  $model,array $beam, int $newTokenId): array
    {
        return [];
    }

    protected function decoderForward(PreTrainedModel $model, array $modelInputs): array
    {
        return [];
    }

    /**
     * Run beam search on the seq2seq model for a single beam.
     * @param array $beam The beam search object for which to run the model.
     * @return array The output of the seq2seq model for the given beam.
     */
    protected function seq2seqRunBeam(PreTrainedModel $model, array &$beam): array
    {
        $inputName = $model->mainInputName;

        $decoderInputIds = $beam['output_token_ids'];

        if ($beam['prev_model_outputs']) {
            // After the first step, `prev_model_outputs` won't be null.
            // So, we cut decoder_input_ids if past is used
            $decoderInputIds = array_slice($decoderInputIds, -1);
        }

        // 1. Prepare
        $model_inputs = [
            $inputName => $beam['inputs'],
            'decoder_input_ids' => Tensor::fromArray($decoderInputIds),
            'encoder_outputs' => $beam['encoder_outputs'],
            'past_key_values' => $beam['prev_model_outputs']['past_key_values'] ?? null,
        ];

        if ($beam['attention_mask']) {
            $model_inputs['attention_mask'] = $beam['attention_mask'];
        }

        // 2. Run
        $output = $model->forward($model_inputs);

        // 3. Update
        $beam['prev_model_outputs'] = $output;
        $beam['encoder_outputs'] = $output['encoder_outputs'];

        return $output;
    }


    /**
     * Start the beam search process for the seq2seq model.
     * @param Tensor $inputTokenIds Array of input token ids for each input sequence.
     * @param GenerationConfig $generationConfig The generation config.
     * @param int $numOutputTokens The maximum number of output tokens for the model.
     * @return array Array of beam search objects.
     * @private
     */
    protected function seq2seqStartBeams(
        PreTrainedModel  $model,
        Tensor           $inputTokenIds,
        GenerationConfig $generationConfig,
        int              $numOutputTokens,
        Tensor           $inputsAttentionMask = null): array
    {
        $beams = [];
        $beamId = 0;

//        $requires_attention_mask = $this['requires_attention_mask'] ?? true; // TODO: FIX
        $requiresAttentionMask = true;

        // decoder_input_ids == output_token_ids
        $decoder_input_ids =
            $generationConfig->decoder_input_ids
            ?? $generationConfig->decoder_start_token_id
            ?? $generationConfig->bos_token_id
            ?? $generationConfig->eos_token_id;


        // Support input as tensor or list
        // TODO support batched decoder_input_ids
        if ($decoder_input_ids instanceof Tensor) {
            $decoder_input_ids = $decoder_input_ids->tolist()->flat();
        } elseif (!is_array($decoder_input_ids)) {
            $decoder_input_ids = [$decoder_input_ids];
        }


        foreach ($inputTokenIds as $tokens) {
            $tokens = Tensor::fromNdArray($tokens);

            // TODO: Improve
            // Currently, just add back batch dimension.
            // In future, allow for true parallel execution
            $tokens->reshape([1, ...$tokens->shape()]);

            // Create beam
            $start = [
                'inputs' => $tokens,
                'encoder_outputs' => null,
                'prev_model_outputs' => null,

                'output_token_ids' => $decoder_input_ids,
                'done' => false,
                'score' => 0,
                'id' => $beamId++ // assign unique id to beams
            ];

            if ($requiresAttentionMask) {
                $start['attention_mask'] = $model->prepareAttentionMask($tokens);
            }

            $beams[] = $start;
        }

        return $beams;
    }

    /**
     * Update a beam with a new token ID.
     * @param array $beam The beam to update.
     * @param int $newTokenId The new token ID to add to the beam's output.
     * @private
     */
    protected function seq2seqUpdatebeam(PreTrainedModel  $model,array $beam, int $newTokenId): array
    {
        return [];
    }

    /**
     * Perform forward pass on the seq2seq model (both encoder and decoder).
     * @param array $modelInputs The input object for the model containing encoder and decoder inputs.
     * @return array The output of the seq2seq model.
     * @private
     */
    protected function seq2seqForward(PreTrainedModel $model, array $modelInputs): array
    {
        $encoder_outputs = $modelInputs['encoder_outputs'] ?? null;
        $past_key_values = $modelInputs['past_key_values'] ?? null;

        if (!$encoder_outputs) {
            // Encoder outputs are not given, so we must compute them.
            $encoder_outputs = $this->encoderForward($modelInputs)['last_hidden_state'];
        }

        $decoderFeeds = [
            'input_ids' => $modelInputs['decoder_input_ids'],
            'encoder_hidden_states' => $encoder_outputs,
        ];

        $use_cache_branch = !!$past_key_values;

        if (in_array('use_cache_branch', $this->decoderMergedSessions->inputs)) {
            $decoderFeeds['use_cache_branch'] = boolTensor($use_cache_branch);
        }

        if (in_array('encoder_attention_mask', $this->decoderMergedSessions->inputs)) {
            $decoderFeeds['encoder_attention_mask'] = $modelInputs['attention_mask'];
        }

        preparePositionIds($this->decoderMergedSessions, $decoderFeeds, $use_cache_branch);
        $this->addPastKeyValues($decoderFeeds, $past_key_values);

        $decoderResults = $this->runSession($this->decoderMergedSessions, $decoderFeeds);
        $logits = $decoderResults['logits'];
        $past_key_values = $this->getPastKeyValues($decoderResults, $past_key_values);

        // Get cross attention and/or decoder attentions if they are present
        $attns = $this->getAttentions($decoderResults);

        return [
            'logits' => $logits,
            'past_key_values' => $past_key_values,
            'encoder_outputs' => $encoder_outputs,
            ...$attns
        ];
    }

}
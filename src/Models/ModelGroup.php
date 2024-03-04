<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models;

use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\Tensor;

enum ModelGroup: string
{
    case EncoderDecoder = 'EncoderDecoder';
    case EncoderOnly = 'EncoderOnly';
    case DecoderOnly = 'DecoderOnly';
    case Seq2SeqLM = 'Seq2SeqLM';
    case Vision2Seq = 'Vision2Seq';
    case MaskGeneration = 'MaskGeneration';

    // <editor-fold desc="Model classes">

    const ENCODER_ONLY_MODELS = [
        "bert" => BertModel::class,
        "distilbert" => DistilBertModel::class,
        "mobilebert" => MobileBertModel::class,
        "deberta-v2" => DebertaV2Model::class,
        "roformer" => RoFormerModel::class,
    ];

    const ENCODER_DECODER_MODELS = [
        "t5" => T5Model::class,
    ];

    const DECODER_ONLY_MODELS = [
        "gpt2" => GPT2Model::class,
    ];

    const SEQ_2_SEQ_LM_MODELS = [
        "t5" => T5ForConditionalGeneration::class,
    ];

    //</editor-fold>

    // <editor-fold desc="Initialization">
    /**
     * @param string $modelType
     * @return class-string<PreTrainedModel>
     */
    public function getModelClass(string $modelType): string
    {
        return match ($this) {
            self::EncoderOnly => self::ENCODER_ONLY_MODELS[$modelType],
            self::EncoderDecoder => self::ENCODER_DECODER_MODELS[$modelType],
            self::DecoderOnly => self::DECODER_ONLY_MODELS[$modelType],
            self::Seq2SeqLM => self::SEQ_2_SEQ_LM_MODELS[$modelType],
            default => throw new \Error("Model group {$this->value} does not contain a model for type {$modelType}."),
        };
    }

    public function constructModel(
        string     $modelNameOrPath,
        bool       $quantized = true,
        AutoConfig $config = null,
        ?string    $cacheDir = null,
        ?string    $token = null,
        string     $revision = 'main',
        ?string    $modelFilename = null)
    {
        $modelClass = $this->getModelClass($config->modelType);

        return $modelClass::fromPretrained(
            modelNameOrPath: $modelNameOrPath,
            quantized: $quantized,
            config: $config,
            cacheDir: $cacheDir,
            token: $token,
            revision: $revision,
            modelFilename: $modelFilename,
            modelGroup: $this
        );
    }

    public static function inferFromModelType(string $modelType): self
    {
        return match (true) {
            isset(self::ENCODER_ONLY_MODELS[$modelType]) => self::EncoderOnly,
            isset(self::ENCODER_DECODER_MODELS[$modelType]) => self::EncoderDecoder,
            isset(self::DECODER_ONLY_MODELS[$modelType]) => self::DecoderOnly,
            isset(self::SEQ_2_SEQ_LM_MODELS[$modelType]) => self::Seq2SeqLM,
            default => throw new \Error("Model group for model type {$modelType} is not implemented yet."),
        };
    }

    //</editor-fold>

    // <editor-fold desc="Abstract methods">

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
            self::Seq2SeqLM => $this->seq2seqRunBeam($model, $beam),
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
            self::Seq2SeqLM => $this->seq2seqStartBeams($model, $inputTokenIds, $generationConfig, $numOutputTokens, $inputsAttentionMask),
            default => throw new \Error('This model type does not support beam search'),
        };
    }

    public function updateBeam(PreTrainedModel $model, array $beam, int $newTokenId): array
    {
        return match ($this) {
            self::DecoderOnly => $this->decoderUpdatebeam($model, $beam, $newTokenId),
            self::Seq2SeqLM, self::Vision2Seq => $this->seq2seqUpdatebeam($model, $beam, $newTokenId),
            default => throw new \Error('This model type does not support beam search'),
        };
    }

    public function forward(PreTrainedModel $model, array $modelInputs): array
    {
        return match ($this) {
            self::EncoderOnly => $this->encoderForward($model, $modelInputs),
            self::DecoderOnly => $this->decoderForward($model, $modelInputs),
            self::Seq2SeqLM, self::Vision2Seq => $this->seq2seqForward($model, $modelInputs),
            default => throw new \Error('This model type does not have a forward method'),
        };
    }

    //</editor-fold>

    //<editor-fold desc="Encoder methods">

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

    //</editor-fold>

    //<editor-fold desc="Decoder methods">

    protected function decoderRunBeam(PreTrainedModel $model, array &$beam): array
    {
        return [];
    }

    protected function decoderStartBeams(
        PreTrainedModel  $model,
        Tensor           $inputTokenIds,
        GenerationConfig $generationConfig,
        int              $numOutputTokens,
        Tensor           $inputsAttentionMask = null
    ): array
    {
        return [];
    }

    protected function decoderUpdatebeam(PreTrainedModel $model, array $beam, int $newTokenId): array
    {
        return [];
    }

    protected function decoderForward(PreTrainedModel $model, array $modelInputs): array
    {
        return [];
    }

    //</editor-fold>

    //<editor-fold desc="Seq2Seq methods">

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

    protected function seq2seqStartBeams(
        PreTrainedModel  $model,
        Tensor           $inputTokenIds,
        GenerationConfig $generationConfig,
        int              $numOutputTokens,
        Tensor           $inputsAttentionMask = null
    ): array
    {
        return [];
    }

    protected function seq2seqUpdatebeam(PreTrainedModel $model, array $beam, int $newTokenId): array
    {
        return [];
    }

    protected function seq2seqForward(PreTrainedModel $model, array $modelInputs): array
    {
        return [];
    }

    //</editor-fold>

}

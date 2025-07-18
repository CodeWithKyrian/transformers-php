<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Configs\AutoConfig;
use Codewithkyrian\Transformers\Exceptions\UnsupportedModelTypeException;
use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Models\Pretrained\PretrainedModel;
use Codewithkyrian\Transformers\Transformers;

/**
 * Base class of all AutoModels. Contains the `from_pretrained` function
 * which is used to instantiate pretrained models.
 */
abstract class AutoModelBase
{
    /**
     * Mapping from model type to model class.
     * @var array<string, class-string<PretrainedModel>> The model class mappings.
     */
    const MODELS = [];

    /**
     * Whether to attempt to instantiate the base class (`PretrainedModel`) if
     * the model type is not found in the mapping.
     */
    const BASE_IF_FAIL = false;

    /**
     * Instantiate a model from a pretrained model configuration.
     *
     * @param string $modelNameOrPath The model name or path.
     * @param bool $quantized Whether to use a quantized model.
     * @param array|null $config The configuration for the model.
     * @param string|null $cacheDir The cache directory to save the model in.
     * @param string $revision The revision of the model.
     * @param string|null $modelFilename The filename of the model.
     * @return PretrainedModel The instantiated pretrained model.
     */
    public static function fromPretrained(
        string           $modelNameOrPath,
        bool             $quantized = true,
        ?array           $config = null,
        ?string          $cacheDir = null,
        string           $revision = 'main',
        ?string          $modelFilename = null,
        ?callable        $onProgress = null
    ): PretrainedModel {
        $config = AutoConfig::fromPretrained($modelNameOrPath, $config, $cacheDir, $revision, $onProgress);

        foreach (static::MODELS as $modelType => $modelClass) {
            if ($modelType != $config->modelType)  continue;

            $modelArchitecture = self::getModelArchitecture($modelClass);

            return $modelClass::fromPretrained(
                modelNameOrPath: $modelNameOrPath,
                quantized: $quantized,
                config: $config,
                cacheDir: $cacheDir,
                revision: $revision,
                modelFilename: $modelFilename,
                modelArchitecture: $modelArchitecture,
                onProgress: $onProgress
            );
        }

        if (static::BASE_IF_FAIL) {
            $logger = Transformers::getLogger();
            $logger->warning("Unknown model class for model type {$config->modelType}. Using base class PreTrainedModel.");

            return PretrainedModel::fromPretrained(
                modelNameOrPath: $modelNameOrPath,
                quantized: $quantized,
                config: $config,
                cacheDir: $cacheDir,
                revision: $revision,
                modelFilename: $modelFilename,
                onProgress: $onProgress
            );
        } else {
            throw UnsupportedModelTypeException::make($config->modelType);
        }
    }

    protected static function getModelArchitecture($modelClass): ModelArchitecture
    {
        return match (true) {
            in_array($modelClass, AutoModel::ENCODER_ONLY_MODELS) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModel::ENCODER_DECODER_MODELS) => ModelArchitecture::EncoderDecoder,
            in_array($modelClass, AutoModel::DECODER_ONLY_MODELS) => ModelArchitecture::DecoderOnly,
            in_array($modelClass, AutoModelForSequenceClassification::MODELS) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModelForSeq2SeqLM::MODELS) => ModelArchitecture::Seq2SeqLM,
            in_array($modelClass, AutoModelForCausalLM::MODELS) => ModelArchitecture::DecoderOnly,
            in_array($modelClass, AutoModelForTokenClassification::MODELS) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModelForQuestionAnswering::MODELS) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModelForMaskedLM::MODELS) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModelForVision2Seq::MODELS) => ModelArchitecture::Vision2Seq,
            in_array($modelClass, AutoModelForImageClassification::MODELS) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModelForAudioClassification::MODELS) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModelForSpeechSeq2Seq::MODELS) => ModelArchitecture::Seq2SeqLM,
            in_array($modelClass, AutoModelForCTC::MODELS) => ModelArchitecture::EncoderOnly,

            default => ModelArchitecture::EncoderOnly,
        };
    }
}

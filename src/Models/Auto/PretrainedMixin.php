<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Exceptions\UnsupportedModelTypeException;
use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Models\Pretrained\PreTrainedModel;
use Codewithkyrian\Transformers\Utils\AutoConfig;

/**
 * Base class of all AutoModels. Contains the `from_pretrained` function
 * which is used to instantiate pretrained models.
 */
abstract class PretrainedMixin
{
    /**
     * Mapping from model type to model class.
     * @var array<string, array<string, string>> The model class mappings.
     */
    const MODEL_CLASS_MAPPINGS = [];

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
     * @param string|null $token The API token to use.
     * @param string $revision The revision of the model.
     * @param string|null $modelFilename The filename of the model.
     * @return PreTrainedModel The instantiated pretrained model.
     */
    public static function fromPretrained(
        string  $modelNameOrPath,
        bool    $quantized = true,
        ?array  $config = null,
        ?string $cacheDir = null,
        ?string $token = null,
        string  $revision = 'main',
        ?string $modelFilename = null,
    ): PreTrainedModel
    {
        $config = AutoConfig::fromPretrained($modelNameOrPath, $config, $cacheDir, $revision);

        foreach (static::MODEL_CLASS_MAPPINGS as $modelClassMapping) {
            $modelClass = $modelClassMapping[$config->modelType] ?? null;


            if ($modelClass === null) continue;

            $modelArchitecture = self::getModelArchitecture($modelClass);

            return $modelClass::fromPretrained(
                modelNameOrPath: $modelNameOrPath,
                quantized: $quantized,
                config: $config,
                cacheDir: $cacheDir,
                token: $token,
                revision: $revision,
                modelFilename: $modelFilename,
                modelArchitecture: $modelArchitecture
            );
        }

        if (static::BASE_IF_FAIL) {
            echo "Unknown model class for model type {$config->modelType}. Using base class PreTrainedModel.\n";

            return PreTrainedModel::fromPretrained(
                modelNameOrPath: $modelNameOrPath,
                quantized: $quantized,
                config: $config,
                cacheDir: $cacheDir,
                token: $token,
                revision: $revision,
                modelFilename: $modelFilename
            );
        } else {
            throw UnsupportedModelTypeException::make($config->modelType);
        }
    }

    protected static function getModelArchitecture($modelClass): ModelArchitecture
    {
        return match (true) {
            in_array($modelClass, AutoModel::ENCODER_ONLY_MODEL_MAPPING) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModel::ENCODER_DECODER_MODEL_MAPPING) => ModelArchitecture::EncoderDecoder,
            in_array($modelClass, AutoModel::DECODER_ONLY_MODEL_MAPPING) => ModelArchitecture::DecoderOnly,
            in_array($modelClass, AutoModelForSequenceClassification::MODEL_CLASS_MAPPING) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModelForSeq2SeqLM::MODEL_CLASS_MAPPING) => ModelArchitecture::Seq2SeqLM,
            in_array($modelClass, AutoModelForCausalLM::MODEL_CLASS_MAPPING) => ModelArchitecture::DecoderOnly,
            in_array($modelClass, AutoModelForTokenClassification::MODEL_CLASS_MAPPING) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModelForQuestionAnswering::MODEL_CLASS_MAPPING) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModelForMaskedLM::MODEL_CLASS_MAPPING) => ModelArchitecture::EncoderOnly,

            default => ModelArchitecture::EncoderOnly,
        };
    }
}
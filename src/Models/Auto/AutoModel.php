<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Models\Pretrained\BartModel;
use Codewithkyrian\Transformers\Models\Pretrained\BertModel;
use Codewithkyrian\Transformers\Models\Pretrained\DebertaV2Model;
use Codewithkyrian\Transformers\Models\Pretrained\DistilBertModel;
use Codewithkyrian\Transformers\Models\Pretrained\GPT2Model;
use Codewithkyrian\Transformers\Models\Pretrained\M2M100Model;
use Codewithkyrian\Transformers\Models\Pretrained\MobileBertModel;
use Codewithkyrian\Transformers\Models\Pretrained\RoFormerModel;
use Codewithkyrian\Transformers\Models\Pretrained\T5Model;

class AutoModel extends PretrainedMixin
{
    const ENCODER_ONLY_MODEL_MAPPING = [
        "bert" => BertModel::class,
        "distilbert" => DistilBertModel::class,
        "mobilebert" => MobileBertModel::class,
        "deberta-v2" => DebertaV2Model::class,
        "roformer" => RoFormerModel::class,
    ];

    const ENCODER_DECODER_MODEL_MAPPING = [
        "t5" => T5Model::class,
        "bart" => BartModel::class,
        "m2m_100" => M2M100Model::class,
    ];

    const DECODER_ONLY_MODEL_MAPPING = [
        "gpt2" => GPT2Model::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        AutoModelForCausalLM::MODEL_CLASS_MAPPING,
        AutoModelForSeq2SeqLM::MODEL_CLASS_MAPPING,
        AutoModelForSequenceClassification::MODEL_CLASS_MAPPING,

        self::DECODER_ONLY_MODEL_MAPPING,
        self::ENCODER_DECODER_MODEL_MAPPING,
        self::ENCODER_ONLY_MODEL_MAPPING,
    ];


    const BASE_IF_FAIL = true;

    protected static function getModelArchitecture($modelClass): ModelArchitecture
    {
        return match (true) {
            in_array($modelClass, AutoModel::ENCODER_ONLY_MODEL_MAPPING) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModel::ENCODER_DECODER_MODEL_MAPPING) => ModelArchitecture::EncoderDecoder,
            in_array($modelClass, AutoModel::DECODER_ONLY_MODEL_MAPPING) => ModelArchitecture::DecoderOnly,
            in_array($modelClass, AutoModelForSequenceClassification::MODEL_CLASS_MAPPING) => ModelArchitecture::EncoderOnly,
            in_array($modelClass, AutoModelForSeq2SeqLM::MODEL_CLASS_MAPPING) => ModelArchitecture::Seq2SeqLM,
            in_array($modelClass, AutoModelForCausalLM::MODEL_CLASS_MAPPING) => ModelArchitecture::DecoderOnly,
            default => ModelArchitecture::EncoderOnly,
        };
    }
}
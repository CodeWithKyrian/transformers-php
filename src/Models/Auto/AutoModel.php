<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModel extends PretrainedMixin
{
    const ENCODER_ONLY_MODEL_MAPPING = [
        "albert" => \Codewithkyrian\Transformers\Models\Pretrained\AlbertModel::class,
        "bert" => \Codewithkyrian\Transformers\Models\Pretrained\BertModel::class,
        "distilbert" => \Codewithkyrian\Transformers\Models\Pretrained\DistilBertModel::class,
        "deberta" => \Codewithkyrian\Transformers\Models\Pretrained\DebertaModel::class,
        "deberta-v2" => \Codewithkyrian\Transformers\Models\Pretrained\DebertaV2Model::class,
        "mobilebert" => \Codewithkyrian\Transformers\Models\Pretrained\MobileBertModel::class,
        "roformer" => \Codewithkyrian\Transformers\Models\Pretrained\RoFormerModel::class,
        "roberta" => \Codewithkyrian\Transformers\Models\Pretrained\RobertaModel::class,
        "clip" => \Codewithkyrian\Transformers\Models\Pretrained\CLIPModel::class,
        "vit" => \Codewithkyrian\Transformers\Models\Pretrained\ViTModel::class,
        "deit" => \Codewithkyrian\Transformers\Models\Pretrained\DeiTModel::class,
    ];

    const ENCODER_DECODER_MODEL_MAPPING = [
        "t5" => \Codewithkyrian\Transformers\Models\Pretrained\T5Model::class,
        "bart" => \Codewithkyrian\Transformers\Models\Pretrained\BartModel::class,
        "m2m_100" => \Codewithkyrian\Transformers\Models\Pretrained\M2M100Model::class,
    ];

    const DECODER_ONLY_MODEL_MAPPING = [
        "gpt2" => \Codewithkyrian\Transformers\Models\Pretrained\GPT2Model::class,
        "gptj" => \Codewithkyrian\Transformers\Models\Pretrained\GPTJModel::class,
        "gpt_bigcode" => \Codewithkyrian\Transformers\Models\Pretrained\GPTBigCodeModel::class,
        "codegen" => \Codewithkyrian\Transformers\Models\Pretrained\CodeGenModel::class,
    ];

    const MODEL_CLASS_MAPPINGS = [
        self::ENCODER_ONLY_MODEL_MAPPING,
        self::ENCODER_DECODER_MODEL_MAPPING,
        self::DECODER_ONLY_MODEL_MAPPING,

        AutoModelForSequenceClassification::MODEL_CLASS_MAPPING,
        AutoModelForTokenClassification::MODEL_CLASS_MAPPING,
        AutoModelForSeq2SeqLM::MODEL_CLASS_MAPPING,
        AutoModelForCausalLM::MODEL_CLASS_MAPPING,
        AutoModelForMaskedLM::MODEL_CLASS_MAPPING,
        AutoModelForQuestionAnswering::MODEL_CLASS_MAPPING,
        AutoModelForImageClassification::MODEL_CLASS_MAPPING,
        AutoModelForVision2Seq::MODEL_CLASS_MAPPING
    ];


    const BASE_IF_FAIL = true;
}
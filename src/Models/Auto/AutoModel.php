<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Auto;

class AutoModel extends AutoModelBase
{
    const ENCODER_ONLY_MODELS = [
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
        "siglip" => \Codewithkyrian\Transformers\Models\Pretrained\SiglipModel::class,

        "audio-spectrogram-transformer" => \Codewithkyrian\Transformers\Models\Pretrained\ASTModel::class,
        "wav2vec2" => \Codewithkyrian\Transformers\Models\Pretrained\Wav2Vec2Model::class,

        'detr' => \Codewithkyrian\Transformers\Models\Pretrained\DETRModel::class,
        'yolos' => \Codewithkyrian\Transformers\Models\Pretrained\YOLOSModel::class,
        'owlvit' => \Codewithkyrian\Transformers\Models\Pretrained\OwlVitModel::class,
        'owlv2' => \Codewithkyrian\Transformers\Models\Pretrained\OwlV2Model::class,
        'swin2sr' => \Codewithkyrian\Transformers\Models\Pretrained\Swin2SRModel::class,
    ];

    const ENCODER_DECODER_MODELS = [
        "t5" => \Codewithkyrian\Transformers\Models\Pretrained\T5Model::class,
        "bart" => \Codewithkyrian\Transformers\Models\Pretrained\BartModel::class,
        "m2m_100" => \Codewithkyrian\Transformers\Models\Pretrained\M2M100Model::class,
    ];

    const DECODER_ONLY_MODELS = [
        "gpt2" => \Codewithkyrian\Transformers\Models\Pretrained\GPT2Model::class,
        "gptj" => \Codewithkyrian\Transformers\Models\Pretrained\GPTJModel::class,
        "gpt_bigcode" => \Codewithkyrian\Transformers\Models\Pretrained\GPTBigCodeModel::class,
        "codegen" => \Codewithkyrian\Transformers\Models\Pretrained\CodeGenModel::class,
        "llama" => \Codewithkyrian\Transformers\Models\Pretrained\LlamaModel::class,
        "qwen2" => \Codewithkyrian\Transformers\Models\Pretrained\Qwen2Model::class,
    ];

    const MODELS = [
        ...self::ENCODER_ONLY_MODELS,
        ...self::ENCODER_DECODER_MODELS,
        ...self::DECODER_ONLY_MODELS,

        ...AutoModelForSequenceClassification::MODELS,
        ...AutoModelForTokenClassification::MODELS,
        ...AutoModelForSeq2SeqLM::MODELS,
        ...AutoModelForCausalLM::MODELS,
        ...AutoModelForMaskedLM::MODELS,
        ...AutoModelForQuestionAnswering::MODELS,
        ...AutoModelForImageClassification::MODELS,
        ...AutoModelForVision2Seq::MODELS,
        ...AutoModelForObjectDetection::MODELS,
        ...AutoModelForZeroShotObjectDetection::MODELS,
    ];


    const BASE_IF_FAIL = true;
}

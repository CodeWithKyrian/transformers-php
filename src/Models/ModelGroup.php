<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models;

enum ModelGroup: string
{
    case EncoderDecoder = 'EncoderDecoder';
    case EncoderOnly = 'EncoderOnly';
    case DecoderOnly = 'DecoderOnly';
    case SequenceClassification = 'SequenceClassification';
    case TokenClassification = 'TokenClassification';
    case Seq2SeqLM = 'Seq2SeqLM';
    case SpeechSeq2Seq = 'SpeechSeq2Seq';
    case TextToSpectrogram = 'TextToSpectrogram';
    case CausalLM = 'CausalLM';
    case MaskedLM = 'MaskedLM';
    case ImageClassification = 'ImageClassification';
    case FeatureExtraction = 'FeatureExtraction';
    case ObjectDetection = 'ObjectDetection';
    case MaskGeneration = 'MaskGeneration';
    case DocumentQuestionAnswering = 'DocumentQuestionAnswering';

    public function models(): array
    {
        return match ($this) {
            self::EncoderOnly => $this->encoderOnlyModels(),
            self::EncoderDecoder => $this->encoderDecoderModels(),
            self::DecoderOnly => $this->decoderOnlyModels(),
            self::Seq2SeqLM => $this->sequence2SequenceLMModels(),
            default => throw new \Error("Model group {$this->value} is not implemented yet."),
        };
    }

    protected function encoderOnlyModels(): array
    {
        return [
            "bert" => BertModel::class,
            "distilbert" => DistilBertModel::class,
            "mobilebert" => MobileBertModel::class,
            "deberta-v2" => DebertaV2Model::class,
            "roformer" => RoFormerModel::class,
        ];
    }

    protected function encoderDecoderModels(): array
    {
        return [
            "t5" => T5Model::class,
        ];
    }

    protected function decoderOnlyModels(): array
    {
        return [
            "gpt2" => GPT2Model::class,
        ];
    }

    protected function sequence2SequenceLMModels(): array
    {
        return [
            "t5" => T5ForConditionalGeneration::class,
            "longt5" => LongT5ForConditionalGeneration::class,
            "mt5" => MT5ForConditionalGeneration::class,
            "bart" => BartForConditionalGeneration::class,
            "mbart" => MBartForConditionalGeneration::class,
        ];
    }

}

<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\ModelGroup;

enum Task: string
{
    case FillMask = 'fill-mask';
    case SentimentAnalysis = 'sentiment-analysis';
    case TextClassification = 'text-classification';
    case QuestionAnswering = 'question-answering';
    case ZeroShotClassification = 'zero-shot-classification';
    case FeatureExtraction = 'feature-extraction';
    case Text2TextGeneration = 'text2text-generation';


    case Ner = 'ner';
    case Summarization = 'summarization';
    case Translation_xx_to_yy = 'translation_xx_to_yy';
    case TextGeneration = 'text-generation';

    public function pipeline(): string
    {
        return match ($this) {
            self::SentimentAnalysis,
            self::TextClassification => TextClassificationPipeline::class,

            self::FillMask => FillMaskPipeline::class,
            self::QuestionAnswering => QuestionAnsweringPipeline::class,
            self::ZeroShotClassification => ZeroShotClassificationPipeline::class,

            self::FeatureExtraction => FeatureExtractionPipeline::class,

            self::Text2TextGeneration => Text2TextGenerationPipeline::class,

            default => throw new \Error("Pipeline for task {$this->value} is not implemented yet."),
        };
    }

    public function defaultModel(): string
    {
        return match ($this) {
            self::SentimentAnalysis,
            self::TextClassification => 'Xenova/distilbert-base-uncased-finetuned-sst-2-english',

            self::FillMask => 'Xenova/bert-base-uncased',

            self::QuestionAnswering => 'Xenova/distilbert-base-uncased-distilled-squad',

            self::ZeroShotClassification => 'Xenova/distilbert-base-uncased-mnli',

            self::FeatureExtraction => 'Xenova/all-MiniLM-L6-v2',

            self::Text2TextGeneration => 'Xenova/flan-t5-small',

            default => throw new \Error("Default model for task {$this->value} is not implemented yet."),
        };
    }

    public function modelGroup(): ModelGroup
    {
        return match ($this) {
            self::FillMask,
            self::QuestionAnswering,
            self::TextClassification,
            self::SentimentAnalysis,
            self::ZeroShotClassification,
            self::FeatureExtraction => ModelGroup::EncoderOnly,

            self::Text2TextGeneration => ModelGroup::Seq2SeqLM,

            default => throw new \Error("Model group for task {$this->value} is not implemented yet."),
        };
    }

}

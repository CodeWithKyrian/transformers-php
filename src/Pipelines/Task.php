<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\ModelGroup;

enum Task: string
{
    case FeatureExtraction = 'feature-extraction';
    case SentimentAnalysis = 'sentiment-analysis';
    case TextClassification = 'text-classification';
    case Ner = 'ner';
    case QuestionAnswering = 'question-answering';
    case FillMask = 'fill-mask';
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

            default => throw new \Error("Default model for task {$this->value} is not implemented yet."),
        };
    }

    public function modelGroup(): ModelGroup
    {
        return match ($this) {
            self::FillMask,
            self::QuestionAnswering,
            self::TextClassification,
            self::SentimentAnalysis => ModelGroup::EncoderOnly,

            default => throw new \Error("Model group for task {$this->value} is not implemented yet."),
        };
    }

}

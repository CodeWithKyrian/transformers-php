<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\Pretrained\PreTrainedModel;
use Codewithkyrian\Transformers\PretrainedTokenizers\PretrainedTokenizer;

enum Task: string
{
    case FillMask = 'fill-mask';
    case SentimentAnalysis = 'sentiment-analysis';
    case TextClassification = 'text-classification';
    case QuestionAnswering = 'question-answering';
    case ZeroShotClassification = 'zero-shot-classification';
    case FeatureExtraction = 'feature-extraction';
    case Embeddings = 'embeddings';
    case Text2TextGeneration = 'text2text-generation';
    case Summarization = 'summarization';
    case Translation = 'translation';
    case TextGeneration = 'text-generation';


    case Ner = 'ner';

    public function getPipeline(PreTrainedModel $model, PretrainedTokenizer $tokenizer): Pipeline
    {
        return match ($this) {
            self::SentimentAnalysis,
            self::TextClassification => new TextClassificationPipeline($this, $model, $tokenizer),

            self::FillMask => new FillMaskPipeline($this, $model, $tokenizer),

            self::QuestionAnswering => new QuestionAnsweringPipeline($this, $model, $tokenizer),

            self::ZeroShotClassification => new ZeroShotClassificationPipeline($this, $model, $tokenizer),

            self::FeatureExtraction,
            self::Embeddings => new FeatureExtractionPipeline($this, $model, $tokenizer),

            self::Text2TextGeneration => new Text2TextGenerationPipeline($this, $model, $tokenizer),

            self::Summarization => new SummarizationPipeline($this, $model, $tokenizer),

            self::Translation => new TranslationPipeline($this, $model, $tokenizer),

            self::TextGeneration => new TextGenerationPipeline($this, $model, $tokenizer),

            default => throw new \Error("Pipeline for task {$this->value} is not implemented yet."),
        };
    }

    public function defaultModel(): string
    {
        return match ($this) {
            self::SentimentAnalysis,
            self::TextClassification => 'Xenova/distilbert-base-uncased-finetuned-sst-2-english',

            self::FillMask => 'Xenova/bert-base-uncased', // Original: 'bert-base-uncased',

            self::QuestionAnswering => 'Xenova/distilbert-base-uncased-distilled-squad',

            self::ZeroShotClassification => 'Xenova/distilbert-base-uncased-mnli',

            self::FeatureExtraction, self::Embeddings => 'Xenova/all-MiniLM-L6-v2', // Original: 'sentence-transformers/all-MiniLM-L6-v2'

            self::Text2TextGeneration => 'Xenova/flan-t5-small', // Original: 'google/flan-t5-small',

            self::Summarization => 'Xenova/distilbart-cnn-6-6', // Original: 'sshleifer/distilbart-cnn-6-6',

            self::Translation => 'Xenova/t5-small', // Original: 't5-small',

            self::TextGeneration => 'Xenova/gpt2', // Original: 'gpt2',

            default => throw new \Error("Default model for task {$this->value} is not implemented yet."),
        };
    }
}

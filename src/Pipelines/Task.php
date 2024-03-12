<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\Auto\AutoModel;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForCausalLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForMaskedLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForQuestionAnswering;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForSeq2SeqLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForSequenceClassification;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForTokenClassification;
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
    case TokenClassification = 'token-classification';
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

            self::TokenClassification,
            self::Ner => new TokenClassificationPipeline($this, $model, $tokenizer),
        };
    }

    public function defaultModelName(): string
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

            self::TokenClassification, self::Ner => 'Xenova/bert-base-multilingual-cased-ner-hrl', // Original: 'Davlan/bert-base-multilingual-cased-ner-hrl',
        };
    }

    public function pretrainedModel(
        string  $modelNameOrPath,
        bool    $quantized = true,
        ?array  $config = null,
        ?string $cacheDir = null,
        ?string $token = null,
        string  $revision = 'main',
        mixed   $legacy = null,
    ): PreTrainedModel
    {
        return match ($this) {
            self::SentimentAnalysis,
            self::TextClassification,
            self::ZeroShotClassification => AutoModelForSequenceClassification::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $token, $revision),

            self::FillMask => AutoModelForMaskedLM::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $token, $revision),

            self::QuestionAnswering => AutoModelForQuestionAnswering::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $token, $revision),

            self::FeatureExtraction,
            self::Embeddings => AutoModel::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $token, $revision),

            self::Text2TextGeneration,
            self::Translation,
            self::Summarization => AutoModelForSeq2SeqLM::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $token, $revision),

            self::TextGeneration => AutoModelForCausalLM::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $token, $revision),

            self::TokenClassification,
            self::Ner => AutoModelForTokenClassification::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $token, $revision),
        };
    }
}

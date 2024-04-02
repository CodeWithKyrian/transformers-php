<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Models\Auto\AutoModel;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForCausalLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForImageClassification;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForMaskedLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForQuestionAnswering;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForSeq2SeqLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForSequenceClassification;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForTokenClassification;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForVision2Seq;
use Codewithkyrian\Transformers\Models\Pretrained\PretrainedModel;
use Codewithkyrian\Transformers\PretrainedTokenizers\AutoTokenizer;
use Codewithkyrian\Transformers\PretrainedTokenizers\PretrainedTokenizer;
use Codewithkyrian\Transformers\Processors\AutoProcessor;
use Codewithkyrian\Transformers\Processors\Processor;
use Symfony\Component\Console\Output\OutputInterface;

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


    case ImageToText = 'image-to-text';
    case ImageClassification = 'image-classification';
    case ZeroShotImageClassification = 'zero-shot-image-classification';


    public function pipeline(PretrainedModel $model, ?PretrainedTokenizer $tokenizer, ?Processor $processor): Pipeline
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

            self::ImageToText => new ImageToTextPipeline($this, $model, $tokenizer, $processor),

            self::ImageClassification => new ImageClassificationPipeline($this, $model, processor: $processor),

            self::ZeroShotImageClassification => new ZeroShotImageClassificationPipeline($this, $model, $tokenizer, $processor)
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

            self::ImageToText => 'Xenova/vit-gpt2-image-captioning', // Original: 'nlpconnect/vit-gpt2-image-captioning'

            self::ImageClassification => 'Xenova/vit-base-patch16-224', // Original: 'google/vit-base-patch16-224'

            self::ZeroShotImageClassification => 'Xenova/clip-vit-base-patch32', // Original: 'openai/clip-vit-base-patch32'
        };
    }

    public function autoModel(
        string           $modelNameOrPath,
        bool             $quantized = true,
        ?array           $config = null,
        ?string          $cacheDir = null,
        string           $revision = 'main',
        ?string          $modelFilename = null,
        ?OutputInterface $output = null
    ): PretrainedModel
    {
        return match ($this) {
            self::SentimentAnalysis,
            self::TextClassification,
            self::ZeroShotClassification => AutoModelForSequenceClassification::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $output),

            self::FillMask => AutoModelForMaskedLM::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $output),

            self::QuestionAnswering => AutoModelForQuestionAnswering::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $output),

            self::FeatureExtraction,
            self::Embeddings => AutoModel::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $output),

            self::Text2TextGeneration,
            self::Translation,
            self::Summarization => AutoModelForSeq2SeqLM::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $output),

            self::TextGeneration => AutoModelForCausalLM::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $output),

            self::TokenClassification,
            self::Ner => AutoModelForTokenClassification::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $output),

            self::ImageToText => AutoModelForVision2Seq::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $output),

            self::ImageClassification => AutoModelForImageClassification::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $output),

            self::ZeroShotImageClassification => AutoModel::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $output),
        };
    }

    public function autoTokenizer(
        string           $modelNameOrPath,
        bool             $quantized = true,
        ?array           $config = null,
        ?string          $cacheDir = null,
        string           $revision = 'main',
        ?OutputInterface $output = null
    ): ?PretrainedTokenizer
    {
        return match ($this) {

            self::ImageClassification => null,


            self::SentimentAnalysis,
            self::TextClassification,
            self::ZeroShotClassification,
            self::FillMask,
            self::QuestionAnswering,
            self::FeatureExtraction,
            self::Embeddings,
            self::Text2TextGeneration,
            self::Translation,
            self::Summarization,
            self::TextGeneration,
            self::TokenClassification,
            self::Ner,
                self::ImageToText,
            self::ZeroShotImageClassification => AutoTokenizer::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, null, $output),
        };
    }

    public function autoProcessor(
        string           $modelNameOrPath,
        ?array           $config = null,
        ?string          $cacheDir = null,
        string           $revision = 'main',
        ?OutputInterface $output = null
    ): ?Processor
    {
        return match ($this) {

            self::ImageToText,
            self::ImageClassification,
            self::ZeroShotImageClassification => AutoProcessor::fromPretrained($modelNameOrPath, $config, $cacheDir, $revision, $output),


            self::SentimentAnalysis,
            self::TextClassification,
            self::ZeroShotClassification,
            self::FillMask,
            self::QuestionAnswering,
            self::FeatureExtraction,
            self::Embeddings,
            self::Text2TextGeneration,
            self::Translation,
            self::Summarization,
            self::TextGeneration,
            self::TokenClassification,
            self::Ner => null,
        };
    }
}

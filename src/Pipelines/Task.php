<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Pipelines;

use Codewithkyrian\Transformers\Exceptions\ModelExecutionException;
use Codewithkyrian\Transformers\Exceptions\UnsupportedModelTypeException;
use Codewithkyrian\Transformers\Models\Auto\AutoModel;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForAudioClassification;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForCausalLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForCTC;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForImageClassification;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForImageFeatureExtraction;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForImageToImage;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForMaskedLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForObjectDetection;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForQuestionAnswering;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForSeq2SeqLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForSequenceClassification;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForSpeechSeq2Seq;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForTokenClassification;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForVision2Seq;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForZeroShotObjectDetection;
use Codewithkyrian\Transformers\Models\Pretrained\PretrainedModel;
use Codewithkyrian\Transformers\PreTrainedTokenizers\AutoTokenizer;
use Codewithkyrian\Transformers\PreTrainedTokenizers\PreTrainedTokenizer;
use Codewithkyrian\Transformers\Processors\AutoProcessor;
use Codewithkyrian\Transformers\Processors\Processor;

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
    case ImageFeatureExtraction = 'image-feature-extraction';
    case ZeroShotImageClassification = 'zero-shot-image-classification';
    case ImageToImage = 'image-to-image';

    case ObjectDetection = 'object-detection';
    case ZeroShotObjectDetection = 'zero-shot-object-detection';

    case AudioClassification = 'audio-classification';
    case AutomaticSpeechRecognition = 'automatic-speech-recognition';
    case ASR = 'asr';


    public function pipeline(PretrainedModel $model, ?PreTrainedTokenizer $tokenizer, ?Processor $processor): Pipeline
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

            self::ImageFeatureExtraction => new ImageFeatureExtractionPipeline($this, $model, processor: $processor),

            self::ZeroShotImageClassification => new ZeroShotImageClassificationPipeline($this, $model, $tokenizer, $processor),

            self::ImageToImage => new ImageToImagePipeline($this, $model, processor: $processor),

            self::ObjectDetection => new ObjectDetectionPipeline($this, $model, $tokenizer, $processor),

            self::ZeroShotObjectDetection => new ZeroShotObjectDetectionPipeline($this, $model, $tokenizer, $processor),

            self::AudioClassification => new AudioClassificationPipeline($this, $model, processor: $processor),

            self::ASR,
            self::AutomaticSpeechRecognition => new AutomaticSpeechRecognitionPipeline($this, $model, $tokenizer, $processor),
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

            self::ImageFeatureExtraction => 'Xenova/vit-base-patch16-224-in21k', // Original: 'google/vit-base-patch16-224-in21k'

            self::ZeroShotImageClassification => 'Xenova/clip-vit-base-patch32', // Original: 'openai/clip-vit-base-patch32'

            self::ImageToImage => 'Xenova/swin2SR-classical-sr-x2-64', // Original: 'caidas/swin2SR-classical-sr-x2-64'

            self::ObjectDetection => 'Xenova/detr-resnet-50', // Original: 'facebook/detr-resnet-50',

            self::ZeroShotObjectDetection => 'Xenova/owlvit-base-patch32', // Original: 'google/owlvit-base-patch32',

            self::AudioClassification => 'Xenova/wav2vec2-base-superb-ks', // Original: 'superb/wav2vec2-base-superb-ks',

            self::ASR,
            self::AutomaticSpeechRecognition => 'Xenova/whisper-tiny.en', // Original: 'openai/whisper-tiny.en',
        };
    }

    public function autoModel(
        string    $modelNameOrPath,
        bool      $quantized = true,
        ?array    $config = null,
        ?string   $cacheDir = null,
        string    $revision = 'main',
        ?string   $modelFilename = null,
        ?callable $onProgress = null
    ): PretrainedModel
    {
        return match ($this) {
            self::SentimentAnalysis,
            self::TextClassification,
            self::ZeroShotClassification => AutoModelForSequenceClassification::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::FillMask => AutoModelForMaskedLM::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::QuestionAnswering => AutoModelForQuestionAnswering::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::FeatureExtraction,
            self::Embeddings => AutoModel::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::Text2TextGeneration,
            self::Translation,
            self::Summarization => AutoModelForSeq2SeqLM::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::TextGeneration => AutoModelForCausalLM::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::TokenClassification,
            self::Ner => AutoModelForTokenClassification::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::ImageToText => AutoModelForVision2Seq::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::ImageClassification => AutoModelForImageClassification::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::ImageFeatureExtraction => AutoModelForImageFeatureExtraction::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::ZeroShotImageClassification => AutoModel::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::ImageToImage => AutoModelForImageToImage::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::ObjectDetection => AutoModelForObjectDetection::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::ZeroShotObjectDetection => AutoModelForZeroShotObjectDetection::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::AudioClassification => AutoModelForAudioClassification::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress),

            self::ASR,
            self::AutomaticSpeechRecognition => (function () use ($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress) {
                try {
                    return AutoModelForSpeechSeq2Seq::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress);
                } catch (UnsupportedModelTypeException) {
                    return AutoModelForCTC::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $revision, $modelFilename, $onProgress);
                }
            })(),
        };
    }

    public function autoTokenizer(
        string    $modelNameOrPath,
        ?string   $cacheDir = null,
        string    $revision = 'main',
        ?callable $onProgress = null
    ): ?PreTrainedTokenizer
    {
        return match ($this) {

            self::ImageClassification,
            self::ImageToImage,
            self::ImageFeatureExtraction,
            self::ObjectDetection,
            self::AudioClassification => null,


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
            self::ZeroShotImageClassification,
            self::ZeroShotObjectDetection,
            self::ASR,
            self::AutomaticSpeechRecognition  => AutoTokenizer::fromPretrained($modelNameOrPath, $cacheDir, $revision, null, $onProgress),
        };
    }

    public function autoProcessor(
        string    $modelNameOrPath,
        ?array    $config = null,
        ?string   $cacheDir = null,
        string    $revision = 'main',
        ?callable $onProgress = null
    ): ?Processor
    {
        return match ($this) {

            self::ImageToText,
            self::ImageClassification,
            self::ImageFeatureExtraction,
            self::ZeroShotImageClassification,
            self::ImageToImage,
            self::ObjectDetection,
            self::ZeroShotObjectDetection,
            self::AudioClassification,
            self::ASR,
            self::AutomaticSpeechRecognition  => AutoProcessor::fromPretrained($modelNameOrPath, $config, $cacheDir, $revision, $onProgress),


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

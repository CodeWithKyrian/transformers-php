<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PreTrainedTokenizers;

use Codewithkyrian\Transformers\Tokenizers\TokenizerModel;
use Codewithkyrian\Transformers\Transformers;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *  Helper class which is used to instantiate pretrained tokenizers with the `from_pretrained` function.
 *  The chosen tokenizer class is determined by the type specified in the tokenizer config.
 */
class AutoTokenizer
{
    /**
     * @template T of PretrainedTokenizer
     * @var class-string<T>
     */
    public const TOKENIZER_CLASS_MAPPING = [
        'T5Tokenizer' => T5Tokenizer::class,
        'DistilBertTokenizer' => DistilBertTokenizer::class,
        'CamembertTokenizer' => CamembertTokenizer::class,
        'DebertaTokenizer' => DebertaTokenizer::class,
        'DebertaV2Tokenizer' => DebertaV2Tokenizer::class,
        'BertTokenizer' => BertTokenizer::class,
        'HerbertTokenizer' => HerbertTokenizer::class,
        'ConvBertTokenizer' => ConvBertTokenizer::class,
        'RoFormerTokenizer' => RoFormerTokenizer::class,
        'XLMTokenizer' => XLMTokenizer::class,
        'ElectraTokenizer' => ElectraTokenizer::class,
        'MobileBertTokenizer' => MobileBertTokenizer::class,
        'SqueezeBertTokenizer' => SqueezeBertTokenizer::class,
        'AlbertTokenizer' => AlbertTokenizer::class,
        'GPT2Tokenizer' => GPT2Tokenizer::class,
        'BartTokenizer' => BartTokenizer::class,
        'MBartTokenizer' => MBartTokenizer::class,
        'MBart50Tokenizer' => MBart50Tokenizer::class,
        'RobertaTokenizer' => RobertaTokenizer::class,
        'WhisperTokenizer' => WhisperTokenizer::class,
        'CodeGenTokenizer' => CodeGenTokenizer::class,
        'CLIPTokenizer' => CLIPTokenizer::class,
        'SiglipTokenizer' => SiglipTokenizer::class,
        // 'MarianTokenizer' => MarianTokenizer::class,
        'BloomTokenizer' => BloomTokenizer::class,
        'NllbTokenizer' => NllbTokenizer::class,
        'M2M100Tokenizer' => M2M100Tokenizer::class,
        'LlamaTokenizer' => LlamaTokenizer::class,
        'CodeLlamaTokenizer' => CodeLlamaTokenizer::class,
        'XLMRobertaTokenizer' => XLMRobertaTokenizer::class,
        'MPNetTokenizer' => MPNetTokenizer::class,
        'FalconTokenizer' => FalconTokenizer::class,
        'GPTNeoXTokenizer' => GPTNeoXTokenizer::class,
        'EsmTokenizer' => EsmTokenizer::class,
        'Wav2Vec2CTCTokenizer' => Wav2Vec2CTCTokenizer::class,
        'BlenderbotTokenizer' => BlenderbotTokenizer::class,
        'BlenderbotSmallTokenizer' => BlenderbotSmallTokenizer::class,
        'SpeechT5Tokenizer' => SpeechT5Tokenizer::class,
        'NougatTokenizer' => NougatTokenizer::class,
        'VitsTokenizer' => VitsTokenizer::class,
        'Qwen2Tokenizer' => Qwen2Tokenizer::class,
        'GemmaTokenizer' => GemmaTokenizer::class,
        'Grok1Tokenizer' => Grok1Tokenizer::class,
        'CohereTokenizer' => CohereTokenizer::class,
        // Base case:
        'PreTrainedTokenizer' => PreTrainedTokenizer::class,
    ];


    /**
     * Instantiate one of the tokenizer classes of the library from a pretrained model.
     *
     *  The tokenizer class to instantiate is selected based on the `tokenizer_class` property of the config object
     *  (either passed as an argument or loaded from `$modelNameOrPath` if possible)
     *
     * @param string $modelNameOrPath The name or path of the pretrained model. Can be either:
     *  - A string, the *model id* of a pretrained tokenizer hosted inside a model repo on huggingface.co.
     *    Valid model ids can be located at the root-level, like `bert-base-uncased`, or namespaced under a
     *    user or organization name, like `dbmdz/bert-base-german-cased`.
     * @param string|null $cacheDir
     * @param string $revision
     * @param mixed $legacy
     * @param OutputInterface|null $output
     *
     * @return PreTrainedTokenizer|null
     */
    public static function fromPretrained(
        string    $modelNameOrPath,
        ?string   $cacheDir = null,
        string    $revision = 'main',
        mixed     $legacy = null,
        ?callable $onProgress = null
    ): ?PreTrainedTokenizer
    {
        ['tokenizerJson' => $tokenizerJson, 'tokenizerConfig' => $tokenizerConfig] =
            TokenizerModel::load($modelNameOrPath, $cacheDir, $revision, $legacy, $onProgress);

        if ($tokenizerJson == null) return null;


        // Some tokenizers are saved with the "Fast" suffix, so we remove that if present.
        $tokenizerClassName = str_replace('Fast', '', $tokenizerConfig['tokenizer_class'] ?? 'PreTrainedTokenizer');

        $cls = self::TOKENIZER_CLASS_MAPPING[$tokenizerClassName] ?? null;

        if ($cls == null) {
            Transformers::getLogger()?->warning("Unknown tokenizer class $tokenizerClassName. Using PreTrainedTokenizer.");

            $cls = PreTrainedTokenizer::class;
        }

        return new $cls($tokenizerJson, $tokenizerConfig);
    }
}

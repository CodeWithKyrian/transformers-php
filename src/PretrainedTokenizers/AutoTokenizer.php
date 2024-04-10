<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PretrainedTokenizers;

use Codewithkyrian\Transformers\Tokenizers\Tokenizer;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *  Helper class which is used to instantiate pretrained tokenizers with the `from_pretrained` function.
 *  The chosen tokenizer class is determined by the type specified in the tokenizer config.
 */
class AutoTokenizer
{
    public const TOKENIZER_CLASS_MAPPING = [
        'T5Tokenizer' => 'T5Tokenizer',
        'DistilBertTokenizer' => 'DistilBertTokenizer',
        'CamembertTokenizer' => 'CamembertTokenizer',
        'DebertaTokenizer' => 'DebertaTokenizer',
        'DebertaV2Tokenizer' => 'DebertaV2Tokenizer',
        'BertTokenizer' => 'BertTokenizer',
        'HerbertTokenizer' => 'HerbertTokenizer',
        'ConvBertTokenizer' => 'ConvBertTokenizer',
        'RoFormerTokenizer' => 'RoFormerTokenizer',
        'XLMTokenizer' => 'XLMTokenizer',
        'ElectraTokenizer' => 'ElectraTokenizer',
        'MobileBertTokenizer' => 'MobileBertTokenizer',
        'SqueezeBertTokenizer' => 'SqueezeBertTokenizer',
        'AlbertTokenizer' => 'AlbertTokenizer',
        'GPT2Tokenizer' => 'GPT2Tokenizer',
        'BartTokenizer' => 'BartTokenizer',
        'MBartTokenizer' => 'MBartTokenizer',
        'MBart50Tokenizer' => 'MBart50Tokenizer',
        'RobertaTokenizer' => 'RobertaTokenizer',
        'WhisperTokenizer' => 'WhisperTokenizer',
        'CodeGenTokenizer' => 'CodeGenTokenizer',
        'CLIPTokenizer' => 'CLIPTokenizer',
        'SiglipTokenizer' => 'SiglipTokenizer',
        'MarianTokenizer' => 'MarianTokenizer',
        'BloomTokenizer' => 'BloomTokenizer',
        'NllbTokenizer' => 'NllbTokenizer',
        'M2M100Tokenizer' => 'M2M100Tokenizer',
        'LlamaTokenizer' => 'LlamaTokenizer',
        'CodeLlamaTokenizer' => 'CodeLlamaTokenizer',
        'XLMRobertaTokenizer' => 'XLMRobertaTokenizer',
        'MPNetTokenizer' => 'MPNetTokenizer',
        'FalconTokenizer' => 'FalconTokenizer',
        'GPTNeoXTokenizer' => 'GPTNeoXTokenizer',
        'EsmTokenizer' => 'EsmTokenizer',
        'Wav2Vec2CTCTokenizer' => 'Wav2Vec2CTCTokenizer',
        'BlenderbotTokenizer' => 'BlenderbotTokenizer',
        'BlenderbotSmallTokenizer' => 'BlenderbotSmallTokenizer',
        'SpeechT5Tokenizer' => 'SpeechT5Tokenizer',
        'NougatTokenizer' => 'NougatTokenizer',
        'VitsTokenizer' => 'VitsTokenizer',
        // Base case:
        'PreTrainedTokenizer' => 'PreTrainedTokenizer',
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
     * @return PretrainedTokenizer|null
     */
    public static function fromPretrained(
        string           $modelNameOrPath,
        ?string          $cacheDir = null,
        string           $revision = 'main',
        mixed            $legacy = null,
        ?OutputInterface $output = null
    ): ?PretrainedTokenizer
    {
        ['tokenizerJson' => $tokenizerJson, 'tokenizerConfig' => $tokenizerConfig] =
            Tokenizer::load($modelNameOrPath, $cacheDir, $revision, $legacy, $output);

        if ($tokenizerJson == null) return null;


        // Some tokenizers are saved with the "Fast" suffix, so we remove that if present.
        $tokenizerClassName = str_replace('Fast', '', $tokenizerConfig['tokenizer_class'] ?? 'PreTrainedTokenizer');

        $cls = self::TOKENIZER_CLASS_MAPPING[$tokenizerClassName] ?? null;

        if ($cls == null) {
            echo "Unknown tokenizer class $tokenizerClassName. Using PreTrainedTokenizer. \n";

            $cls = 'PreTrainedTokenizer';
        }

        // Build the fully qualified class name
        $cls = __NAMESPACE__ . '\\' . $cls;

        return new $cls($tokenizerJson, $tokenizerConfig);
    }
}
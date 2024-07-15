<?php /** @noinspection PhpUnreachableStatementInspection */

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Tokenizers;

use ArrayObject;
use Codewithkyrian\Transformers\Exceptions\HubException;
use Codewithkyrian\Transformers\Utils\Hub;
use Exception;

abstract class Tokenizer
{
    public const SPECIAL_TOKEN_ATTRIBUTES = [
        'bos_token',
        'eos_token',
        'unk_token',
        'sep_token',
        'pad_token',
        'cls_token',
        'mask_token',
        // additional_special_tokens (TODO)
    ];
    /**
     * An array of tokens.
     * @var string[]
     */
    public array $vocab = [];
    /**
     * A mapping of tokens to ids.
     * @var array<string, int>
     */
    public array $tokenToIds = [];
    public ?string $endOfWordSuffix = null;
    public ?string $continuingSubwordPrefix = null;
    /**
     *The id of the unknown token.
     */
    protected ?int $unkTokenId = null;
    /**
     * The unknown token string.
     */
    protected ?string $unkToken = null;

    public function __construct(protected array $config)
    {
        $this->continuingSubwordPrefix = $config['continuing_subword_prefix'] ?? null;
        if ($this->continuingSubwordPrefix == "") {
            $this->continuingSubwordPrefix = null;
        }
    }

    /**
     * Instantiates a new TokenizerModel instance based on the configuration object provided.
     */
    public static function fromConfig(array $config, ...$args): self
    {
        return match ($config['type'] ?? null) {
            'WordPiece' => new WordPieceTokenizer($config),
            'Unigram' => new UnigramTokenizer($config, ...$args),
            'BPE' => new BPETokenizer($config),
            default => (function () use ($config, $args) {
                if ($config['vocab'] ?? false) {
                    return new LegacyTokenizer($config, ...$args);
                }

                throw new Exception("Unknown tokenizer type {$config['type']}");
            })()
        };
    }

    /**
     * Loads a tokenizer from the specified path.
     *
     * @param string $modelNameOrPath The path to the tokenizer model directory
     * @param string|null $cacheDir
     * @param string $revision
     * @param mixed $legacy
     * @param callable|null $onProgress
     * @return array {tokenizerJson: array, tokenizerConfig: array}
     * @throws HubException
     */
    public static function load(
        string           $modelNameOrPath,
        ?string          $cacheDir,
        string           $revision,
        mixed            $legacy,
        ?callable $onProgress = null
    ): array
    {
        $tokenizerJson = Hub::getJson(
            $modelNameOrPath,
            fileName: 'tokenizer.json',
            cacheDir: $cacheDir,
            revision: $revision,
            fatal: false,
            onProgress: $onProgress
        );

        $tokenizerConfig = Hub::getJson(
            $modelNameOrPath,
            fileName: 'tokenizer_config.json',
            cacheDir: $cacheDir,
            revision: $revision,
            fatal: false,
            onProgress: $onProgress
        );

        if ($legacy != null) {
            $tokenizerConfig['legacy'] = $legacy;
        }

        return [
            'tokenizerJson' => $tokenizerJson,
            'tokenizerConfig' => $tokenizerConfig,
        ];
    }

    /**
     * Helper function to split a string on whitespace.
     *
     * @param string $text The text to split.
     * @return string[] The split text.
     */
    public static function whitespaceSplit(string $text): array
    {
        return preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Helper function to lowercase a string and remove accents.
     * @param string $text The text to lowercase and remove accents from.
     * @return string The text with accents removed and lowercased.
     */
    public static function lowerCaseAndRemoveAccents(string $text): string
    {
        return mb_strtolower(self::removeAccents($text));
    }

    /**
     * Helper function to remove accents from a string.
     * @param string $text The text to remove accents from.
     * @return string The text with accents removed.
     */
    public static function removeAccents(string $text): string
    {
        return preg_replace('/[\x{0300}-\x{036f}]/u', '', $text);
    }

    /**
     * Clean up a list of simple English tokenization artifacts like spaces before punctuations and abbreviated forms
     *
     * @param string $text The text to clean up.
     * @return string The cleaned up text.
     */
    public static function cleanUpTokenization(string|int $text): string
    {
        if (is_int($text)) {
            $text = (string)$text;
        }

        $text = preg_replace('/ \./', '.', $text);
        $text = preg_replace('/ \?/', '?', $text);
        $text = preg_replace('/ \!/', '!', $text);
        $text = preg_replace('/ ,/', ',', $text);
        $text = preg_replace('/ \' /', "'", $text);
        $text = preg_replace('/ n\'t/', "n't", $text);
        $text = preg_replace('/ \'m/', "'m", $text);
        $text = preg_replace('/ \'s/', "'s", $text);
        $text = preg_replace('/ \'ve/', "'ve", $text);

        return preg_replace('/ \'re/', "'re", $text);
    }

    public static function toMap(array $arr): array
    {
        $arrayObject = new ArrayObject($arr);

        return $arrayObject->getArrayCopy();
    }

    public static function unicodeToBytes(): array
    {
        return array_flip(self::bytesToUnicode());
    }

    /**
     * Returns list of utf-8 byte and a mapping to unicode strings.
     * Specifically avoids mapping to whitespace/control characters the BPE code barfs on.
     * @returns array Object with utf-8 byte keys and unicode string values.
     */
    public static function bytesToUnicode(): array
    {
        $bs = array_merge(
            range(mb_ord('!'), mb_ord('~')),
            range(mb_ord('¡'), mb_ord('¬')),
            range(mb_ord('®'), mb_ord('ÿ'))
        );

        $cs = $bs;

        // Adjust to correctly add missing bytes and map them starting from extended ASCII
        $n = 0;
        for ($b = 0; $b < 256; ++$b) {
            if (!in_array($b, $bs)) {
                $bs[] = $b;
                // Here we start mapping to code points beyond the standard ASCII range
                $cs[] = 256 + $n;
                $n += 1;
            }
        }

        // Convert $cs array elements to their corresponding Unicode characters
        $cs = array_map(fn($code) => mb_chr($code), $cs);

        return array_combine($bs, $cs);
    }

    /**
     * Internal function to call the TokenizerModel instance.
     * @param string[] $tokens The tokens to encode.
     * @return string[] The encoded token IDs.
     */
    public function __invoke(array $tokens): array
    {
        $ids = $this->encode($tokens);

        if ($this->fuseUnk()) {
            $ids = $this->fuse($ids, $this->unkTokenId, $this->tokenToIds);
        }

        return $ids;
    }

    /**
     * Encodes a list of tokens into a list of token IDs.
     *
     * @param string[] $tokens The tokens to encode.
     * @return string[] The encoded token IDs.
     */
    protected abstract function encode(array $tokens): array;

    protected function fuseUnk(): bool
    {
        return $this->config['fuse_unk'] ?? false;
    }

    /**
     * Helper function to fuse consecutive values in an array equal to the specified value.
     *
     * @param string[] $arr The input array
     * @param mixed $value The value to fuse on.
     * @param array<string, mixed> $mappings The mapping from input domain to value.
     * @return array
     */
    private static function fuse(array $arr, mixed $value, array $mappings): array
    {
        $fused = [];
        $fusedIds = [];
        $fusedLength = 0;

        foreach ($arr as $i => $v) {
            if ($v === $value) {
                $fusedLength++;
            } else {
                if ($fusedLength > 0) {
                    $fused[] = $mappings[$value];
                    $fusedIds[] = $value;
                    $fusedLength = 0;
                }

                $fused[] = $v;
                $fusedIds[] = $i;
            }
        }

        if ($fusedLength > 0) {
            $fused[] = $mappings[$value];
            $fusedIds[] = $value;
        }

        return $fused;
    }

    /**
     * Adds whitespace around any CJK (Chinese, Japanese, or Korean) character in the input text.
     *
     * @param string $text The input text to tokenize.
     * @return string The tokenized text with whitespace added around CJK characters.
     */
    public static function tokenizeChineseChars(string $text): string
    {
        $output = [];
        for ($i = 0; $i < mb_strlen($text); ++$i) {
            $char = mb_substr($text, $i, 1);
            $cp = mb_ord($char);
            if (self::isChineseChar($cp)) {
                $output[] = " ";
                $output[] = $char;
                $output[] = " ";
            } else {
                $output[] = $char;
            }
        }
        return implode("", $output);
    }

    /**
     * Checks whether the given Unicode codepoint represents a CJK (Chinese, Japanese, or Korean) character.
     *
     * A "chinese character" is defined as anything in the CJK Unicode block.
     *
     * @param int $cp The Unicode codepoint to check.
     * @return bool True if the codepoint represents a CJK character, false otherwise.
     */
    public static function isChineseChar(int $cp): bool
    {
        return (
            ($cp >= 0x4E00 && $cp <= 0x9FFF)
            || ($cp >= 0x3400 && $cp <= 0x4DBF)
            || ($cp >= 0x20000 && $cp <= 0x2A6DF)
            || ($cp >= 0x2A700 && $cp <= 0x2B73F)
            || ($cp >= 0x2B740 && $cp <= 0x2B81F)
            || ($cp >= 0x2B820 && $cp <= 0x2CEAF)
            || ($cp >= 0xF900 && $cp <= 0xFAFF)
            || ($cp >= 0x2F800 && $cp <= 0x2FA1F)
        );
    }

    /**
     * Converts a list of tokens into a list of token IDs.
     *
     * @param string[] $tokens The tokens to convert.
     * @return int[] The converted token IDs.
     */
    public function convertTokensToIds(array $tokens): array
    {
        $ids = [];

        foreach ($tokens as $token) {
            $ids[] = $this->tokenToIds[$token] ?? $this->unkTokenId;
        }


        return $ids;
    }

    /**
     * Converts a list of token IDs into a list of tokens.
     *
     * @param string[] $ids The token IDs to convert.
     * @return string[] The converted tokens.
     */
    public function convertIdsToTokens(array $ids): array
    {
        $tokens = [];

        foreach ($ids as $id) {
            $tokens[] = $this->vocab[$id] ?? $this->unkToken;
        }

        return $tokens;
    }
}
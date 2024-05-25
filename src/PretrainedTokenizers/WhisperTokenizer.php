<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PretrainedTokenizers;

use Exception;
use function Codewithkyrian\Transformers\Utils\array_every;

class WhisperTokenizer extends PretrainedTokenizer
{
    protected string $defaultChatTemplate = '{% for message in messages %}" "{{ message.content }}{{ eos_token }}" "{% endfor %}';

    private const WHISPER_LANGUAGES = [
        "en" => "english",
        "zh" => "chinese",
        "de" => "german",
        "es" => "spanish",
        "ru" => "russian",
        "ko" => "korean",
        "fr" => "french",
        "ja" => "japanese",
        "pt" => "portuguese",
        "tr" => "turkish",
        "pl" => "polish",
        "ca" => "catalan",
        "nl" => "dutch",
        "ar" => "arabic",
        "sv" => "swedish",
        "it" => "italian",
        "id" => "indonesian",
        "hi" => "hindi",
        "fi" => "finnish",
        "vi" => "vietnamese",
        "he" => "hebrew",
        "uk" => "ukrainian",
        "el" => "greek",
        "ms" => "malay",
        "cs" => "czech",
        "ro" => "romanian",
        "da" => "danish",
        "hu" => "hungarian",
        "ta" => "tamil",
        "no" => "norwegian",
        "th" => "thai",
        "ur" => "urdu",
        "hr" => "croatian",
        "bg" => "bulgarian",
        "lt" => "lithuanian",
        "la" => "latin",
        "mi" => "maori",
        "ml" => "malayalam",
        "cy" => "welsh",
        "sk" => "slovak",
        "te" => "telugu",
        "fa" => "persian",
        "lv" => "latvian",
        "bn" => "bengali",
        "sr" => "serbian",
        "az" => "azerbaijani",
        "sl" => "slovenian",
        "kn" => "kannada",
        "et" => "estonian",
        "mk" => "macedonian",
        "br" => "breton",
        "eu" => "basque",
        "is" => "icelandic",
        "hy" => "armenian",
        "ne" => "nepali",
        "mn" => "mongolian",
        "bs" => "bosnian",
        "kk" => "kazakh",
        "sq" => "albanian",
        "sw" => "swahili",
        "gl" => "galician",
        "mr" => "marathi",
        "pa" => "punjabi",
        "si" => "sinhala",
        "km" => "khmer",
        "sn" => "shona",
        "yo" => "yoruba",
        "so" => "somali",
        "af" => "afrikaans",
        "oc" => "occitan",
        "ka" => "georgian",
        "be" => "belarusian",
        "tg" => "tajik",
        "sd" => "sindhi",
        "gu" => "gujarati",
        "am" => "amharic",
        "yi" => "yiddish",
        "lo" => "lao",
        "uz" => "uzbek",
        "fo" => "faroese",
        "ht" => "haitian creole",
        "ps" => "pashto",
        "tk" => "turkmen",
        "nn" => "nynorsk",
        "mt" => "maltese",
        "sa" => "sanskrit",
        "lb" => "luxembourgish",
        "my" => "myanmar",
        "bo" => "tibetan",
        "tl" => "tagalog",
        "mg" => "malagasy",
        "as" => "assamese",
        "tt" => "tatar",
        "haw" => "hawaiian",
        "ln" => "lingala",
        "ha" => "hausa",
        "ba" => "bashkir",
        "jw" => "javanese",
        "su" => "sundanese",
    ];

    protected array $WHISPER_LANGUAGE_TO_CODE;


    public function __construct(array $tokenizerJSON, array $tokenizerConfig)
    {
        parent::__construct($tokenizerJSON, $tokenizerConfig);

        $this->WHISPER_LANGUAGE_TO_CODE = [
            ...array_flip(self::WHISPER_LANGUAGES),
            "burmese" => "my",
            "valencian" => "ca",
            "flemish" => "nl",
            "haitian" => "ht",
            "letzeburgesch" => "lb",
            "pushto" => "ps",
            "panjabi" => "pa",
            "moldavian" => "ro",
            "moldovan" => "ro",
            "sinhalese" => "si",
            "castilian" => "es",
        ];
    }


    /**
     * Decodes automatic speech recognition (ASR) sequences.
     *
     * @param array $sequences The sequences to decode, each sequence is an associative array with 'tokens', 'token_timestamps', and 'stride'.
     * @param bool $returnTimestamps Whether to return timestamps.
     * @param bool $returnLanguage Whether to return language.
     * @param float $timePrecision The precision of the timestamps in seconds.
     * @param bool $forceFullSequences Whether to force full sequences (default is true).
     * @return array The decoded sequences.
     * @throws Exception If timePrecision is not specified.
     */
    public function decodeASR(
        array       $sequences,
        float       $timePrecision,
        bool|string $returnTimestamps = false,
        bool        $returnLanguage = false,
        bool        $forceFullSequences = true
    ): array
    {
        // Set forceFullSequences=false if you want streaming
        // TODO add support for `returnLanguage`

        // Internal method meant to only be used by ASR pipeline.
        // Handles all the little quirks specific to whisper to handle
        // the various options not allowed in other seq2seq models

        // =========== Overview ============
        // - iterate over all outputs
        // - all tokens within output
        // - Each token can be
        //   - language token
        //   - special token
        //   - timestamp token
        //   - text token
        // - We accumulate the text tokens.
        // - We split on end timestamps
        // - Lots of complexity comes from stride and timestamps

        $lastLanguage = null;

        $returnWordTimestamps = $returnTimestamps === "word";

        $newChunk = fn() => ["language" => null, "timestamp" => [null, null], "text" => ""];

        // Welcome to the state machine!
        $chunks = [];
        $chunk = $newChunk();
        $timeOffset = 0.0;
        $timestampBegin = $this->tokenizer->convertTokensToIds(["<|notimestamps|>"])[0] + 1;

        $previousTokens = [];
        $previousTokenTimestamps = [];
        $skip = false;

        // Iterate over sequences
        foreach ($sequences as $output) {
            $tokenIds = $output['tokens'];
            $tokenTimestamps = $returnWordTimestamps ? $output['token_timestamps'] : null;

            $lastTimestamp = null;
            $firstTimestamp = $timestampBegin;

            if (isset($output['stride'])) {
                [$chunkLen, $strideLeft, $strideRight] = $output['stride'];

                // Offset the timings to account for the other `model_outputs`.
                $timeOffset -= $strideLeft;
                $rightStrideStart = $chunkLen - $strideRight;

                if ($strideLeft) {
                    $firstTimestamp = $strideLeft / $timePrecision + $timestampBegin;
                }

                if ($strideRight) {
                    for ($i = count($tokenIds) - 1; $i >= 0; --$i) {
                        $token = $tokenIds[$i];
                        if ($token >= $timestampBegin) {
                            if ($lastTimestamp !== null && ($token - $timestampBegin) * $timePrecision < $rightStrideStart) {
                                break;
                            }
                            $lastTimestamp = $token;
                        }
                    }
                }
            }

            $currentTokens = [];
            $currentTokenTimestamps = [];

            foreach ($tokenIds as $i => $token) {
                // 4 possible states for each token
                // - 1/ Language code
                // - 2/ all other special tokens (which we ignore)
                // - 3/ Timestamp
                // - 4/ Regular text
                if (in_array($token, $this->allSpecialIds)) {
                    $text = $this->decode([$token]);
                    $language = self::WHISPER_LANGUAGES[substr($text, 2, -2)] ?? null;

                    if ($language !== null) {
                        // 1/ Indeed some language
                        // TODO Handle when language is different from the previous
                        // one, and we cannot use timestamped tokens to create chunks
                        if ($lastLanguage !== null && $language !== $lastLanguage && !$returnTimestamps) {
                            $previousTokens[] = $currentTokens;
                            $resolvedTokens = $this->findLongestCommonSequence($previousTokens)[0];
                            $resolvedText = $this->decode($resolvedTokens);
                            $chunk['text'] = $resolvedText;
                            $chunks[] = $chunk;

                            $previousTokens = [];
                            $currentTokens = [];
                            $chunk = $newChunk();
                        }

                        $lastLanguage = $chunk['language'] = $language;
                    } else {
                        // 2/ This is a regular special token, ignoring it
                    }
                } elseif ($token >= $timestampBegin) {
                    // 3/ Timestamp token
                    $time = ($token - $timestampBegin) * $timePrecision + $timeOffset;
                    $roundedTime = round($time, 2);

                    if ($lastTimestamp !== null && $token >= $lastTimestamp) {
                        // Whisper outputted a timestamp token, but it falls within
                        // our stride, so we're going to skip it for the time being
                        // and resolve this later
                        // Skip is necessary because timestamp tokens always come
                        // by pair, so we need to skip the next one too (which would mark the start of another chunk).
                        $skip = true;
                    } elseif ($skip || (!empty($previousTokens) && $token < $firstTimestamp)) {
                        $skip = false;
                    } elseif ($chunk['timestamp'][0] === null) {
                        $chunk['timestamp'][0] = $roundedTime;
                    } else {
                        // This is the end of the timestamp chunk
                        if ($roundedTime !== $chunk['timestamp'][0]) {
                            $chunk['timestamp'][1] = $roundedTime;
                            $previousTokens[] = $currentTokens;

                            if ($returnWordTimestamps) {
                                $previousTokenTimestamps[] = $currentTokenTimestamps;
                            }

                            [$resolvedTokens, $resolvedTokenTimestamps] = $this->findLongestCommonSequence(
                                $previousTokens, $previousTokenTimestamps
                            );

                            $resolvedText = $this->decode($resolvedTokens);
                            $chunk['text'] = $resolvedText;

                            if ($returnWordTimestamps) {
                                $chunk['words'] = $this->collateWordTimestamps(
                                    $resolvedTokens, $resolvedTokenTimestamps, $lastLanguage
                                );
                            }

                            $chunks[] = $chunk;

                            $previousTokens = [];
                            $currentTokens = [];
                            $previousTokenTimestamps = [];
                            $currentTokenTimestamps = [];
                            $chunk = $newChunk();
                        }
                        else {
                            // This is a bug in timestamp token output
                            // where we're taking the duplicate token
                            // as a stop where it should be a start.
                            // This is an issue in the underlying model output
                            // Let's just skip it so it becomes de-factor a start agin
                        }
                    }
                } else {
                    // 4/ Regular token
                    // We just append to the list of all tokens so we can handle
                    // merges later and decode into text.
                    $currentTokens[] = $token;

                    if ($returnWordTimestamps) {
                        $startTime = round($tokenTimestamps[$i] + $timeOffset, 2);
                        $endTime = $i + 1 < count($tokenTimestamps) ? round($tokenTimestamps[$i + 1] + $timeOffset, 2) : null;
                        $currentTokenTimestamps[] = [$startTime, $endTime];
                    }
                }
            }

//            dump($this->decode($currentTokens), empty($previousTokens) ? '': $this->decode($previousTokens[0]));
            if (isset($output['stride'])) {
                [$chunkLen, $strideLeft, $strideRight] = $output['stride'];
                $timeOffset += $chunkLen - $strideRight;
            }

            if (!empty($currentTokens)) {
                $previousTokens[] = $currentTokens;

                if ($returnWordTimestamps) {
                    $previousTokenTimestamps[] = $currentTokenTimestamps;
                }
            } elseif (array_every($previousTokens, fn($x) => empty($x))) {
                $chunk = $newChunk();
                $previousTokens = [];
                $currentTokens = [];
                $previousTokenTimestamps = [];
                $currentTokenTimestamps = [];
            }
        }

        if (count($previousTokens) > 0) {
            if ($forceFullSequences && $returnTimestamps) {
                // Last token should always be timestamps, so there shouldn't be leftover
                throw new Exception(
                    "Whisper did not predict an ending timestamp, which can happen if audio is cut off in the middle of a word. " .
                    "Also make sure WhisperTimeStampLogitsProcessor was used during generation."
                );
            }

            // Happens when we don't use timestamps
            [$resolvedTokens, $resolvedTokenTimestamps] = $this->findLongestCommonSequence($previousTokens, $previousTokenTimestamps);

            // Flushing previous tokens (FINAL)
            $resolvedText = $this->decode($resolvedTokens);
            $chunk['text'] = $resolvedText;
            if ($returnWordTimestamps) {
                $chunk['words'] = $this->collateWordTimestamps($resolvedTokens, $resolvedTokenTimestamps, $lastLanguage);
            }
            $chunks[] = $chunk;
        }

        $optional = [];

        $fullText = implode('', array_map(fn($chunk) => $chunk['text'], $chunks));

        if ($returnTimestamps || $returnLanguage) {
            for ($i = 0; $i < count($chunks); $i++) {
                if (!$returnTimestamps) {
                    unset($chunks[$i]['timestamp']);
                }

                if (!$returnLanguage) {
                    unset($chunks[$i]['language']);
                }
            }

            if ($returnWordTimestamps) {
                $newChunks = [];
                foreach ($chunks as $chunk) {
                    foreach ($chunk['words'] as $word) {
                        $newChunks[] = $word;
                    }
                }
                $optional = ["chunks" => $newChunks];
            } else {
                $optional = ["chunks" => $chunks];
            }
        }

        return [$fullText, $optional];

    }

    /**
     * Finds the longest common sequence among the provided sequences.
     * @param array $sequences An array of sequences of token ids to compare.
     * @param array|null $tokenTimestampSequences Optional array of token timestamp sequences.
     * @return array The longest common sequence found.
     * @throws Exception If there is a bug within the function.
     * @private
     */
    private function findLongestCommonSequence(array $sequences, array $tokenTimestampSequences = null): array
    {
        $leftSequence = $sequences[0];
        $leftLength = count($leftSequence);
        $totalSequence = [];

        $useTokenTimestampSequences = is_array($tokenTimestampSequences) && count($tokenTimestampSequences) > 0;
        $totalTokenTimestampSequence = $useTokenTimestampSequences ? [] : null;
        $leftTokenTimestampSequence = $useTokenTimestampSequences ? $tokenTimestampSequences[0] : null;

        for ($i = 1; $i < count($sequences); ++$i) {
            $rightSequence = $sequences[$i];
            $max = 0.0;
            $maxIndices = [$leftLength, $leftLength, 0, 0];

            $rightLength = count($rightSequence);
            for ($j = 1; $j < $leftLength + $rightLength; ++$j) {
                // epsilon to favor long perfect matches
                $eps = $j / 10000.0;
                $leftStart = max(0, $leftLength - $j);
                $leftStop = min($leftLength, $leftLength + $rightLength - $j);
                $left = array_slice($leftSequence, $leftStart, $leftStop - $leftStart);
                $rightStart = max(0, $j - $leftLength);
                $rightStop = min($rightLength, $j);
                $right = array_slice($rightSequence, $rightStart, $rightStop - $rightStart);

                if (count($left) !== count($right)) {
                    throw new Exception("There is a bug within whisper `decodeASR` function, please report it. Dropping to prevent bad inference.");
                }

                $matches = count(
                    array_filter($left, fn($elem, $idx) => $elem === $right[$idx], ARRAY_FILTER_USE_BOTH)
                );

                $matching = $matches / $j + $eps;
                if ($matches > 1 && $matching > $max) {
                    $max = $matching;
                    $maxIndices = [$leftStart, $leftStop, $rightStart, $rightStop];
                }
            }

            [$leftStart, $leftStop, $rightStart, $rightStop] = $maxIndices;
            $leftMid = (int)floor(($leftStop + $leftStart) / 2);
            $rightMid = (int)floor(($rightStop + $rightStart) / 2);
            $totalSequence = array_merge($totalSequence, array_slice($leftSequence, 0, $leftMid));
            $leftSequence = array_slice($rightSequence, $rightMid);
            $leftLength = count($leftSequence);

            if ($useTokenTimestampSequences) {
                $totalTokenTimestampSequence = array_merge($totalTokenTimestampSequence, array_slice($leftTokenTimestampSequence, 0, $leftMid));
                $leftTokenTimestampSequence = array_slice($tokenTimestampSequences[$i], $rightMid);
            }
        }

        $totalSequence = array_merge($totalSequence, $leftSequence);

        if ($useTokenTimestampSequences) {
            $totalTokenTimestampSequence = array_merge($totalTokenTimestampSequence, $leftTokenTimestampSequence);
            return [$totalSequence, $totalTokenTimestampSequence];
        } else {
            return [$totalSequence, []];
        }
    }

    public function collateWordTimestamps($tokens, $token_timestamps, $language): array
    {
        [$words, , $token_indices] = $this->combineTokensIntoWords($tokens, $language);

        $timings = [];
        foreach ($words as $i => $word) {
            $indices = $token_indices[$i];
            $timings[] = [
                'text' => $word,
                'timestamp' => [
                    $token_timestamps[$indices[0]][0],
                    $token_timestamps[end($indices)][1],
                ],
            ];
        }
        return $timings;
    }

    /**
     * Groups tokens by word. Returns a tuple containing a list of strings with the words,
     * and a list of `token_id` sequences with the tokens making up each word.
     * @param array $tokens
     * @param string|null $language
     * @return array
     * @private
     */
    private function combineTokensIntoWords(
        array  $tokens,
        string $language = null
    ): array
    {
        $language = $language ?? 'english';
        $prependPunctuations = "\"'“¡¿([{-";
        $appendPunctuations = "\"'.。,，!！?？:：”)]}、";

        if (in_array($language, ["chinese", "japanese", "thai", "lao", "myanmar"])) {
            // These languages don't typically use spaces.
            [$words, $wordTokens, $tokenIndices] = $this->splitTokensOnUnicode($tokens);
        } else {
            [$words, $wordTokens, $tokenIndices] = $this->splitTokensOnSpaces($tokens);
        }

        return $this->mergePunctuations($words, $wordTokens, $tokenIndices, $prependPunctuations, $appendPunctuations);
    }

    public function decode(
        array $tokenIds,
        bool  $skipSpecialTokens = false,
        ?bool $cleanUpTokenizationSpaces = null,
        bool  $decodeWithTimestamps = null,
        float $timePrecision = 0.02
    ): string
    {

        if ($decodeWithTimestamps) {
            $text = $this->decodeWithTimestamps($tokenIds, $skipSpecialTokens, $cleanUpTokenizationSpaces, $timePrecision);
        } else {
            $text = parent::decode($tokenIds, $skipSpecialTokens, $cleanUpTokenizationSpaces);
        }

        // TODO: implement offsets
        // if (isset($decode_args['output_offsets']) && $decode_args['output_offsets']) {
        //     $offsets = $this->computeOffsets();
        // }
        return $text;
    }

    private function decodeWithTimestamps(
        array $tokenIds,
        bool  $skipSpecialTokens = false,
        ?bool $cleanUpTokenizationSpaces = null,
        float $timePrecision = 0.02
    ): string
    {
        $timestampBegin = end($this->allSpecialIds) + 1;
        $outputs = [[]];

        foreach ($tokenIds as $token) {
            if ($token >= $timestampBegin) {
                $timestamp = round(($token - $timestampBegin) * $timePrecision, 2);
                $outputs[] = "<|$timestamp|>";
                $outputs[] = [];
            } else {
                $outputs[count($outputs) - 1][] = $token;
            }
        }

        $outputs = array_map(fn($s) => is_string($s) ? $s : parent::decode($tokenIds, $skipSpecialTokens, $cleanUpTokenizationSpaces), $outputs);

        return implode('', $outputs);
    }

    /**
     * Combine tokens into words by splitting at any position where the tokens are decoded as valid Unicode points.
     * @param array $tokens
     * @return array
     * @private
     */
    private function splitTokensOnUnicode(array $tokens): array
    {
        $decodedFull = $this->decode($tokens, decodeWithTimestamps: true);

        $replacementChar = "\u{FFFD}";

        $words = [];
        $wordTokens = [];
        $tokenIndices = [];
        $currentTokens = [];
        $currentIndices = [];
        $unicodeOffset = 0;

        foreach ($tokens as $token_idx => $token) {
            $currentTokens[] = $token;
            $currentIndices[] = $token_idx;

            $decoded = $this->decode($currentTokens, decodeWithTimestamps: true);

            if (!str_contains($decoded, $replacementChar) || $decodedFull[$unicodeOffset + strpos($decoded, $replacementChar)] === $replacementChar) {
                $words[] = $decoded;
                $wordTokens[] = $currentTokens;
                $tokenIndices[] = $currentIndices;
                $currentTokens = [];
                $currentIndices = [];
                $unicodeOffset += strlen($decoded);
            }
        }

        return [$words, $wordTokens, $tokenIndices];
    }

    /**
     * Combine tokens into words by splitting at whitespace and punctuation tokens.
     * @param array $tokens
     * @return array
     * @private
     */
    private function splitTokensOnSpaces(array $tokens): array
    {
        [$subwords, $subwordTokensList, $subwordIndicesList] = $this->splitTokensOnUnicode($tokens);

        $words = [];
        $word_tokens = [];
        $token_indices = [];

//        $punctuationRegex = '/^\p{P}+$/u';
        $punctuationRegex = '\p{P}\x21-\x2F\x3A-\x40\x5B-\x60\x7B-\x7E';
        $punctuationRegex = "/\s+|([$punctuationRegex])+/u";

        foreach ($subwords as $i => $subword) {
            $subwordTokens = $subwordTokensList[$i];
            $subwordIndices = $subwordIndicesList[$i];

            $special = $subwordTokens[0] >= $this->tokenizer->tokenToIds['<|endoftext|>'];
            $withSpace = str_starts_with($subword, ' ');
            $trimmed = trim($subword);
            $punctuation = preg_match($punctuationRegex, $trimmed);

            if ($special || $withSpace || $punctuation || empty($words)) {
                $words[] = $subword;
                $word_tokens[] = $subwordTokens;
                $token_indices[] = $subwordIndices;
            } else {
                $ix = count($words) - 1;
                $words[$ix] .= $subword;
                $word_tokens[$ix] = array_merge($word_tokens[$ix], $subwordTokens);
                $token_indices[$ix] = array_merge($token_indices[$ix], $subwordIndices);
            }
        }

        return [$words, $word_tokens, $token_indices];
    }


    /**
     * Merges punctuation tokens with neighboring words.
     * @param array $words
     * @param array $tokens
     * @param array $indices
     * @param string $prepended
     * @param string $appended
     * @return array
     * @private
     */
    private function mergePunctuations(array $words, array $tokens, array $indices, string $prepended, string $appended): array
    {
        $newWords = $words;
        $newTokens = $tokens;
        $newIndices = $indices;

        // prepend punctuations
        $i = count($newWords) - 2;
        $j = count($newWords) - 1;

        while ($i >= 0) {
            if (str_starts_with($newWords[$i], ' ') && str_contains($prepended, trim($newWords[$i]))) {
                $newWords[$j] = $newWords[$i] . $newWords[$j];
                $newTokens[$j] = array_merge($newTokens[$i], $newTokens[$j]);
                $newIndices[$j] = array_merge($newIndices[$i], $newIndices[$j]);
                $newWords[$i] = '';
                $newTokens[$i] = [];
                $newIndices[$i] = [];
            } else {
                $j = $i;
            }
            $i--;
        }

        // append punctuations
        $i = 0;
        $j = 1;
        while ($j < count($newWords)) {
            if (!str_ends_with($newWords[$i], ' ') && str_contains($appended, $newWords[$j])) {
                $newWords[$i] .= $newWords[$j];
                $newTokens[$i] = array_merge($newTokens[$i], $newTokens[$j]);
                $newIndices[$i] = array_merge($newIndices[$i], $newIndices[$j]);
                $newWords[$j] = '';
                $newTokens[$j] = [];
                $newIndices[$j] = [];
            } else {
                $i = $j;
            }
            $j++;
        }

        return [
            array_values(array_filter($newWords, fn($x) => $x !== '')),
            array_values(array_filter($newTokens, fn($x) => count($x) > 0)),
            array_values(array_filter($newIndices, fn($x) => count($x) > 0)),
        ];
    }

    /**
     * Helper function to build translation inputs for a `WhisperTokenizer`,
     * depending on the language, task, and whether to predict timestamp tokens.
     *
     * Used to override the prefix tokens appended to the start of the label sequence.
     *
     * **Example: Get ids for a language**
     * ```php
     * // instantiate the tokenizer and set the prefix token to Spanish
     * $tokenizer = WhisperTokenizer::from_pretrained('Xenova/whisper-tiny');
     * $forced_decoder_ids = $tokenizer->get_decoder_prompt_ids(['language' => 'spanish']);
     * // [(1, 50262), (2, 50363)]
     * ```
     *
     * @param string|null $language The language of the transcription text.
     * The corresponding language id token is appended to the start of the sequence for multilingual
     * speech recognition and speech translation tasks, e.g. for "Spanish" the token "" is appended
     * to the start of sequence.
     * @param string|null $task Task identifier to append at the start of sequence (if any).
     * This should be used for mulitlingual fine-tuning, with "transcribe" for speech recognition and
     * "translate" for speech translation.
     * @param bool $noTimestamps Whether to add the <no_timestamps> token at the start of the sequence.
     * @return array The decoder prompt ids.
     * @throws Exception
     */
    public function getDecoderPromptIds(
        ?string $language = null,
        ?string $task = null,
        bool    $noTimestamps = true
    ): array
    {
        $forcedDecoderIds = [];

        if ($language) {
            // User wishes to specify the language
            $language = strtolower($language);

            // Map to code from user-friendly name (e.g., "english" -> "en")
            $languageCode = $this->WHISPER_LANGUAGE_TO_CODE[$language] ?? null;

            if ($languageCode === null) {
                // User provided something that is not a language name

                if (isset(self::WHISPER_LANGUAGES[$language])) {
                    // User provided the language code directly (e.g., "en")
                    $languageCode = $language;
                } else {
                    // User provided something that is not a language code or name
                    $is_language_code = strlen($language) === 2;
                    $languages = $is_language_code ? array_keys(self::WHISPER_LANGUAGES) : array_values(self::WHISPER_LANGUAGES);

                    throw new Exception("Language \"$language\" is not supported. Must be one of: " . json_encode($languages));
                }
            }

            $languageTokenId = $this->tokenizer->tokenToIds["<|$languageCode|>"] ?? null;
            if ($languageTokenId === null) {
                throw new Exception("Unable to find language \"$languageCode\" in model vocabulary. Please report this issue.");
            }

            $forcedDecoderIds[] = $languageTokenId;
        } else {
            // No token will be forced, which leaves the model to predict the language
            $forcedDecoderIds[] = null;
        }

        if ($task) {
            $task = strtolower($task);
            if ($task !== 'transcribe' && $task !== 'translate') {
                throw new Exception("Task \"$task\" is not supported. Must be one of: [\"transcribe\", \"translate\"]");
            }

            $taskTokenId = $this->tokenizer->tokenToIds["<|$task|>"] ?? null;
            if ($taskTokenId === null) {
                throw new Exception("Unable to find task \"$task\" in model vocabulary. Please report this issue.");
            }

            $forcedDecoderIds[] = $taskTokenId;
        } else {
            // No token will be forced, which leaves the model to predict the task
            $forcedDecoderIds[] = null;
        }

        if ($noTimestamps) {
            $noTimestampsId = $this->tokenizer->tokenToIds["<|$noTimestamps|>"] ?? null;
            if ($noTimestampsId === null) {
                throw new Exception('Unable to find "" in model vocabulary. Please report this issue.');
            }

            $forcedDecoderIds[] = $noTimestampsId;
        }

        // Remove null elements and prepend index numbers
        $result = [];
        $index = 1;
        foreach ($forcedDecoderIds as $id) {
            if ($id !== null) {
                $result[] = [$index++, $id];
            }
        }


        return $result;
    }


}
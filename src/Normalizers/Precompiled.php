<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Normalizers;

use Codewithkyrian\Transformers\DataStructures\CharTrie;
use Generator;

class Precompiled extends Normalizer
{
    /**
     * Normalized chars mapping.
     */
    private string $normalized;

    /**
     * Trie for fast prefix search.
     */
    private CharTrie $trie;

    public function __construct(array $config)
    {
        parent::__construct($config);
        
        $this->parsePrecompiledCharsmap(base64_decode($config['precompiled_charsmap']));
    }

    /**
     * Parses the precompiled charsmap.
     * 
     * @param string $charsMap The precompiled charsmap.
     */
    private function parsePrecompiledCharsmap(string $charsMap): void
    {
        $data = unpack('V', $charsMap , 0);
        $trieSize = $data[1];

        $this->trie = new CharTrie();
        $this->normalized = mb_substr($charsMap, 4 + $trieSize);

        $offset = 0;
        while ($offset < mb_strlen($this->normalized)) {
            $end = mb_strpos($this->normalized, "\0", $offset);
            if ($end === false) {
                break;
            }
            $replacement = mb_substr($this->normalized, $offset, $end - $offset);
            $this->trie->push(mb_chr($offset) . $replacement);
            $offset = $end + 1;
        }
    }

    /**
     * Normalizes the given text by applying the precompiled charsmap.
     *
     * @param string $text The text to normalize.
     *
     * @return string The normalized text.
     */
    public function normalize(string $text): string
    {
        $normalized = '';
        $graphemes = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($graphemes as $grapheme) {
            if (mb_strlen($grapheme) < 6) {
                $norm = $this->transform($grapheme);
                if ($norm !== null) {
                    $normalized .= $norm;
                    continue;
                }
            }

            foreach (preg_split('//u', $grapheme, -1, PREG_SPLIT_NO_EMPTY) as $char) {
                $norm = $this->transform($char);
                if ($norm !== null) {
                    $normalized .= $norm;
                } else {
                    $normalized .= $char;
                }
            }
        }

        return $normalized;
    }

    /**
     * Transforms the given chunk by finding the longest match in the trie.
     * 
     * @param string $chunk The chunk to transform.
     * 
     * @return string|null The transformed chunk or null if no match is found.
     */
    private function transform(string $chunk): ?string
    {
        $results = $this->trie->commonPrefixSearch($chunk);
        $longestMatch = $this->findLongestMatch($results);

        if ($longestMatch === null) {
            return null;
        }

        return mb_substr($longestMatch, 1);
    }

    /**
     * Finds the longest match in the given results.
     * 
     * @param Generator $results The results to find the longest match in.
     * 
     * @return string|null The longest match or null if no match is found.
     */
    private function findLongestMatch(Generator $results): ?string
    {
        $longestMatch = null;
        foreach ($results as $result) {
            if ($longestMatch === null || mb_strlen($result) > mb_strlen($longestMatch)) {
                $longestMatch = $result;
            }
        }
        return $longestMatch;
    }
}
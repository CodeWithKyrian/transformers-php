<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Normalizers;

use Codewithkyrian\Transformers\DataStructures\CharTrie;
use Generator;

class Precompiled extends Normalizer
{
    private $precompiled_charsmap;
    private $normalized;
    private DoubleArray $trie;

    public function __construct($config)
    {
        $this->precompiled_charsmap = $config['precompiled_charsmap'];
        $this->parse(base64_decode($this->precompiled_charsmap));
    }

    private function parse($precompiled_charsmap)
    {
        $trie_size = unpack('V', substr($precompiled_charsmap, 0, 4))[1];
        $trie_char_size = $trie_size / 4;
        $trie_blob = [];
        $offset = 4;
        for ($i = 0; $i < $trie_char_size; $i++) {
            $trie_blob[] = unpack('V', substr($precompiled_charsmap, $offset, 4))[1];
            $offset += 4;
        }
        $this->normalized = substr($precompiled_charsmap, $offset);
        $this->trie = new DoubleArray($trie_blob);
    }

    public function normalize(string $text): string
    {
        $transformations = [];
        $modified = false;
        $graphemes = mb_str_split($text);

        foreach ($graphemes as $grapheme) {
            if (mb_strlen($grapheme) < 6) {
                $norm = $this->transform($grapheme);
                if ($norm !== null) {
                    $modified = true;
                    $this->replace($transformations, $grapheme, $norm);
                    continue;
                }
            }
            $chars = mb_str_split($grapheme);
            foreach ($chars as $char) {
                $norm = $this->transform($char);
                if ($norm !== null) {
                    $modified = true;
                    $this->replace($transformations, $char, $norm);
                } else {
                    $transformations[] = [$char, 0];
                }
            }
        }

        if ($modified) {
            $text =  $this->applyTransformations($text, $transformations);
        }

        // Remove control characters
        $text = preg_replace('/[\x{0001}-\x{0008}\x{000B}\x{000E}-\x{001F}\x{007F}\x{008F}\x{009F}]/u', '', $text);

        // Replace certain characters with a space
        $text = preg_replace('/[\x{0009}\x{000A}\x{000C}\x{000D}\x{1680}\x{200B}\x{200C}\x{200E}\x{200F}\x{2028}\x{2029}\x{2581}\x{FEFF}\x{FFFD}]/u', ' ', $text);

        // Special case handling for Fullwidth Tilde character
        if (mb_strpos($text, "\u{FF5E}") !== false) {
            $parts = explode("\u{FF5E}", $text);
            $normalizedParts = array_map(fn ($part) => $this->normalizeNFKC($part), $parts);
            $text = implode("\u{FF5E}", $normalizedParts);
        } else {
            $text = $this->normalizeNFKC($text);
        }

        return $text;
    }

    private function normalizeNFKC(string $text): string
    {
        // Perform NFKC normalization using PHP's intl extension
        if (class_exists('Normalizer')) {
            return \Normalizer::normalize($text, \Normalizer::FORM_KC);
        }
        return $text; // Fallback if intl extension is not available
    }

    private function transform($chunk): ?string
    {
        $results = $this->trie->commonPrefixSearch($chunk);
        if (empty($results)) {
            return null;
        }
        $index = $results[0];
        $index2 = $index;
        while ($index2 < mb_strlen($this->normalized)) {
            if (ord($this->normalized[$index2]) === 0) {
                break;
            }
            $index2++;
        }
        return mb_substr($this->normalized, $index, $index2 - $index);
    }

    private function replace(&$transformations, $old_part, $new_part): void
    {
        $old_count = mb_strlen($old_part);
        $new_count = mb_strlen($new_part);
        $diff = $new_count - $old_count;

        foreach (mb_str_split($new_part) as $char) {
            $transformations[] = [$char, 0];
        }

        if ($diff > 0) {
            for ($i = 0; $i < $diff; $i++) {
                $transformations[count($transformations) - 1 - $i][1] = 1;
            }
        } elseif ($diff < 0) {
            $transformations[count($transformations) - 1][1] += $diff;
        }
    }

    private function applyTransformations($original, $transformations): string
    {
        $result = '';
        $offset = 0;
        foreach ($transformations as [$char, $change]) {
            $result .= $char;
            $offset += $change;
        }
        return $result;
    }
}

class DoubleArray
{

    public function __construct(protected array $array) {}

    public function commonPrefixSearch($key): array
    {
        $node_pos = 0;
        $results = [];

        $unit = $this->array[$node_pos];
        $node_pos ^= $this->offset($unit);

        foreach (mb_str_split($key) as $c) {
            if (ord($c) === 0) {
                break;
            }
            $node_pos ^= ord($c);
            $unit = $this->array[$node_pos];
            if ($this->label($unit) !== mb_ord($c)) {
                return $results;
            }
            $node_pos ^= $this->offset($unit);
            if ($this->hasLeaf($unit)) {
                $results[] = $this->value($this->array[$node_pos]);
            }
        }
        return $results;
    }

    private function offset($unit): int
    {
        return $unit & ((1 << 22) - 1);
    }

    private function label($unit): int
    {
        return $unit >> 24;
    }

    private function hasLeaf($unit): int
    {
        return ($unit >> 23) & 1;
    }

    private function value($unit): int
    {
        return $unit & ((1 << 31) - 1);
    }
}

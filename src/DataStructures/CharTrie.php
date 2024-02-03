<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\DataStructures;

use Generator;

class CharTrie
{

    /** @var CharTrieNode The root node of the trie. */
    private CharTrieNode $root;

    /**
     * Creates a new CharTrie instance.
     */
    public function __construct()
    {
        $this->root = CharTrieNode::default(256);
    }

    /**
     * Adds one or more `texts` to the trie.
     * @param array $texts The strings to add to the trie.
     */
    public function extend(array $texts): void
    {
        foreach ($texts as $text) {
            $this->push($text);
        }
    }

    /**
     * Adds text to the trie.
     * @param string $text The string to add to the trie.
     */
    public function push(string $text): void
    {
        $node = $this->root;

        for ($i = 0, $length = strlen($text); $i < $length; $i++) {
            $ch = $text[$i];
            $node = $node->getChild($ch);
        }

        $node->isLeaf = true;
    }

    /**
     * Searches the trie for all strings with a common prefix of `text`.
     * @param string $text The common prefix to search for.
     * @return Generator Yields each string in the trie that has `text` as a prefix.
     */
    public function commonPrefixSearch(string $text): Generator
    {
        $node = $this->root;
        $prefix = "";
        for ($i = 0; $i < strlen($text) && $node != null; $i++) {
            $ch = $text[$i];
            $prefix .= $ch;
            $node = $node->getChild($ch);
            if ($node?->isLeaf) {
                yield $prefix;
            }
        }
    }
}
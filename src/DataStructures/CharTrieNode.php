<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\DataStructures;

class CharTrieNode
{

    /**
     * Create a new CharTrieNode.
     * @param bool $isLeaf Whether the node is a leaf node or not.
     * @param CharTrieNode[] $children A map containing the node's children, where the key is a character and the value is a `CharTrieNode`.
     */
    public function __construct(public bool $isLeaf, public array $children)
    {
    }

    /**
     * Returns a new `CharTrieNode` instance with default values.
     * @return CharTrieNode A new `CharTrieNode` instance with `isLeaf` set to `false` and an empty `children` map.
     */
    public static function default(): CharTrieNode
    {
        return new CharTrieNode(false, []);
    }
}
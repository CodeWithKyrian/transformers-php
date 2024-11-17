<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Tokenizers;

use SplDoublyLinkedList;
use SplPriorityQueue;

/**
 * BPE class for encoding text into Byte-Pair-Encoding (BPE) tokens.
 */
class BPEModel extends TokenizerModel
{
    /**
     * Mapping of BPE merges to their rank.
     */
    protected array $bpeRanks;

    /**
     * Array of BPE merges.
     */
    protected array $merges;

    /**
     * Suffix to insert between words (custom for BlenderbotSmallTokenizer).
     */
    protected ?string $continuingSubwordSuffix;

    /**
     * Flag indicating whether to use byte fallback for unknown tokens.
     */
    protected bool $byteFallback;

    /**
     * Cache of BPE encoded tokens.
     *
     */
    protected array $cache = [];


    protected const BPE_SPLIT_TOKEN = ' ';

    public function __construct(array $config)
    {
        parent::__construct($config);

        $vocab = $moreConfig['vocab'] ?? $this->config['vocab'];

        $this->tokenToIds = self::toMap($vocab);

        $this->unkTokenId = $this->tokenToIds[$config['unk_token']] ?? null;
        $this->unkToken = $config['unk_token'];

        $this->vocab = array_flip($vocab);

        $this->bpeRanks = array_flip($config['merges']);

        $this->merges = array_map(fn($merge) => explode(' ', $merge), $config['merges']);

        $this->endOfWordSuffix = $config['end_of_word_suffix'] ?? null;
        $this->continuingSubwordSuffix = $config['continuing_subword_suffix'] ?? null;

        $this->byteFallback = $config['byte_fallback'] ?? false;

    }

    /**
     * Apply Byte-Pair-Encoding (BPE) to a given token. Efficient heap-based priority
     *  queue implementation adapted from https://github.com/belladoreai/llama-tokenizer-js.
     *
     * @param string $token The token to encode.
     * @return string[] The encoded token.
     */
    protected function bpe(string $token): array
    {
        if (mb_strlen($token) === 0) {
            return [];
        }

        if (isset($this->cache[$token])) {
            return $this->cache[$token];
        }

        $word = preg_split('//u', $token, -1, PREG_SPLIT_NO_EMPTY);

        if ($this->endOfWordSuffix) {
            $word[count($word) - 1] .= $this->endOfWordSuffix;
        }

        $result = [];
        if (count($word) > 1) {
            $queue = new SplPriorityQueue();
            $queue->setExtractFlags(SplPriorityQueue::EXTR_DATA);

            // Construct a doubly-linked list of nodes that will be inserted into the priority queue,
            // starting with the individual characters. We also populate each node with a positional
            // bias to break ties in the priority queue.
            $startingNode = new BPENode($word[0], 0);
            $previousNode = $startingNode;

            for ($i = 1; $i < count($word); $i++) {
                $currentNode = new BPENode(
                    $word[$i],
                    $i / count($word),
                    $previousNode,
                );

                $previousNode->next = $currentNode;
                $this->addNodeToQueue($queue, $previousNode);
                $previousNode = $currentNode;
            }

            while (!$queue->isEmpty()) {
                /**
                 * Get the next node with the highest priority
                 * @var BPENode $node
                 */
                $node = $queue->extract();


                // Check that this merge is still possible
                if ($node->deleted || !$node->next || $node->next->deleted) {
                    continue;
                }

                $node->deleted = true;
                $node->next->deleted = true;

                // Next, we fix the node that comes before the current node (i.e., left side of the merge).
                if ($node->prev) {
                    // Make a shallow copy of the previous node
                    $newPrevNode = clone $node->prev;

                    // Mark the old previous node as deleted. This avoids erroneous merges later,
                    // because there may still be references to this node in the priority queue.
                    $node->prev->deleted = true;
                    $node->prev = $newPrevNode;

                    // Update the reference of the previous node, by pointing its previous node to this new previous node.
                    if ($newPrevNode->prev) {
                        $newPrevNode->prev->next = $newPrevNode;
                    } else {
                        // If the previous of the previous node does not exist, it means that
                        // `newPreviousNode` must be the new `startingNode`.
                        $startingNode = $newPrevNode;
                    }
                }

                // Create a new node which represents the result of the merge.
                $merged = new BPENode($node->token . $node->next->token, $node->bias, $node->prev, $node->next->next);

                // We now consider where we can add the new merged node to the priority queue:
                // 1. prev <-> merged
                if ($merged->prev) {
                    $merged->prev->next = $merged;
                    $this->addNodeToQueue($queue, $merged->prev);
                } else {
                    // If `merged.prev` does not exist, then `merged` must be the new `startingNode`.
                    $startingNode = $merged;
                }

                // 2. merged <-> next
                if ($merged->next) {
                    $merged->next->prev = $merged;
                    $this->addNodeToQueue($queue, $merged);
                }
            }

            // Finally, we construct the result by traversing the doubly-linked list of nodes.
            for ($node = $startingNode; $node != null; $node = $node->next) {
                $result[] = $node->token;
            }
        } else {
            $result = $word;
        }

        // Possibly append suffix to continuing subwords
        if ($this->continuingSubwordSuffix) {
            for ($i = 0; $i < count($result) - 1; ++$i) {
                $result[$i] .= $this->continuingSubwordSuffix;
            }
        }

        // Save the result to the cache
        $this->cache[$token] = $result;

        return $result;
    }


    public function addNodeToQueue(SplPriorityQueue $queue, BPENode $node): void
    {
        // `score` is a measure of the merge priority: lower means higher priority.
        // We use the BPE rank as a measure of priority (i.e., the local of the merge in the merges list)
        // We also add a fractional component to the score to break ties (with the earlier character having higher priority)
        $rank = $this->bpeRanks[$node->token . self::BPE_SPLIT_TOKEN . $node->next?->token] ?? null;

        if ($rank !== null) {
            $node->score = -($rank + $node->bias);
            $queue->insert($node, $node->score);
        }
    }

    /**
     * Encodes the input sequence of tokens using the BPE algorithm and returns the resulting subword tokens.
     * @param string[] $tokens The input tokens to encode.
     * @return string[] The resulting subword tokens after applying the BPE algorithm to the input sequence of tokens.
     */
    function encode(array $tokens): array
    {
        $outputTokens = [];

        foreach ($tokens as $token) {
            $bpeTokenList = $this->bpe($token);


            foreach ($bpeTokenList as $bpeToken) {
                if (array_key_exists($bpeToken, $this->tokenToIds)) {
                    $outputTokens[] = $bpeToken;
                }
                else {
                    if ($this->byteFallback) {
                        $bytes = unpack('C*', $bpeToken);

                        foreach ($bytes as $byte) {
                            $outputTokens[] = sprintf("<0x%02X>", $byte);
                        }
                    } else {
                        $outputTokens[] = $this->unkToken;
                    }
                }
            }
        }

        return $outputTokens;
    }
}

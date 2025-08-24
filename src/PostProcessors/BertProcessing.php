<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\PostProcessors;

/**
 * A post-processor that adds special tokens to the beginning and end of the input.
 */
class BertProcessing extends PostProcessor
{
    /**
     * @var string The special token to add to the beginning of the input.
     */
    protected string $cls;

    /**
     * @var string The special token to add to the end of the input.
     */
    protected string $sep;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->cls = $config['cls'][0];
        $this->sep = $config['sep'][0];
    }

    /**
     * Adds the special tokens to the beginning and end of the input.
     *
     * @param string[] $tokens The input tokens.
     * @param string[]|null $tokenPair The input tokens for the second sequence in a pair.
     * @param bool $addSpecialTokens Whether to add the special tokens associated with the corresponding model.
     * @return PostProcessedOutput
     */
    public function postProcess(array $tokens, ?array $tokenPair = null, bool $addSpecialTokens = true): PostProcessedOutput
    {
        if ($addSpecialTokens) {
            $tokens = array_merge([$this->cls], $tokens, [$this->sep]);
        }

        $tokenTypeIds = array_fill(0, count($tokens), 0);

        if ($tokenPair) {
            // NOTE: It is intended to add 2 EOS tokens after the first set of tokens
            // https://github.com/huggingface/tokenizers/issues/983

            $middle = $addSpecialTokens && $this instanceof RobertaProcessing ? [$this->sep] : [];
            $after = $addSpecialTokens ? [$this->sep] : [];

            $tokens = array_merge($tokens, $middle, $tokenPair, $after);
            $tokenTypeIds = array_merge($tokenTypeIds, array_fill(0, count($middle) + count($tokenPair) + count($after), 1));
        }

        return new PostProcessedOutput($tokens, $tokenTypeIds);
    }
}

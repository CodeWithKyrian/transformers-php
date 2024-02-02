<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Decoders;

/**
 * MetaspaceDecoder class extends the Decoder class and decodes Metaspace tokenization.
 */
class MetaspaceDecoder extends Decoder
{
    /**
     * Whether to add a prefix space to the decoded string.
     */
    protected bool $addPrefixSpace;

    /**
     * The string to replace spaces with.
     */
    protected string $replacement;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->addPrefixSpace = $config['add_prefix_space'] ?? false;
        $this->replacement = $config['replacement'] ?? '';
    }

    protected function decodeChain(array $tokens): array
    {
        $result = [];

        foreach ($tokens as $i => $token) {
            $normalized = str_replace($this->replacement, ' ', $token);

            if ($this->addPrefixSpace && $i == 0 && str_starts_with($normalized, ' ')) {
                $normalized = substr($normalized, 1);
            }

            $result[] = $normalized;
        }

        return $result;
    }
}
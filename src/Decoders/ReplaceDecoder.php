<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Decoders;

class ReplaceDecoder extends Decoder
{

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    protected function decodeChain(array $tokens): array
    {
        $pattern = $this->config['pattern'] ?? null;

        if ($pattern === null) {
            return $tokens;
        }

        $regex = $pattern['Regex'] ?? null;
        $string = $pattern['String'] ?? null;
        $replacement = $this->config['content'] ?? '';

        return array_map(function ($token) use ($regex, $string, $replacement) {
            if ($regex !== null) {
                return preg_replace("/{$regex}/u", $replacement, (string)$token);
            }
            if ($string !== null) {
                return str_replace($string, $replacement, (string)$token);
            }
            return $token;
        }, $tokens);
    }
}
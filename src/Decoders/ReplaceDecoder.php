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


        return $pattern == null ?
            $tokens :
            array_map(function ($token) use ($pattern) {
                if (isset($pattern['Regex'])) {
                    return preg_replace("/{$pattern['Regex']}/u", $this->config['content'], (string)$token);
                } elseif (isset($pattern['String'])) {
                    return str_replace($pattern['String'], $this->config['content'], (string)$token);
                } else {
                    return $token;
                }
            }, $tokens);
    }
}
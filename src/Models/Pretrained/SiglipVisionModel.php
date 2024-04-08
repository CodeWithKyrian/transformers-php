<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Utils\AutoConfig;
use Symfony\Component\Console\Output\OutputInterface;

class SiglipVisionModel extends CLIPPretrainedModel
{
    public static function fromPretrained(string $modelNameOrPath, bool $quantized = true, AutoConfig|array $config = null, ?string $cacheDir = null, ?string $token = null, string $revision = 'main', ?string $modelFilename = null, ModelArchitecture $modelArchitecture = ModelArchitecture::EncoderOnly, ?OutputInterface $output = null): PretrainedModel
    {
        // Update default model file name if not provided
        $modelFilename ??= 'vision_model';
        return parent::fromPretrained($modelNameOrPath, $quantized, $config, $cacheDir, $token, $revision, $modelFilename, $modelArchitecture, $output);
    }
}
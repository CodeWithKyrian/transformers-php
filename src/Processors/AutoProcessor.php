<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Processors;

use Codewithkyrian\Transformers\Exceptions\HubException;
use Codewithkyrian\Transformers\FeatureExtractors\ImageFeatureExtractor;
use Codewithkyrian\Transformers\Utils\Hub;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Helper class which is used to instantiate pretrained processors with the `fromPretrained` function.
 *  The chosen processor class is determined by the type specified in the processor config.
 *
 * *Example:** Load a processor using `fromPretrained`.
 * ```php
 *  $processor =  AutoProcessor.fromPretrained('openai/whisper-tiny.en');
 *  ```
 *
 * *Example:** Run an image through a processor.
 *
 * ```php
 *  $processor = AutoProcessor::fromPretrained('Xenova/vit-base-patch16-224');
 *  $image = Image::read('images/a-stored-image.jpeg');
 *  $imageInputs = $processor($image);
 * ```
 */
class AutoProcessor
{

    /**
     * Instantiate one of the processor classes of the library from a pretrained model.
     *
     * The processor class to instantiate is selected based on the `feature_extractor_type` property of the config object
     *  (either passed as an argument or loaded from `$modelNameOrPath` if possible)
     *
     * @param string $modelNameOrPath The name or path of the pretrained model. Can be either:
     *   - A string, the *model id* of a pretrained tokenizer hosted inside a model repo on huggingface.co.
     *     Valid model ids can be located at the root-level, like `bert-base-uncased`, or namespaced under a
     *     user or organization name, like `dbmdz/bert-base-german-cased`.
     *   - A path to a *directory* containing tokenizer files, e.g., `./my_model_directory/`.
     * @param array|null $config
     * @param string|null $cacheDir
     * @param string $revision
     * @param callable|null $onProgress
     * @return Processor
     * @throws HubException
     */
    public static function fromPretrained(
        string           $modelNameOrPath,
        ?array           $config = null,
        ?string          $cacheDir = null,
        string           $revision = 'main',
        ?callable $onProgress = null
    ): Processor
    {
        $preprocessorConfig = $config ?? Hub::getJson($modelNameOrPath, 'preprocessor_config.json', $cacheDir, $revision, onProgress: $onProgress);

        $featureExtractorKey = $preprocessorConfig['feature_extractor_type'] ?? $preprocessorConfig['image_processor_type'];

        $featureExtractorClass = "\\Codewithkyrian\\Transformers\\FeatureExtractors\\{$featureExtractorKey}";


        if(!class_exists($featureExtractorClass))
        {
            if(isset($preprocessorConfig['size']))
            {
//                $output?->writeln("Feature extractor type `{$featureExtractorKey}` not found, assuming ImageFeatureExtractor due to size parameter in config.");

                // Assume ImageFeatureExtractor
                $featureExtractorClass = ImageFeatureExtractor::class;
            }
            else{
                throw new \Exception("Unknown Feature Extractor type: {$featureExtractorKey}");
            }
        }

        $processorKey = $preprocessorConfig['processor_class'] ?? "Processor";
        $processorClass = "\\Codewithkyrian\\Transformers\\Processors\\$processorKey";


        $featureExtractor = new $featureExtractorClass($preprocessorConfig);

        return new $processorClass($featureExtractor);
    }
}
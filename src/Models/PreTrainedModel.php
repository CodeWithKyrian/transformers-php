<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models;

use Codewithkyrian\Transformers\LogitsProcessors\BadWordsLogitsProcessor;
use Codewithkyrian\Transformers\LogitsProcessors\ForcedBOSTokenLogitsProcessor;
use Codewithkyrian\Transformers\LogitsProcessors\ForcedEOSTokenLogitsProcessor;
use Codewithkyrian\Transformers\LogitsProcessors\ForceTokensLogitsProcessor;
use Codewithkyrian\Transformers\LogitsProcessors\LogitsProcessorList;
use Codewithkyrian\Transformers\LogitsProcessors\MinLengthLogitsProcessor;
use Codewithkyrian\Transformers\LogitsProcessors\MinNewTokensLengthLogitsProcessor;
use Codewithkyrian\Transformers\LogitsProcessors\NoRepeatNGramLogitsProcessor;
use Codewithkyrian\Transformers\LogitsProcessors\RepetitionPenaltyLogitsProcessor;
use Codewithkyrian\Transformers\LogitsProcessors\SuppressTokensAtBeginLogitsProcessor;
use Codewithkyrian\Transformers\Samplers\Sampler;
use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\Hub;
use Codewithkyrian\Transformers\Utils\Tensor;
use OnnxRuntime\InferenceSession;

/**
 * A base class for pre-trained models that provides the model configuration and an ONNX session.
 */
class PreTrainedModel
{
    public string $mainInputName = 'input_ids';

    protected static ModelType $modelType = ModelType::EncoderOnly;


    /**
     * @param array $config The model configuration.
     * @param mixed $session The ONNX session.
     */
    public function __construct(
        public readonly AutoConfig $config,
        public InferenceSession    $session,
                                   ...$args
    )
    {
    }


    /**
     * Instantiate one of the model classes of the library from a pretrained model.
     *
     * The model class to instantiate is selected based on the `model_type` property of the config object
     *  (either passed as an argument or loaded from `pretrained_model_name_or_path` if possible)
     *
     * @param string $modelNameOrPath The name or path of the pretrained model. Can be either:
     *  - A string, the *model id* of a pretrained model hosted inside a model repo on huggingface.co.
     *    Valid model ids can be located at the root-level, like `bert-base-uncased`, or namespaced under a
     *    user or organization name, like `dbmdz/bert-base-german-cased`.
     *  - A path to a *directory* containing model weights, e.g., `./my_model_directory/`.
     * @param bool $quantized Whether to load the quantized version of a model (as opposed to the original one).
     * @param array|null $config The configuration object used to instantiate the model.
     * @param string|null $cacheDir Path to a directory in which a downloaded pretrained model configuration should
     * @param string|null $token The token to use as an authorization to download from private model repos.
     * @param string $revision The specific model version to use. It can be a branch name, a tag name,
     * @param string|null $modelFilename The name of the model file to load. If not provided, will default to the
     *
     * @return self The model instantiated from the configuration.
     * @throws \Exception
     */
    public static function fromPretrained(
        string           $modelNameOrPath,
        bool             $quantized = true,
        array|AutoConfig $config = null,
        ?string          $cacheDir = null,
        ?string          $token = null,
        string           $revision = 'main',
        ?string          $modelFilename = null,
    ): self
    {
        $className = get_called_class();

        if (!$config instanceof AutoConfig) {
            $config = AutoConfig::fromPretrained($modelNameOrPath, $config, $cacheDir, $revision);
        }

        $modelType = $className::$modelType;

        switch ($modelType) {
            case ModelType::DecoderOnly:
            {
                $session = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: $modelFilename ?? 'decoder_model_merged', cacheDir: $cacheDir, revision: $revision);

                $generatorConfig = Hub::getJson(pathOrRepoID: $modelNameOrPath, fileName: 'generator_config.json',
                    cacheDir: $cacheDir, revision: $revision, fatal: false);

                return new static($config, $session, $generatorConfig);
            }

            case ModelType::Seq2Seq:
            case ModelType::Vision2Seq:
            {
                $encoderSession = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'encoder_model', cacheDir: $cacheDir, revision: $revision);

                $decoderSession = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'decoder_model_merged', cacheDir: $cacheDir, revision: $revision);

                $generatorConfigArr = Hub::getJson(pathOrRepoID: $modelNameOrPath, fileName: 'generation_config.json',
                    cacheDir: $cacheDir, revision: $revision, fatal: false);

                $generatorConfig = new GenerationConfig($generatorConfigArr);

                return new static($config, $encoderSession, $decoderSession, $generatorConfig);
            }

            case ModelType::MaskGeneration:
            {
                $visionEncoder = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'vision_encoder', cacheDir: $cacheDir, revision: $revision);

                $promptMaskEncoder = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'prompt_encoder_mask_decoder', cacheDir: $cacheDir, revision: $revision);

                return new static($config, $visionEncoder, $promptMaskEncoder);
            }

            case ModelType::EncoderDecoder:
            {
                $encoderSession = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'encoder_model', cacheDir: $cacheDir, revision: $revision);

                $decoderSession = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'decoder_model_merged', cacheDir: $cacheDir, revision: $revision);

                return new static($config, $encoderSession, $decoderSession);
            }

            default:
            {
                if ($modelType != ModelType::EncoderOnly) {
                    echo "WARNING: {$modelType->value} is not a valid model type. Defaulting to EncoderOnly.";
                }

                $session = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'model', cacheDir: $cacheDir, revision: $revision);

                return new static($config, $session);
            }
        }
    }

    public function __invoke(array $modelInputs): array
    {
        return $this->forward($modelInputs);
    }

    /**
     * Forward method for a pretrained model. If not overridden by a subclass, the correct forward method
     *  will be chosen based on the model type.
     * @param array $modelInputs The input data to the model in the format specified in the ONNX model.
     * @return array{logits: Tensor, hidden_states: Tensor, attentions: Tensor} The output data from the model in the format specified in the ONNX model.
     */
    public function forward(array $modelInputs): array
    {
        return $this::$modelType->forward($this, $modelInputs);
    }

    /**
     *  Initializes and returns the beam for text generation task
     *
     * @param Tensor $inputTokenIds The input token ids.
     * @param GenerationConfig $generationConfig The generation config.
     * @param int $numOutputTokens The number of tokens to generate.
     * @param Tensor|null $inputsAttentionMask The attention mask for the input token ids.
     * @return array The initial beam for text generation.
     *
     */
    public function getStartBeams(
        Tensor           $inputTokenIds,
        GenerationConfig $generationConfig,
        int              $numOutputTokens,
        Tensor           $inputsAttentionMask = null
    ): array
    {
        return $this::$modelType->startBeams(
            $this,
            $inputTokenIds,
            $generationConfig,
            $numOutputTokens,
            $inputsAttentionMask
        );
    }

    /**
     *  Runs the beam for text generation task
     *
     * @param array $beam The current beam being generated.
     * @return array The updated beam after a single generation step.
     *
     */
    public function runBeam(array &$beam): array
    {
        return $this::$modelType->runBeam($this, $beam);
    }

    /**
     *  Update a beam with a new token ID.
     *
     * @param array $beam The beam to update.
     * @param int $newTokenId The new token id to add to the beam.
     * @return array The updated beam after adding the new token.
     *
     */
    public function updateBeam(array $beam, int $newTokenId): array
    {
        return $this::$modelType->updateBeam($this, $beam, $newTokenId);
    }

    /**
     * Constructs an InferenceSession using a model file located at the specified path.
     *
     * @param string $modelNameOrPath The path to the directory containing the model file.
     * @param string $fileName The name of the model file.
     * @param string|null $cacheDir Path to a directory in which a downloaded pretrained model should
     * @param string|null $token The token to use as an authorization to download from private model repos.
     * @param string $revision The specific model version to use. It can be a branch name, a tag name,
     * @param string $subFolder In case the relevant files are located inside a subfolder of the model repo or
     * directory, indicate it here.
     * @param bool $fatal Whether to raise an error if the file could not be loaded.
     * @return InferenceSession|null
     */

    public static function constructSession(
        string  $modelNameOrPath,
        string  $fileName,
        bool    $quantized = true,
        ?string $cacheDir = null,
        ?string $token = null,
        string  $revision = 'main',
        string  $subFolder = '',
        bool    $fatal = true,
                ...$sessionOptions
    ): ?InferenceSession
    {
        $modelFileName = sprintf('onnx/%s%s.onnx', $fileName, $quantized ? '_quantized' : '');

        $file = Hub::getFile($modelNameOrPath, $modelFileName, $cacheDir, $token, $revision, $subFolder, $fatal);

        if ($file === null) {
            if ($fatal) {
                throw new \Exception("Unable to load file $fileName from $modelNameOrPath");
            }
            return null;
        }

        return new InferenceSession($file, ...$sessionOptions);
    }

    /**
     * @param InferenceSession $session
     * @param Tensor[] $inputs
     * @return Tensor[]
     */
    public function validateInputs(InferenceSession $session, array $inputs): array
    {
        $checkedInputs = [];
        $missingInputs = [];

        foreach ($session->inputs as ['name' => $inputName]) {
            $tensor = $inputs[$inputName];

            // Check if the input is an instance of Tensor
            if (!($tensor instanceof Tensor)) {
                $missingInputs[] = $inputName;
                continue;
            }

            $checkedInputs[$inputName] = $tensor;
        }


        if (!empty($missingInputs)) {
            throw new \Exception('An error occurred during model execution: "Missing the following inputs:
             ' . implode(', ', $missingInputs) . '".');
        }

        $numInputsProvided = count($inputs);
        $numInputsNeeded = count($session->inputs);


        if ($numInputsProvided > $numInputsNeeded) {
            // No missing inputs, but too many inputs were provided.
            // Warn the user and ignore the extra inputs.
            $ignored = array_diff(array_keys($inputs), $session->inputs);
            echo 'WARNING: Too many inputs were provided (' . $numInputsProvided . ' > ' . $numInputsNeeded . '). 
            The following inputs will be ignored: "' . implode(', ', $ignored) . '".';
        }

        return array_map(fn($i) => $i->toArray(), $inputs);
    }

    public function runSession(InferenceSession $session, array $inputs): array
    {
        $inputs = $this->validateInputs($session, $inputs);

        try {
            $outputNames = array_map(fn($o) => $o['name'], $session->outputs());

            $outputs = $session->run($outputNames, $inputs);

            $outputsAssoc = [];
            for ($i = 0; $i < count($outputNames); ++$i) {
                $outputsAssoc[$outputNames[$i]] = Tensor::fromArray($outputs[$i]);
            }

            return $outputsAssoc;
        } catch (\Exception $e) {
            throw new \Exception('An error occurred during model execution: "' . $e->getMessage() . '".');
        }
    }

    /**
     * This function merges multiple generation configs together to form a final generation config to be used by the model for text generation.
     * It first creates an empty `GenerationConfig` object, then it applies the model's own `generation_config` property to it. Finally, if a `generation_config` object was passed in the arguments, it overwrites the corresponding properties in the final config with those of the passed config object.
     * @param ?GenerationConfig $generationConfig A `GenerationConfig` object containing generation parameters.
     * @return GenerationConfig The final generation config object to be used by the model for text generation.
     */
    protected function getGenerationConfig(?GenerationConfig $generationConfig): GenerationConfig
    {
        // Create empty generation config (contains defaults)
        // We pass `$this->config` so that if `eos_token_id` or `bos_token_id` exist in the model's config, we will use them
        $genConfig = new GenerationConfig($this->config->config);


        $genConfigArray = $genConfig->toArray();

        // Apply model's generation config, if it exists
        if (property_exists($this, 'generationConfig')) {
            $genConfigArray = array_merge($genConfigArray, $this->generationConfig->toArray());
        }


        // Finally, use any generation config specified by the user
        // when calling `generate`
        if ($generationConfig !== null) {
            $genConfigArray = array_merge($genConfigArray, $generationConfig->toArray());
        }


        return new GenerationConfig($genConfigArray);
    }

    protected function getLogitsProcessor(
        GenerationConfig     $generationConfig,
        int                  $inputIdsSeqLength,
        ?LogitsProcessorList $logitsProcessor = null
    ): LogitsProcessorList
    {
        $processors = new LogitsProcessorList();

        if ($generationConfig->repetition_penalty != null && $generationConfig->repetition_penalty !== 1.0) {
            $processors->push(new RepetitionPenaltyLogitsProcessor($generationConfig->repetition_penalty));
        }

        if ($generationConfig->no_repeat_ngram_size != null && $generationConfig->no_repeat_ngram_size > 0) {
            $processors->push(new NoRepeatNGramLogitsProcessor($generationConfig->no_repeat_ngram_size));
        }

        if ($generationConfig->bad_words_ids != null) {
            $processors->push(new BadWordsLogitsProcessor($generationConfig->bad_words_ids, $inputIdsSeqLength));
        }

        if ($generationConfig->min_length != null && $generationConfig->eos_token_id != null && $generationConfig->min_length > 0) {
            $processors->push(new MinLengthLogitsProcessor($generationConfig->min_length, $generationConfig->eos_token_id));
        }

        if ($generationConfig->min_new_tokens != null && $generationConfig->eos_token_id != null && $generationConfig->min_new_tokens > 0) {
            $processors->push(new MinNewTokensLengthLogitsProcessor(
                    $inputIdsSeqLength,
                    $generationConfig->min_new_tokens,
                    $generationConfig->eos_token_id)
            );
        }

        if ($generationConfig->forced_bos_token_id !== null) {
            $processors->push(new ForcedBOSTokenLogitsProcessor($generationConfig->forced_bos_token_id));
        }

        if ($generationConfig->forced_eos_token_id !== null) {
            $processors->push(new ForcedEOSTokenLogitsProcessor($generationConfig->forced_eos_token_id));
        }

        if ($generationConfig->begin_suppress_tokens !== null) {
            $beginIndex = ($inputIdsSeqLength > 1 || $generationConfig->forced_bos_token_id == null)
                ? $inputIdsSeqLength
                : $inputIdsSeqLength + 1;

            if ($generationConfig->forced_decoder_ids != null) {
                $beginIndex += $generationConfig->forced_decoder_ids[array_key_last($generationConfig->forced_decoder_ids)][0];
            }

            $processors->push(new SuppressTokensAtBeginLogitsProcessor($generationConfig->begin_suppress_tokens, $beginIndex));
        }

        if ($generationConfig->forced_decoder_ids !== null) {
            $processors->push(new ForceTokensLogitsProcessor($generationConfig->forced_decoder_ids));
        }

        if ($logitsProcessor !== null) {
            $processors->extend($logitsProcessor);
        }

//         `LogitNormalization` should always be the last logit processor, when present
//        if($generationConfig->renormalize_logits) {
//            $processors->push(new LogitNormalization());
//        }

        return $processors;

    }


    /**
     * Prepares an attention mask for a sequence of tokens based on configuration options.
     * @param Tensor $tokens The input tokens.
     * @return Tensor The attention mask tensor.
     * @private
     */
    public function prepareAttentionMask(Tensor $tokens): Tensor
    {

        // Prepare attention mask
        $padTokenId = $this->config['pad_token_id'] ?? null;
        $eosTokenId = $this->config['eos_token_id'] ?? null;

        if (is_int($eosTokenId)) {
            $eosTokenId = [$eosTokenId];
        }

        $isPadTokenInInputs = in_array($padTokenId, $tokens->toArray());
        $isPadTokenNotEqualToEosTokenId = ($eosTokenId === null) || !in_array($padTokenId, $eosTokenId);

        if ($isPadTokenInInputs && $isPadTokenNotEqualToEosTokenId) {
            $data = array_map(fn($x) => $x != $padTokenId, $tokens->buffer()->toArray());
            return new Tensor($data, $tokens->dtype(), $tokens->shape());
        } else {
            return Tensor::onesLike($tokens);
        }
    }


    public function generate(
        Tensor               $inputs,
        ?GenerationConfig    $generationConfig = null,
        ?LogitsProcessorList $logitsProcessor = null,
        array                $inputsAttentionMask = null
    ): array
    {
        if (!$this::$modelType->canGenerate()) {
            $className = get_called_class();
            $errorMsg = "The current model class {$className} is not is not compatible with \`.generate()\`, as it doesn't have a language model head.";

            $modelType = $this->config->modelType;
            $possibleInfo =
                ModelGroup::CausalLM->models()[$modelType->value]
                ?? ModelGroup::Seq2SeqLM->models()[$modelType->value]
                ?? ModelGroup::SpeechSeq2Seq->models()[$modelType->value]
                ?? ModelGroup::TextToSpectrogram->models()[$modelType->value]
//                ?? ModelGroup::VisionToSeq->models()[$modelType->value]
                ?? null;

            if ($possibleInfo) {
                $errorMsg .= " Try using `{$possibleInfo}` instead.";
            }

            throw new \Error($errorMsg);

        }

        $inputIdsSeqLength = 0;

        // Prepare `input_ids` which will be used for auto-regressive generation
        // TODO: Update to align with HF transformers' implementation
        if (!$this->config->isEncoderDecoder) {
            $inputIdsSeqLength = $inputs->shape()[count($inputs->shape()) - 1];

            // decoder-only
            if ($inputIdsSeqLength === 0) {
                throw new \Error("Must supply a non-empty Tensor of input token ids.");
            }
        }

        // Update generation config with defaults
        $generationConfig = $this->getGenerationConfig($generationConfig);

        $logitsProcessor ??= new LogitsProcessorList();

        // Update logits processor
        $logitsProcessor = $this->getLogitsProcessor($generationConfig, $inputIdsSeqLength, $logitsProcessor);

        $eos_token_ids = $generationConfig->eos_token_id;

        if ($eos_token_ids !== null && !is_array($eos_token_ids)) {
            $eos_token_ids = [$eos_token_ids];
        }

        // TODO implement early_stopping
        // https://huggingface.co/blog/how-to-generate

        $numOutputTokens = 1;
        $maxOutputTokens = $numOutputTokens + ($generationConfig->max_new_tokens ?? INF);

        // Only use max length if max_new_tokens is not provided
        $useMaxLength = is_int($generationConfig->max_length) && is_null($generationConfig->max_new_tokens);

        $sampler = Sampler::getSampler($generationConfig);


        $beams = $this->getStartBeams($inputs, $generationConfig, $numOutputTokens, $inputsAttentionMask);

        while (array_reduce($beams, fn($carry, $beam) => $carry || !$beam['done'], false) && $numOutputTokens < $maxOutputTokens) {
            $newest_beams = [];
            foreach ($beams as $beam) {
                if ($beam['done']) {
                    // Add this beam back into the pool
                    $newest_beams[] = $beam;
                    continue;
                }
                if ($useMaxLength && count($beam['output_token_ids']) >= $generationConfig->max_length) {
                    // Set this beam to done and add it back into the pool
                    $beam['done'] = true;
                    $newest_beams[] = $beam;
                    continue;
                }

                $output = $this->runBeam($beam);

                // add attentions/scores to beam only if user requested
                if ($generationConfig->output_attentions) {
                    $this->addAttentionsToBeam($beam, $output);
                }

                if ($generationConfig->output_scores) {
                    // TODO add
                }

                // Logits are of the form [batch_size, out_seq_length, vocab_size]
                // In most cases, this will be [batch_size, 1, vocab_size]
                // So, we select the last token's logits:
                // (equivalent to `logits = outputs.logits[:, -1, :]`)
                $logits = array_slice($output['logits'], 0, -1);

                // Apply logits processor
                $logitsProcessor($beam['output_token_ids'], $logits);

                $sampledTokens = $sampler($logits);
                foreach ($sampledTokens as [$newTokenId, $logProb]) {
                    // use previous beam as a starting point
                    $newBeam = $beam;

                    // update new beam
                    $this->updateBeam($newBeam, $newTokenId);

                    $newBeam['score'] += $logProb;

                    if ($eos_token_ids && in_array($newTokenId, $eos_token_ids, true)) {
                        $newBeam['done'] = true;
                    }

                    $newest_beams[] = $newBeam;
                }
            }
            ++$numOutputTokens;

            // Next, we get the best beams, per ID
            $groupedBeams = $this->groupBeams($newestBeams);

            // Sort and slice within each group
            $newestBeams = array_map(function ($group) use ($generationConfig) {
                usort($group, function ($a, $b) {
                    return $b->score - $a->score; // Sort descending by score
                });

                return array_slice($group, 0, $generationConfig->num_beams); // Keep top beams
            }, $groupedBeams);

            // Flatten beams
            $beams = array_merge(...$newest_beams);

            // Run callback
//            if ($generationConfig['callback_function']) {
//                $generation_config['callback_function']($beams);
//            }
        }

        // TODO: Ensure that we can return non-batched outputs

        $groupedBeams = $this->groupBeams($beams);

        $getFlattened = function ($key) use ($groupedBeams, $generationConfig) {
            return array_map(function ($batch) use ($generationConfig, $key) {
                if ($generationConfig->num_return_sequences > 1) {
                    return array_map(fn($x) => $x[$key], array_slice($batch, 0, $generationConfig->num_return_sequences));
                } else {
                    return [$batch[0][$key]];
                }
            }, $groupedBeams);
        };

        $sequences = $getFlattened('output_token_ids'); // [1, seqLength]

        if ($generationConfig->return_dict_in_generate) {
            // NOTE: `decoder_attentions` and `cross_attentions` should be:
            //    list (one element for each generated token)
            //    of list (one element for each layer of the decoder)
            //    of torch.FloatTensor of shape (batch_size, num_heads, generated_length, sequence_length)
            // However, since we are only generating one batch at a time, they are of the form:
            //   list (batches)
            //   of list (one element for each generated token)
            //   of list (one element for each layer of the decoder)
            //   of torch.FloatTensor of shape (1, num_heads, generated_length, sequence_length)
            //
            // TODO: In future (when true parallelism, we should be able to return the correct shape)

            $decoderAttentions = $getFlattened('decoder_attentions');
            $crossAttentions = $getFlattened('cross_attentions');

            return [
                'sequences' => $sequences,
                'decoder_attentions' => $decoderAttentions,
                'cross_attentions' => $crossAttentions,
            ];
        } else {
            return $sequences;
        }
    }


}
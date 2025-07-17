<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Configs\PretrainedConfig;
use Codewithkyrian\Transformers\Configs\AutoConfig;
use Codewithkyrian\Transformers\Configs\GenerationConfig;
use Codewithkyrian\Transformers\Exceptions\HubException;
use Codewithkyrian\Transformers\Exceptions\MissingModelInputException;
use Codewithkyrian\Transformers\Exceptions\ModelExecutionException;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\NoBadWordsLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\ForcedBOSTokenLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\ForcedEOSTokenLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\LogitsProcessorList;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\MinLengthLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\MinNewTokensLengthLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\NoRepeatNGramLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\RepetitionPenaltyLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\SuppressTokensAtBeginLogitsProcessor;
use Codewithkyrian\Transformers\Generation\Samplers\Sampler;
use Codewithkyrian\Transformers\Generation\StoppingCriteria\EosTokenCriteria;
use Codewithkyrian\Transformers\Generation\StoppingCriteria\MaxLengthCriteria;
use Codewithkyrian\Transformers\Generation\StoppingCriteria\MaxTimeCriteria;
use Codewithkyrian\Transformers\Generation\StoppingCriteria\StoppingCriteria;
use Codewithkyrian\Transformers\Generation\StoppingCriteria\StoppingCriteriaList;
use Codewithkyrian\Transformers\Generation\Streamers\Streamer;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForCausalLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForSeq2SeqLM;
use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Models\Output\ModelOutput;
use Codewithkyrian\Transformers\Tensor\Tensor;
use Codewithkyrian\Transformers\Utils\Hub;
use Codewithkyrian\Transformers\Utils\InferenceSession;
use Error;
use Exception;

use function Codewithkyrian\Transformers\Utils\array_every;
use function Codewithkyrian\Transformers\Utils\array_keys_to_snake_case;
use function Codewithkyrian\Transformers\Utils\array_pick;
use function Codewithkyrian\Transformers\Utils\array_pop_key;

/**
 * A base class for pre-trained models that provides the model configuration and an ONNX session.
 */
class PretrainedModel
{
    public string $mainInputName = 'input_ids';

    protected array $forwardParams = ['input_ids', 'attention_mask'];

    /**
     * @param PretrainedConfig $config The model configuration.
     * @param InferenceSession $session The ONNX session.
     * @param ModelArchitecture $modelArchitecture
     * @param mixed ...$args
     */
    public function __construct(
        public PretrainedConfig  $config,
        public InferenceSession  $session,
        public ModelArchitecture $modelArchitecture = ModelArchitecture::EncoderOnly,
        ...$args
    ) {
        if ($this->modelArchitecture->canGenerate()) {
            $this->forwardParams[] = 'past_key_values';
        }
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
     * @param array|PretrainedConfig|null $config The configuration object used to instantiate the model.
     * @param string|null $cacheDir Path to a directory in which a downloaded pretrained model configuration should
     * @param string|null $token The token to use as an authorization to download from private model repos.
     * @param string $revision The specific model version to use. It can be a branch name, a tag name,
     * @param string|null $modelFilename The name of the model file to load. If not provided, will default to the
     * @param ModelArchitecture $modelArchitecture
     * @param callable|null $onProgress
     *
     * @return self The model instantiated from the configuration.
     * @throws HubException
     */
    public static function fromPretrained(
        string                      $modelNameOrPath,
        bool                        $quantized = true,
        array|PretrainedConfig|null $config = null,
        ?string                     $cacheDir = null,
        ?string                     $token = null,
        string                      $revision = 'main',
        ?string                     $modelFilename = null,
        ModelArchitecture           $modelArchitecture = ModelArchitecture::EncoderOnly,
        ?callable                   $onProgress = null
    ): self {
        if (is_array($config)) {
            $config = AutoConfig::fromPretrained($modelNameOrPath, $config, $cacheDir, $revision, $onProgress);
        }

        $quantizedSuffix = $quantized ? '_quantized' : '';

        switch ($modelArchitecture) {
            case ModelArchitecture::DecoderOnly: {
                    $session = self::constructSession(
                        modelNameOrPath: $modelNameOrPath,
                        fileName: $modelFilename ?? "decoder_model_merged$quantizedSuffix",
                        cacheDir: $cacheDir,
                        revision: $revision,
                        onProgress: $onProgress,
                    );

                    $generatorConfigArr = Hub::getJson(
                        pathOrRepoID: $modelNameOrPath,
                        fileName: 'generation_config.json',
                        cacheDir: $cacheDir,
                        revision: $revision,
                        fatal: false,
                        onProgress: $onProgress
                    );

                    $generatorConfig = new GenerationConfig($generatorConfigArr);

                    return new static(
                        config: $config,
                        session: $session,
                        modelArchitecture: $modelArchitecture,
                        generationConfig: $generatorConfig
                    );
                }

            case ModelArchitecture::Seq2SeqLM:
            case ModelArchitecture::Vision2Seq: {
                    $encoderSession = self::constructSession(
                        modelNameOrPath: $modelNameOrPath,
                        fileName: "encoder_model$quantizedSuffix",
                        cacheDir: $cacheDir,
                        revision: $revision,
                        onProgress: $onProgress,
                    );

                    $decoderSession = self::constructSession(
                        modelNameOrPath: $modelNameOrPath,
                        fileName: "decoder_model_merged$quantizedSuffix",
                        cacheDir: $cacheDir,
                        revision: $revision,
                        onProgress: $onProgress,
                    );

                    $generatorConfigArr = Hub::getJson(
                        pathOrRepoID: $modelNameOrPath,
                        fileName: 'generation_config.json',
                        cacheDir: $cacheDir,
                        revision: $revision,
                        fatal: false,
                        onProgress: $onProgress
                    );

                    $generatorConfig = new GenerationConfig($generatorConfigArr);

                    return new static(
                        config: $config,
                        session: $encoderSession,
                        modelArchitecture: $modelArchitecture,
                        generationConfig: $generatorConfig,
                        decoderMergedSession: $decoderSession
                    );
                }

            case ModelArchitecture::MaskGeneration: {
                    $visionEncoder = self::constructSession(
                        modelNameOrPath: $modelNameOrPath,
                        fileName: "vision_encoder$quantizedSuffix",
                        cacheDir: $cacheDir,
                        revision: $revision,
                        onProgress: $onProgress
                    );

                    $promptMaskEncoder = self::constructSession(
                        modelNameOrPath: $modelNameOrPath,
                        fileName: "prompt_encoder_mask_decoder$quantizedSuffix",
                        cacheDir: $cacheDir,
                        revision: $revision,
                        onProgress: $onProgress
                    );

                    return new static(
                        config: $config,
                        session: $visionEncoder,
                        promptMaskEncoderSession: $promptMaskEncoder,
                        modelArchitecture: $modelArchitecture
                    );
                }

            case ModelArchitecture::EncoderDecoder: {
                    $encoderSession = self::constructSession(
                        modelNameOrPath: $modelNameOrPath,
                        fileName: "encoder_model$quantizedSuffix",
                        cacheDir: $cacheDir,
                        revision: $revision,
                        onProgress: $onProgress
                    );

                    $decoderSession = self::constructSession(
                        modelNameOrPath: $modelNameOrPath,
                        fileName: "decoder_model_merged$quantizedSuffix",
                        cacheDir: $cacheDir,
                        revision: $revision,
                        onProgress: $onProgress
                    );

                    return new static(
                        config: $config,
                        session: $encoderSession,
                        decoderMergedSession: $decoderSession,
                        modelArchitecture: $modelArchitecture
                    );
                }

            default: {
                    if ($modelArchitecture != ModelArchitecture::EncoderOnly) {
                        echo "WARNING: {$modelArchitecture->value} is not a valid model group. Defaulting to EncoderOnly.";
                    }


                    $session = self::constructSession(
                        modelNameOrPath: $modelNameOrPath,
                        fileName: $modelFilename ?? "model$quantizedSuffix",
                        cacheDir: $cacheDir,
                        revision: $revision,
                        onProgress: $onProgress
                    );


                    return new static(
                        config: $config,
                        session: $session,
                        modelArchitecture: $modelArchitecture
                    );
                }
        }
    }

    /**
     * Constructs an InferenceSession using a model file located at the specified path.
     *
     * @param string $modelNameOrPath The path to the directory containing the model file.
     * @param string $fileName The name of the model file.
     * @param string|null $cacheDir Path to a directory in which a downloaded pretrained model should
     * @param string $revision The specific model version to use. It can be a branch name, a tag name,
     * @param string $subFolder In case the relevant files are located inside a subfolder of the model repo or
     * directory, indicate it here.
     * @param bool $fatal Whether to raise an error if the file could not be loaded.
     * @param callable|null $onProgress
     * @param mixed ...$sessionOptions
     *
     * @return InferenceSession|null
     * @throws HubException
     */
    public static function constructSession(
        string    $modelNameOrPath,
        string    $fileName,
        ?string   $cacheDir = null,
        string    $revision = 'main',
        string    $subFolder = 'onnx',
        bool      $fatal = true,
        ?callable $onProgress = null,
        ...$sessionOptions
    ): ?InferenceSession {
        $modelFileName = "$fileName.onnx";

        $file = Hub::getFile($modelNameOrPath, $modelFileName, $cacheDir, $revision, $subFolder, $fatal, $onProgress);

        if ($file === null) return null;

        return new InferenceSession($file, ...$sessionOptions);
    }

    /**
     * Makes the model callable. Alias for the forward method.
     *
     * @param array $modelInputs
     * @return array|ModelOutput
     */
    public function __invoke(array $modelInputs): array|ModelOutput
    {
        return $this->forward($modelInputs);
    }

    /**
     * Forward method for a pretrained model. Delegates to the appropriate forward 
     * method based on model architecture.
     *
     * @param array $modelInputs The input data to the model in the format specified in the ONNX model.
     *
     * @return array{logits: Tensor, hidden_states: Tensor, attentions: Tensor} The output data from the model in the format specified in the ONNX model.
     */
    public function forward(array $modelInputs): array
    {
        return $this->modelArchitecture->forward($this, $modelInputs);
    }

    /** Generates text based on the given inputs and generation configuration using the model.
     *
     * @param Tensor $inputs The input token ids.
     * @param GenerationConfig|null $generationConfig The generation configuration to use. If null, default configuration will be used.
     * @param LogitsProcessorList|null $logitsProcessor An optional logits processor to use. If null, a new LogitsProcessorList instance will be created.
     * @param Streamer|null $streamer
     * @param mixed ...$kwargs
     *
     * @return array|Tensor An array of generated output sequences, where each sequence is an array of token IDs.
     * @throws Exception
     */
    public function generate(
        Tensor               $inputs,
        ?GenerationConfig    $generationConfig = null,
        ?LogitsProcessorList $logitsProcessor = null,
        ?StoppingCriteria    $stoppingCriteria = null,
        ?Streamer            $streamer = null,
        ...$kwargs
    ): array|Tensor {
        $this->validateModelClass();

        $kwargs = array_keys_to_snake_case($kwargs);

        // 1. Prepare generation config
        $generationConfig = $this->prepareGenerationConfig($generationConfig);

        // 2. Prepare encoder inputs
        [$modelInputs, $modelInputName] = $this->prepareEncoderInputs($inputs, $kwargs);

        $isEncoderDecoder = $this->config->isEncoderDecoder;

        // 3. Run encoder forward pass if model is encoder decoder
        if ($isEncoderDecoder && !isset($modelInputs['encoder_outputs'])) {
            $modelInputs = $this->runEncoderForwardPass($modelInputs, $generationConfig);
        }

        // 4. Prepare decoder inputs
        if ($isEncoderDecoder) {
            [$inputIds, $modelInputs] = $this->prepareDecoderInputs(
                $modelInputs[$modelInputName]->shape()[0],
                $modelInputs,
                $generationConfig
            );
        } else {
            $inputIds = $modelInputs[$modelInputName];
        }

        // 5. Prepare `max_length` depending on other stopping criteria.
        $inputIdsLength = $inputs->shape()[count($inputs->shape()) - 1];

        if ($generationConfig->max_new_tokens !== null) {
            $generationConfig->max_length = $inputIdsLength + $generationConfig->max_new_tokens;
        }

        // 6. Prepare logits processor, stopping criteria and sampler
        $logitsProcessor = $this->prepareLogitsProcessor($generationConfig, $inputIdsLength, $logitsProcessor);
        $stoppingCriteria = $this->prepareStoppingCriteria($generationConfig, $stoppingCriteria);
        $sampler = Sampler::getSampler($generationConfig);

        // 7. Final preparation before generation
        $attentions = [];
        $numInputs = $modelInputs[$modelInputName]->shape()[0];
        $scores = array_fill(0, $numInputs, 0);
        $allInputIds = $inputIds->toArray();
        $streamer?->put($allInputIds);

        // 9. Generation loop
        while (true) {
            $modelInputs = $this->prepareInputsForGeneration($allInputIds, $modelInputs);

            $outputs = $this->forward($modelInputs);

            if ($generationConfig->output_attentions && $generationConfig->return_dict_in_generate) {
                $tokenAttentions = $this->getAttentions($outputs);
                foreach ($tokenAttentions as $key => $value) {
                    if (!array_key_exists($key, $attentions)) {
                        $attentions[$key] = [];
                    }
                    $attentions[$key][] = $value;
                }
            }

            // Logits are of the form [batch_size, out_seq_length, vocab_size]. In most cases, this will be [batch_size, 1, vocab_size]
            // So, we select the last token's logits: (equivalent to `logits = outputs.logits[:, -1, :]`)
            $logits = $outputs['logits']->slice(null, -1, null);

            // Apply logits processor
            $nextTokenScores = $logitsProcessor($allInputIds, $logits);

            $generatedInputIds = [];

            // Loop over each batch
            for ($batchIdx = 0; $batchIdx < $nextTokenScores->shape()[0]; ++$batchIdx) {
                $logs = $nextTokenScores[$batchIdx];

                $sampledTokens = $sampler($logs);

                foreach ($sampledTokens as [$newTokenId, $logProb]) {
                    // update generated ids, model inputs, and length for next step
                    $scores[$batchIdx] += $logProb;
                    $allInputIds[$batchIdx][] = $newTokenId;
                    $generatedInputIds[] = [$newTokenId];

                    // TODO: Support beam search
                    break;
                }
            }

            $streamer?->put($generatedInputIds);

            $stop = $stoppingCriteria($generatedInputIds, $scores);
            if (array_every($stop, fn($x) => $x)) {
                break;
            }

            $modelInputs = $this->updateInputsAfterGeneration($generatedInputIds, $outputs, $modelInputs, $isEncoderDecoder);
        }

        $streamer?->end();

        // 9. Retrieve and dispose all final past key values (including encoder attentions)
        $pastKeyValues = $this->getPastKeyValues($outputs, $modelInputs['past_key_values'] ?? null);

        $sequences = Tensor::fromArray($allInputIds, Tensor::int64);

        if ($generationConfig->return_dict_in_generate) {
            return [
                'sequences' => $sequences,
                'past_key_values' => $pastKeyValues,
                ...$attentions
            ];
        } else {
            return $sequences;
        }
    }

    /**
     * Runs the ONNX session with the provided inputs.
     *
     * @param InferenceSession $session The ONNX session to run.
     * @param array $inputs The inputs to the session.
     * @return array The outputs from the session.
     * @throws ModelExecutionException If an error occurs during session run.
     * @throws MissingModelInputException If required inputs are missing.
     */
    public function runSession(InferenceSession $session, array $inputs): array
    {
        try {
            $inputNames = array_column($session->inputs(), 'name');

            $inputs = $this->validateInputs($inputNames, $inputs);

            $outputNames = array_column($session->outputs(), 'name');

            return $session->run($outputNames, $inputs);
        } catch (MissingModelInputException $e) {
            throw $e;
        } catch (Exception $e) {
            throw ModelExecutionException::make($e->getMessage(), $e);
        }
    }

    /**
     * Validates the inputs against the expected input names of the ONNX session.
     *
     * @param string[] $inputNames Expected input names.
     * @param array $inputs Provided inputs.
     * @return array Validated inputs.
     * @throws MissingModelInputException If required inputs are missing.
     */
    public function validateInputs(array $inputNames, array $inputs): array
    {
        $checkedInputs = [];
        $missingInputs = [];

        foreach ($inputNames as $inputName) {
            $tensor = $inputs[$inputName] ?? null;

            if (!($tensor instanceof Tensor)) {
                $missingInputs[] = $inputName;
                continue;
            }

            $checkedInputs[$inputName] = $tensor;
        }

        if (!empty($missingInputs)) {
            throw MissingModelInputException::make($missingInputs);
        }

        $numInputsProvided = count($inputs);
        $numInputsNeeded = count($inputNames);

        if ($numInputsProvided > $numInputsNeeded) {
            $ignored = array_diff(array_keys($inputs), $inputNames);
            echo 'WARNING: Too many inputs were provided (' . $numInputsProvided . ' > ' . $numInputsNeeded . '). 
            The following inputs will be ignored: "' . implode(', ', $ignored) . '".';
        }

        return $inputs;
    }

    /**
     * Validates that the current model class is suitable for generation.
     * @throws Error If the model class cannot perform generation.
     * @return void
     */
    public function validateModelClass(): void
    {
        if (!$this->modelArchitecture->canGenerate()) {
            $className = get_called_class();
            $errorMsg = "The current model class $className is not is not compatible with \`generate()\`, as it doesn't have a language model head.";

            $possibleInfo =
                AutoModelForCausalLM::MODEL_CLASS_MAPPING[$this->config->modelType]
                ?? AutoModelForSeq2SeqLM::MODEL_CLASS_MAPPING[$this->config->modelType]
                ?? null;

            if ($possibleInfo) {
                $errorMsg .= " Try using `{$possibleInfo}` instead.";
            }

            throw new Error($errorMsg);
        }
    }

    /**
     * Merges multiple generation configs to create the final configuration for generation.
     *
     * @param ?GenerationConfig $generationConfig User-provided generation config.
     * @return GenerationConfig The final generation config.
     */
    protected function prepareGenerationConfig(?GenerationConfig $userProvidedConfig): GenerationConfig
    {
        $modelConfig = $this->config->config;
        foreach (["decoder", "generator", "text_config"] as $key) {
            if (array_key_exists($key, $modelConfig)) {
                $modelConfig = array_merge($modelConfig, $modelConfig[$key]);
            }
        }

        /** @disregard P1014 */
        $classConfig = (property_exists($this, 'generationConfig')) ? $this->generationConfig : null;

        return GenerationConfig::mergeConfigs($modelConfig, $classConfig, $userProvidedConfig);
    }

    /**
     * Prepares the encoder inputs for generation, handling potential conflicts.
     *
     * @param ?Tensor $inputs The input tensor.
     * @param array $modelKwargs Additional model-specific arguments.
     * @return array An array containing the prepared model inputs and the main input name.
     * @throws Exception If `inputs` and main input name are both passed.
     */
    function prepareEncoderInputs(?Tensor $inputs = null, array $modelKwargs = []): array
    {
        $modelInputs = array_pick($modelKwargs, $this->forwardParams);
        $inputName = $this->mainInputName;

        if (array_key_exists($inputName, $modelInputs)) {
            if ($inputs) {
                throw new Exception(
                    "`inputs` were passed alongside `{$inputName}` which is not allowed. " .
                        "Make sure to either pass `inputs` or `{$inputName}`."
                );
            }
        } else {
            $modelInputs[$inputName] = $inputs;
        }

        return [$modelInputs, $inputName];
    }

    /**
     * Runs the forward pass for the encoder part of an encoder-decoder model.
     *
     * @param array $modelInputs The model inputs.
     * @param GenerationConfig $generationConfig The generation configuration.
     * @return array The updated model inputs including encoder outputs.
     * @throws Exception If batch sizes mismatch or other errors occur.
     */
    function runEncoderForwardPass($modelInputs, GenerationConfig $generationConfig)
    {

        $inputNames = array_column($this->session->inputs(), 'name');

        if (
            in_array('inputs_embeds', $inputNames) &&
            !isset($modelInputs['inputs_embeds']) &&
            method_exists($this, 'prepareInputsEmbeds')
        ) {
            // Encoder expects `inputs_embeds` instead of `input_ids`
            $kwargs = array_diff_key($modelInputs, array_flip(['input_ids', 'pixel_values', 'attention_mask']));
            $preparedInputs = call_user_func([$this, 'prepareInputsEmbeds'], $modelInputs);
            $modelInputs = array_merge(
                $kwargs,
                array_pick($preparedInputs, ['inputs_embeds', 'attention_mask'])
            );
        }

        $encoderOutputs = $this->modelArchitecture->encoderForward($this, $modelInputs);

        $lastHiddenState = $encoderOutputs['last_hidden_state'];

        // Handle classifier-free guidance
        if (!is_null($generationConfig['guidance_scale'] ?? null) && $generationConfig['guidance_scale'] > 1) {
            $lastHiddenState = Tensor::concat([$lastHiddenState, Tensor::zerosLike($lastHiddenState)]);

            if (isset($model_inputs['attention_mask'])) {
                $modelInputs['attention_mask'] = Tensor::concat([
                    $modelInputs['attention_mask'],
                    Tensor::zerosLike($modelInputs['attention_mask'])
                ]);
            }
        } elseif (isset($modelInputs['decoder_input_ids'])) {
            $decoderBatchSize = $modelInputs['decoder_input_ids']->shape()[0];
            if ($decoderBatchSize !== $lastHiddenState->shape()[0]) {
                if ($lastHiddenState->shape()[0] !== 1) {
                    throw new Exception(sprintf(
                        "The encoder outputs have a different batch size (%d) than the decoder inputs (%d).",
                        $lastHiddenState->shape()[0],
                        $decoderBatchSize
                    ));
                }
                $lastHiddenState = Tensor::concat(array_fill(0, $decoderBatchSize, $lastHiddenState));
            }
        }

        $modelInputs['encoder_outputs'] = $lastHiddenState;

        return $modelInputs;
    }

    /**
     * Prepares the initial decoder inputs for generation.
     *
     * @param int $batchSize The batch size.
     * @param array $modelInputs The current model inputs.
     * @param GenerationConfig $generationConfig The generation configuration.
     * @return array An array containing the decoder input IDs and updated model inputs.
     * @throws Exception If `decoder_start_token_id` has incorrect length.
     */
    function prepareDecoderInputs($batchSize, $modelInputs, GenerationConfig $generationConfig): array
    {
        $decoderInputIds = array_pop_key($modelInputs, 'decoder_input_ids');

        // Prepare input IDs if not manually defined
        if (!$decoderInputIds instanceof Tensor) {
            if (empty($decoderInputIds)) {
                $decoderStartTokenId = $generationConfig->decoder_start_token_id ?? $generationConfig->bos_token_id;

                if ($this->config['model_type'] === 'musicgen') {
                    $decoderInputIds = array_fill(0, $batchSize * $this->config['decoder']['num_codebooks'], [$decoderStartTokenId]);
                } elseif (is_array($decoderStartTokenId)) {
                    if (count($decoderStartTokenId) !== $batchSize) {
                        throw new Exception(sprintf(
                            "`decoder_start_token_id` expected to have length %d but got %d",
                            $batchSize,
                            count($decoderStartTokenId)
                        ));
                    }
                    $decoderInputIds = $decoderStartTokenId;
                } else {
                    $decoderInputIds = array_fill(0, $batchSize, [$decoderStartTokenId]);
                }
            } elseif (!is_array($decoderInputIds[0])) {
                $decoderInputIds = array_fill(0, $batchSize, $decoderInputIds);
            }

            $decoderInputIds = Tensor::fromArray($decoderInputIds, Tensor::int64);
        }

        $modelInputs['decoder_attention_mask'] = Tensor::onesLike($decoderInputIds);

        return [$decoderInputIds, $modelInputs];
    }

    /**
     * Prepares the list of logits processors based on the generation configuration.
     *
     * @param GenerationConfig $generationConfig The generation configuration.
     * @param int $inputIdsSeqLength The length of the initial input IDs sequence.
     * @param ?LogitsProcessorList $logitsProcessor Optional existing logits processors list.
     * @return LogitsProcessorList The configured list of logits processors.
     */
    protected function prepareLogitsProcessor(
        GenerationConfig     $generationConfig,
        int                  $inputIdsSeqLength,
        ?LogitsProcessorList $logitsProcessor = null
    ): LogitsProcessorList {
        $processors = new LogitsProcessorList();

        if ($generationConfig->repetition_penalty != null && $generationConfig->repetition_penalty !== 1.0) {
            $processors->push(new RepetitionPenaltyLogitsProcessor($generationConfig->repetition_penalty));
        }

        if ($generationConfig->no_repeat_ngram_size != null && $generationConfig->no_repeat_ngram_size > 0) {
            $processors->push(new NoRepeatNGramLogitsProcessor($generationConfig->no_repeat_ngram_size));
        }

        if ($generationConfig->bad_words_ids != null) {
            $processors->push(new NoBadWordsLogitsProcessor($generationConfig->bad_words_ids, $inputIdsSeqLength));
        }

        if ($generationConfig->min_length != null && $generationConfig->eos_token_id != null && $generationConfig->min_length > 0) {
            $processors->push(new MinLengthLogitsProcessor($generationConfig->min_length, $generationConfig->eos_token_id));
        }

        if ($generationConfig->min_new_tokens != null && $generationConfig->eos_token_id != null && $generationConfig->min_new_tokens > 0) {
            $processors->push(
                new MinNewTokensLengthLogitsProcessor(
                    $inputIdsSeqLength,
                    $generationConfig->min_new_tokens,
                    $generationConfig->eos_token_id
                )
            );
        }

        if ($generationConfig->forced_bos_token_id !== null) {
            $processors->push(new ForcedBOSTokenLogitsProcessor($generationConfig->forced_bos_token_id));
        }

        if ($generationConfig->max_new_tokens == null && $generationConfig->forced_eos_token_id !== null) {
            $processors->push(new ForcedEOSTokenLogitsProcessor($generationConfig->max_length, $generationConfig->forced_eos_token_id));
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

        if ($logitsProcessor !== null) {
            $processors->extend($logitsProcessor);
        }

        // `LogitNormalization` should always be the last logit processor, when present
        // if($generationConfig->renormalize_logits) {
        //     $processors->push(new LogitNormalization());
        // }

        return $processors;
    }

    /**
     * Prepares the list of stopping criteria based on the generation configuration.
     *
     * @param GenerationConfig $generationConfig The generation configuration.
     * @param ?StoppingCriteriaList $stoppingCriteria Optional existing stopping criteria list.
     * @return StoppingCriteriaList The configured list of stopping criteria.
     */
    public function prepareStoppingCriteria(GenerationConfig $generationConfig, ?StoppingCriteriaList $stoppingCriteria = null): StoppingCriteriaList
    {
        $criteria = $stoppingCriteria ?? new StoppingCriteriaList();

        $criteria->push(
            new MaxLengthCriteria($generationConfig->max_length, $generationConfig['max_position_embeddings'] ?? null)
        );

        if ($generationConfig->max_time !== null) {
            $criteria->push(new MaxTimeCriteria($generationConfig->max_time));
        }

        if ($generationConfig->eos_token_id !== null) {
            $criteria->push(new EosTokenCriteria($generationConfig->eos_token_id));
        }

        return $criteria;
    }


    /**
     * Prepares the inputs required for the model's forward pass within the generation loop.
     * Delegates to the model architecture specific implementation.
     *
     * @param array $inputs Current input IDs.
     * @param array $modelInputs Current model inputs (including past states).
     * @return array Prepared model inputs for the next forward pass.
     */
    function prepareInputsForGeneration(array $inputs, array $modelInputs)
    {
        return $this->modelArchitecture->prepareInputsForGeneration($this, $inputs, $modelInputs);
    }

    /**
     * Updates the model inputs dictionary for the next generation step.
     *
     * @param array $generatedInputIds The newly generated token IDs for the current step.
     * @param array $outputs The outputs from the model's forward pass in the current step.
     * @param array $modelInputs The model inputs used in the current step.
     * @param bool $isEncoderDecoder Whether the model is an encoder-decoder architecture.
     * @return array The updated model inputs for the next generation step.
     */
    function updateInputsAfterGeneration(array $generatedInputIds, $outputs, $modelInputs, $isEncoderDecoder)
    {
        $modelInputs['past_key_values'] = $this->getPastKeyValues($outputs, $modelInputs['past_key_values'] ?? null);

        // Update input_ids for the next run
        $flatGeneratedInputIds = array_merge(...$generatedInputIds);
        $modelInputs['input_ids'] = new Tensor($flatGeneratedInputIds, Tensor::int64, [count($generatedInputIds), 1]);

        if (!$isEncoderDecoder) {
            // Update attention mask
            $modelInputs['attention_mask'] = Tensor::concat([
                $modelInputs['attention_mask'],
                Tensor::ones([$modelInputs['attention_mask']->shape()[0], 1], Tensor::int64)
            ], 1);
        } elseif (array_key_exists('decoder_attention_mask', $modelInputs)) {
            // TODO: Update decoder attention mask if the model requires it
        }

        // Force recreate position_ids in the next iteration
        $modelInputs['position_ids'] = null;

        return $modelInputs;
    }

    /**
     * Extracts past key values from the decoder/model outputs.
     *
     * @param array $decoderResults The outputs from the decoder/model forward pass.
     * @param ?array $pastKeyValues The previous past key values.
     * @return array An object containing the updated past key values.
     */
    public function getPastKeyValues(array $decoderResults, ?array $pastKeyValues): array
    {
        $pkvs = [];

        foreach ($decoderResults as $name => $value) {
            if (str_starts_with($name, 'present')) {
                $newName = str_replace('present', 'past_key_values', $name);

                if ($pastKeyValues && str_contains($name, 'encoder')) {
                    // Optimization introduced by optimum to reuse past key values.
                    // So, we just replace the constant outputs with the previous past key values.
                    // https://github.com/huggingface/optimum/blob/0bf2c05fb7e1182b52d21b703cfc95fd9e4ea3dc/optimum/onnxruntime/base.py#L677-L704
                    $pkvs[$newName] = $pastKeyValues[$newName];
                } else {
                    $pkvs[$newName] = $value;
                }
            }
        }

        return $pkvs;
    }

    /**
     * Extracts attention values from the decoder/model outputs.
     *
     * @param array $decoderResults The outputs from the decoder/model forward pass.
     * @return array An object containing attentions.
     */
    public function getAttentions(array $decoderResults): array
    {
        $attns = [];

        foreach (['cross_attentions', 'decoder_attentions'] as $attnName) {
            $result = [];
            foreach ($decoderResults as $name => $value) {
                if (str_starts_with($name, $attnName)) {
                    $index = intval(substr(strrchr($name, '.'), 1));
                    $result[$index] = $value;
                }
            }
            $attns[$attnName] = $result;
        }

        return $attns;
    }

    /**
     * Adds past key values to the decoder feeds object for the ONNX session.
     * Initializes tensors if `pastKeyValues` is null.
     *
     * @param array $decoderFeeds The decoder feeds object (passed by reference).
     * @param ?array $pastKeyValues An object containing past key values.
     * @return void
     */
    public function addPastKeyValues(array &$decoderFeeds, ?array $pastKeyValues): void
    {
        if ($pastKeyValues !== null) {
            $decoderFeeds = array_merge($decoderFeeds, $pastKeyValues);
        } else {
            $shapes = $this->config->getKeyValueShapes();

            foreach ($shapes as $name => $shape) {
                $decoderFeeds[$name] = new Tensor([], shape: $shape);
            }
        }
    }
}

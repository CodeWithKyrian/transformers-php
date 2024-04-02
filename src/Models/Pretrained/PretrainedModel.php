<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;

use Codewithkyrian\Transformers\Exceptions\HubException;
use Codewithkyrian\Transformers\Exceptions\MissingModelInputException;
use Codewithkyrian\Transformers\Exceptions\ModelExecutionException;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\BadWordsLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\ForcedBOSTokenLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\ForcedEOSTokenLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\ForceTokensLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\LogitsProcessorList;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\MinLengthLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\MinNewTokensLengthLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\NoRepeatNGramLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\RepetitionPenaltyLogitsProcessor;
use Codewithkyrian\Transformers\Generation\LogitsProcessors\SuppressTokensAtBeginLogitsProcessor;
use Codewithkyrian\Transformers\Generation\Samplers\Sampler;
use Codewithkyrian\Transformers\Generation\Streamers\Streamer;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForCausalLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForSeq2SeqLM;
use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Models\Output\BaseModelOutput;
use Codewithkyrian\Transformers\Models\Output\ModelOutput;
use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\Hub;
use Codewithkyrian\Transformers\Utils\Tensor;
use Exception;
use OnnxRuntime\InferenceSession;
use Symfony\Component\Console\Output\OutputInterface;
use function Codewithkyrian\Transformers\Utils\array_some;

/**
 * A base class for pre-trained models that provides the model configuration and an ONNX session.
 */
class PretrainedModel
{
    public string $mainInputName = 'input_ids';

    /**
     * @param array $config The model configuration.
     * @param mixed $session The ONNX session.
     */
    public function __construct(
        public AutoConfig        $config,
        public InferenceSession  $session,
        public ModelArchitecture $modelArchitecture = ModelArchitecture::EncoderOnly,
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
     * @param array|AutoConfig|null $config The configuration object used to instantiate the model.
     * @param string|null $cacheDir Path to a directory in which a downloaded pretrained model configuration should
     * @param string|null $token The token to use as an authorization to download from private model repos.
     * @param string $revision The specific model version to use. It can be a branch name, a tag name,
     * @param string|null $modelFilename The name of the model file to load. If not provided, will default to the
     * @param ModelArchitecture $modelArchitecture
     * @return self The model instantiated from the configuration.
     * @throws HubException
     */
    public static function fromPretrained(
        string            $modelNameOrPath,
        bool              $quantized = true,
        array|AutoConfig  $config = null,
        ?string           $cacheDir = null,
        ?string           $token = null,
        string            $revision = 'main',
        ?string           $modelFilename = null,
        ModelArchitecture $modelArchitecture = ModelArchitecture::EncoderOnly,
        ?OutputInterface $output = null
    ): self
    {
        if (is_array($config)) {
            $config = AutoConfig::fromPretrained($modelNameOrPath, $config, $cacheDir, $revision, $output);
        }


        switch ($modelArchitecture) {
            case ModelArchitecture::DecoderOnly:
            {
                $session = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: $modelFilename ?? 'decoder_model_merged', cacheDir: $cacheDir, revision: $revision, output: $output);

                $generatorConfigArr = Hub::getJson(pathOrRepoID: $modelNameOrPath, fileName: 'generation_config.json',
                    cacheDir: $cacheDir, revision: $revision, fatal: false, output: $output);

                $generatorConfig = new GenerationConfig($generatorConfigArr);

                return new static($config, $session, $modelArchitecture, $generatorConfig);
            }

            case ModelArchitecture::Seq2SeqLM:
            case ModelArchitecture::Vision2Seq:
            {
                $encoderSession = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'encoder_model', cacheDir: $cacheDir, revision: $revision, output: $output);

                $decoderSession = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'decoder_model_merged', cacheDir: $cacheDir, revision: $revision, output: $output);

                $generatorConfigArr = Hub::getJson(pathOrRepoID: $modelNameOrPath, fileName: 'generation_config.json',
                    cacheDir: $cacheDir, revision: $revision, fatal: false, output: $output);

                $generatorConfig = new GenerationConfig($generatorConfigArr);


                return new static($config, $encoderSession, $decoderSession, $modelArchitecture, $generatorConfig);
            }

            case ModelArchitecture::MaskGeneration:
            {
                $visionEncoder = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'vision_encoder', cacheDir: $cacheDir, revision: $revision, output: $output);

                $promptMaskEncoder = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'prompt_encoder_mask_decoder', cacheDir: $cacheDir, revision: $revision, output: $output);

                return new static($config, $visionEncoder, $promptMaskEncoder, $modelArchitecture);
            }

            case ModelArchitecture::EncoderDecoder:
            {
                $encoderSession = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'encoder_model', cacheDir: $cacheDir, revision: $revision, output: $output);

                $decoderSession = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'decoder_model_merged', cacheDir: $cacheDir, revision: $revision, output: $output);

                return new static($config, $encoderSession, $decoderSession, $modelArchitecture);
            }

            default:
            {
                if ($modelArchitecture != ModelArchitecture::EncoderOnly) {
                    echo "WARNING: {$modelArchitecture->value} is not a valid model group. Defaulting to EncoderOnly.";
                }

                $session = self::constructSession(modelNameOrPath: $modelNameOrPath,
                    fileName: 'model', cacheDir: $cacheDir, revision: $revision, output: $output);

                return new static($config, $session, $modelArchitecture);
            }
        }
    }

    public function __invoke(array $modelInputs): array|ModelOutput
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
        return $this->modelArchitecture->forward($this, $modelInputs);
    }

    /**
     *  Initializes and returns the beam for text generation task
     *
     * @param Tensor $inputTokenIds The input token ids.
     * @param GenerationConfig $generationConfig The generation config.
     * @param int $numOutputTokens The number of tokens to generate.
     * @param Tensor|null $inputsAttentionMask The attention mask for the input token ids.
     * @return array{ inputs: Tensor, output_token_ids: Tensor, score: float, done: bool, id: int } The initial beam for text generation.
     *
     */
    public function getStartBeams(
        Tensor           $inputTokenIds,
        GenerationConfig $generationConfig,
        int              $numOutputTokens,
        Tensor           $inputsAttentionMask = null
    ): array
    {
        return $this->modelArchitecture->startBeams(
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
        return $this->modelArchitecture->runBeam($this, $beam);
    }

    /**
     *  Update a beam with a new token ID.
     *
     * @param array $beam The beam to update.
     * @param int $newTokenId The new token id to add to the beam.
     *
     */
    public function updateBeam(array &$beam, int $newTokenId): void
    {
        $this->modelArchitecture->updateBeam($beam, $newTokenId);
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
        ?OutputInterface $output = null,
                ...$sessionOptions
    ): ?InferenceSession
    {
        $modelFileName = sprintf('onnx/%s%s.onnx', $fileName, $quantized ? '_quantized' : '');

        $file = Hub::getFile($modelNameOrPath, $modelFileName, $cacheDir, $revision, $subFolder, $fatal, null, $output);

        if ($file === null) return null;

        return new InferenceSession($file, ...$sessionOptions);
    }

    /**
     * @param InferenceSession $session
     * @param Tensor[] $inputs
     * @return Tensor[]
     * @throws MissingModelInputException
     */
    public function validateInputs(array $inputNames, array $inputs): array
    {
        $checkedInputs = [];
        $missingInputs = [];

        foreach ($inputNames as $inputName) {
            $tensor = $inputs[$inputName];

            // Check if the input is an instance of Tensor
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
            // No missing inputs, but too many inputs were provided.
            // Warn the user and ignore the extra inputs.
            $ignored = array_diff(array_keys($inputs), $inputNames);
            echo 'WARNING: Too many inputs were provided (' . $numInputsProvided . ' > ' . $numInputsNeeded . '). 
            The following inputs will be ignored: "' . implode(', ', $ignored) . '".';
        }

        return array_map(fn($i) => $i->toArray(), $inputs);
    }

    /**
     * @throws ModelExecutionException
     * @throws MissingModelInputException
     */
    public function runSession(InferenceSession $session, array $inputs): array
    {
        try {
            $inputNames = array_column($session->inputs(), 'name');

            $inputs = $this->validateInputs($inputNames, $inputs);

            $outputNames = array_column($session->outputs(), 'name');

            $outputs = $session->run($outputNames, $inputs);

            return array_combine($outputNames, array_map([Tensor::class, 'fromArray'], $outputs));
        } catch (MissingModelInputException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw ModelExecutionException::make($e->getMessage());
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
            $mo = Tensor::getMo();

            $data = $mo->f(fn($x) => $x != $padTokenId, $tokens);

            return new Tensor($data, $tokens->dtype(), $tokens->shape());
        } else {
            return Tensor::onesLike($tokens);
        }
    }

    /**
     * Add position IDs to the feeds object.
     * @param array $inputNames The names of the inputs to the model.
     * @param array $feeds The input to the model.
     * @param bool $useCacheBranch Whether to use the cache branch of the model.
     * @return void
     */
    public function preparePositionIds(array $inputNames, array &$feeds, bool $useCacheBranch): void
    {
        if (!in_array('position_ids', $inputNames)) {
            return;
        }

        // TODO: Verify this works properly!!!
        $data = array_fill(0, count($feeds['attention_mask']), 0);

        // Compute cumulative sum of the attention mask along the sequence length dimension
        for ($i = 0; $i < $feeds['attention_mask']['dims'][0]; ++$i) {
            $start = $i * $feeds['attention_mask']['dims'][1];
            $sum = 0;
            for ($j = 0; $j < $feeds['attention_mask']['dims'][1]; ++$j) {
                $index = $start + $j;
                if ($feeds['attention_mask']['data'][$index] === 0) {
                    $data[$index] = 1;
                } else { // === 1
                    $data[$index] = $sum;
                    $sum += $feeds['attention_mask']['data'][$index];
                }
            }
        }

        $feeds['position_ids'] = new Tensor($data, shape: $feeds['attention_mask']->shape());

        if ($useCacheBranch) {
            // TODO: Fix this
//            $feeds['position_ids'] = $feeds['position_ids']->slice(null, -1)->unsqueeze_(-1);
        }
    }

    /**
     * Helper function to add attentions to beam.
     *
     * @param array $beam
     * @param array $output
     * @throws Exception
     */
    public function addAttentionsToBeam(array &$beam, array $output): void
    {
        if ($this->config->isEncoderDecoder) {
            if (empty($output['cross_attentions'])) {
                throw new Exception(
                    "`output_attentions` is true, but the model did not produce cross-attentions. " .
                    "This is most likely because the model was not exported with `output_attentions=True`."
                );
            }
            if (!isset($beam['cross_attentions'])) {
                $beam['cross_attentions'] = [];
            }
            $beam['cross_attentions'][] = $output['cross_attentions'];
        }

        if (empty($output['decoder_attentions'])) {
            throw new Exception(
                "`output_attentions` is true, but the model did not produce decoder-attentions. " .
                "This is most likely because the model was not exported with `output_attentions=True`."
            );
        }
        if (!isset($beam['decoder_attentions'])) {
            $beam['decoder_attentions'] = [];
        }
        $beam['decoder_attentions'][] = $output['decoder_attentions'];
    }

    /**
     * Groups an array of beam objects by their ids.
     *
     * @param array $beams The array of beam objects to group.
     * @return array An array of arrays, where each inner array contains beam objects with the same id.
     */
    public function groupBeams(array $beams): array
    {
        $groups = [];

        foreach ($beams as $obj) {
//            $groups[$obj['id']][] = $obj;
            if (!isset($groups[$obj['id']])) {
                $groups[$obj['id']] = [$obj];
            } else {
                $groups[$obj['id']][] = $obj;
            }
        }

        return array_values($groups);
    }


    /**
     * Returns an object containing past key values from the given decoder results object.
     *
     * @param array $decoderResults The decoder results object.
     * @param ?array $pastKeyValues The previous past key values.
     * @return array An object containing past key values.
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
     * Returns an object containing attentions from the given decoder results object.
     *
     * @param array $decoderResults The decoder results object.
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
     * Adds past key values to the decoder feeds object. If pastKeyValues is null, creates new tensors for past key values.
     *
     * @param array $decoderFeeds The decoder feeds object to add past key values to.
     * @param ?array $pastKeyValues An object containing past key values.
     */
    public function addPastKeyValues(array &$decoderFeeds, ?array $pastKeyValues): void
    {
        if ($pastKeyValues !== null) {
            $decoderFeeds = array_merge($decoderFeeds, $pastKeyValues);
        } else {
            // TODO support batches (i.e., batch_size > 1)
            $batch_size = 1;

            if ($this->config->isEncoderDecoder && ($this->addEncoderPkv ?? true)) {
                $encoderShape = [$batch_size, $this->numEncoderHeads, 1, $this->encoderDimKv];
                $decoderShape = [$batch_size, $this->numDecoderHeads, 1, $this->decoderDimKv];


                for ($i = 0; $i < $this->numDecoderLayers; ++$i) {
                    $decoderFeeds["past_key_values.$i.encoder.key"]
                        = $decoderFeeds["past_key_values.$i.encoder.value"]
                        = new Tensor(null, shape: $encoderShape);
                    $decoderFeeds["past_key_values.$i.decoder.key"]
                        = $decoderFeeds["past_key_values.$i.decoder.value"]
                        = new Tensor(null, shape: $decoderShape);
                }
            } else if ($this->config->modelType === 'falcon') {
                // NOTE: Custom implementation for Falcon
                $shape = [$batch_size * $this->numHeads, 1, $this->dimKv];

                for ($i = 0; $i < $this->numLayers; ++$i) {
                    $decoderFeeds["past_key_values.$i.key"] = new Tensor(null, shape: $shape);
                    $decoderFeeds["past_key_values.$i.value"] = new Tensor(null, shape: $shape);
                }
            } else if ($this->config['multi_query'] ?? null) { // e.g., for `gpt_bigcode`
                $shape = [$batch_size * $this->numHeads, 1, 2 * $this->dimKv];

                for ($i = 0; $i < $this->numLayers; ++$i) {
                    $decoderFeeds["past_key_values.$i.key_value"] = new Tensor(null, shape: $shape);
                }
            } else if ($this->config['model_type'] === 'bloom') {
                // NOTE: Custom implementation for Bloom
                $keyShape = [$batch_size * $this->numHeads, $this->dimKv, 1];
                $valueShape = [$batch_size * $this->numHeads, 1, $this->dimKv];

                for ($i = 0; $i < $this->numLayers; ++$i) {
                    $decoderFeeds["past_key_values.$i.key"] = new Tensor(null, shape: $keyShape);
                    $decoderFeeds["past_key_values.$i.value"] = new Tensor(null, shape: $valueShape);
                }
            } else { // Decoder-only
                $shape = [$batch_size, $this->numHeads, 1, $this->dimKv];

                for ($i = 0; $i < $this->numLayers; ++$i) {
                    $decoderFeeds["past_key_values.$i.key"] = new Tensor(null, shape: $shape);
                    $decoderFeeds["past_key_values.$i.value"] = new Tensor(null, shape: $shape);
                }
            }
        }
    }


    /** Generates text based on the given inputs and generation configuration using the model.
     * @param Tensor $inputs The input token ids.
     * @param GenerationConfig|null $generationConfig The generation configuration to use. If null, default configuration will be used.
     * @param LogitsProcessorList|null $logitsProcessor An optional logits processor to use. If null, a new LogitsProcessorList instance will be created.
     * @param array|null $inputsAttentionMask An optional attention mask for the inputs.
     * @return array An array of generated output sequences, where each sequence is an array of token IDs.
     */
    public function generate(
        Tensor               $inputs,
        ?GenerationConfig    $generationConfig = null,
        ?LogitsProcessorList $logitsProcessor = null,
        Tensor                $inputsAttentionMask = null,
        ?Streamer            $streamer = null,
    ): array
    {
        if (!$this->modelArchitecture->canGenerate()) {
            $className = get_called_class();
            $errorMsg = "The current model class {$className} is not is not compatible with \`generate()\`, as it doesn't have a language model head.";

            $possibleInfo =
                AutoModelForCausalLM::MODEL_CLASS_MAPPING[$this->config->modelType]
                ?? AutoModelForSeq2SeqLM::MODEL_CLASS_MAPPING[$this->config->modelType]
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

        $eosTokenIds = $generationConfig->eos_token_id;

        if ($eosTokenIds !== null && !is_array($eosTokenIds)) {
            $eosTokenIds = [$eosTokenIds];
        }

        // TODO implement early_stopping
        // https://huggingface.co/blog/how-to-generate

        $numOutputTokens = 1;
        $maxOutputTokens = $numOutputTokens + ($generationConfig->max_new_tokens ?? INF);

        // Only use max length if max_new_tokens is not provided
        $useMaxLength = is_null($generationConfig->max_new_tokens);

        $sampler = Sampler::getSampler($generationConfig);


        $beams = $this->getStartBeams($inputs, $generationConfig, $numOutputTokens, $inputsAttentionMask);


        while (array_some($beams, fn($beam) => !$beam['done']) && $numOutputTokens < $maxOutputTokens) {
            $newestBeams = [];
            foreach ($beams as $beam) {
                if ($beam['done']) {
                    // Add this beam back into the pool
                    $newestBeams[] = $beam;
                    continue;
                }
                if ($useMaxLength && count($beam['output_token_ids']) >= $generationConfig->max_length) {
                    // Set this beam to done and add it back into the pool
                    $beam['done'] = true;
                    $newestBeams[] = $beam;
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
                $logits = $output['logits']->slice(null, -1, null);
//                $logits = $output['logits'];

                // Apply logits processor
                $logitsProcessor($beam['output_token_ids'], $logits);

                $sampledTokens = $sampler($logits);

                foreach ($sampledTokens as [$newTokenId, $logProb]) {
                    // use previous beam as a starting point
                    $newBeam = $beam;

                    // update new beam
                    $this->updateBeam($newBeam, $newTokenId);

                    $newBeam['score'] += $logProb;

                    if ($eosTokenIds && in_array($newTokenId, $eosTokenIds, true)) {
                        $newBeam['done'] = true;
                    }

                    $newestBeams[] = $newBeam;
                }

            }


            ++$numOutputTokens;

            // Group and select best beams
            $newestBeams = array_merge(...array_map(
                function ($group) use ($generationConfig) {
                    usort($group, fn($a, $b) => $b['score'] <=> $a['score']);
                    return array_slice(
                        $group,
                        0,
                        $generationConfig->num_beams
                    );
                },
                $this->groupBeams($newestBeams)
            ));


            // Flatten beams
            $beams = $newestBeams;

            // Stream the beams if a streamer is provided
            $streamer?->put($beams);
        }


        // TODO: Ensure that we can return non-batched outputs

        $groupedBeams = $this->groupBeams($beams);

        $getFlattened = function ($key) use ($groupedBeams, $generationConfig) {
            $flattened = array_map(
                function ($batch) use ($key, $generationConfig) {
                    if ($generationConfig->num_return_sequences > 1) {
                        return array_slice(
                            array_map(fn($beam) => $beam[$key], $batch),
                            0,
                            $generationConfig->num_return_sequences
                        );
                    } else {
                        // Only extract the first element's key value
                        return [$batch[0][$key]];
                    }
                },
                $groupedBeams
            );

            return array_merge(...$flattened); // Flatten the resulting array
        };

        $sequences = $getFlattened('output_token_ids'); // [1, seqLength]

        // End the streamer if it was provided
        $streamer?->end();


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
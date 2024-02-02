<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models;

use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\Hub;
use Codewithkyrian\Transformers\Utils\Tensor;
use Interop\Polite\Math\Matrix\NDArray;
use OnnxRuntime\InferenceSession;
use Rindow\Math\Matrix\NDArrayPhp;

/**
 * A base class for pre-trained models that provides the model configuration and an ONNX session.
 */
class PreTrainedModel
{
    protected string $mainInputName = 'input_ids';

    protected static ModelType $modelType = ModelType::EncoderOnly;

    protected bool $canGenerate = false;

    protected mixed $runBeam = null;

    protected mixed $getStartBeams = null;

    protected mixed $updateBeam = null;

    protected mixed $forward = null;

    /**
     * @param array $config The model configuration.
     * @param mixed $session The ONNX session.
     */
    public function __construct(
        public readonly AutoConfig       $config,
        protected InferenceSession $session,
                                   ...$args
    )
    {
        switch ($this::$modelType) {
            case ModelType::DecoderOnly:
                $this->canGenerate = true;

                $this->runBeam = 'decoderRunBeam';
                $this->getStartBeams = 'decoderStartBeams';
                $this->updateBeam = 'decoderUpdatebeam';
                $this->forward = 'decoderForward';
                break;
            case ModelType::Seq2Seq:
            case ModelType::Vision2Seq:
                $this->canGenerate = true;

                $this->runBeam = 'seq2seqRunBeam';
                $this->getStartBeams = 'seq2seqStartBeams';
                $this->updateBeam = 'seq2seqUpdatebeam';
                $this->forward = 'seq2seqForward';
                break;
            case ModelType::EncoderDecoder:
            case ModelType::EncoderOnly:
            default:
                $this->forward = 'encoderForward';
                break;
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

                $generatorConfig = Hub::getJson(pathOrRepoID: $modelNameOrPath, fileName: 'generator_config.json',
                    cacheDir: $cacheDir, revision: $revision, fatal: false);

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
        return $this->{$this->forward}($modelInputs);
    }

    /**
     * Forward pass of an encoder model.
     * @param array{input_ids: Tensor, token_type_ids: Tensor} $modelInputs The input data to be used for the forward pass.
     *
     * @return array{logits: Tensor, hidden_states: Tensor, attentions: Tensor}
     */
    protected function encoderForward(array $modelInputs): array
    {
        $encoderFeeds = [];

        foreach ($this->session->inputs as ['name' => $inputName]) {
            $encoderFeeds[$inputName] = $modelInputs[$inputName];
        }

        $hasTokenTypeIds = in_array('token_type_ids', array_column($this->session->inputs, 'name'));

        if ($hasTokenTypeIds) {
            // Assign default `token_type_ids` (all zeroes) to the `encoderFeeds` if the model expects it,
            // but they weren't created by the tokenizer.
            $encoderFeeds['token_type_ids'] ??= Tensor::zerosLike($encoderFeeds['input_ids']);
        }

        return $this->runSession($this->session, $encoderFeeds);
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


}
<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Models\Pretrained;


use Codewithkyrian\Transformers\Models\Auto\AutoModel;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForCausalLM;
use Codewithkyrian\Transformers\Models\ModelArchitecture;
use Codewithkyrian\Transformers\Utils\AutoConfig;
use Codewithkyrian\Transformers\Utils\GenerationConfig;
use Codewithkyrian\Transformers\Utils\InferenceSession;

/**
 * Vision Encoder-Decoder model based on OpenAI's GPT architecture for image captioning and other vision tasks
 */
class VisionEncoderDecoderModel extends PretrainedModel
{
    public string $mainInputName = 'pixel_values';
    protected array $forwardParams = [
        // Encoder inputs
        'pixel_values',

        // Decoder inputs
        'decoder_input_ids',
        'encoder_hidden_states',
        'past_key_values',
    ];
    protected bool $addEncoderPkv;
    protected mixed $numDecoderLayers;
    protected mixed $numDecoderHeads;
    protected mixed $decoderDimKv;
    protected mixed $numEncoderLayers;
    protected mixed $numEncoderHeads;
    protected mixed $encoderDimKv;
    protected $numLayers;
    protected $numHeads;
    protected $dimKv;

    /**
     * Creates a new instance of the `VisionEncoderDecoderModel` class.
     *
     * @param AutoConfig $config The configuration array specifying the hyperparameters and other model settings.
     * @param mixed $session The ONNX session containing the encoder model.
     * @param InferenceSession $decoderMergedSession The ONNX session containing the merged decoder model.
     * @param ModelArchitecture $modelArchitecture
     * @param GenerationConfig $generationConfig Configuration object for the generation process.
     */
    public function __construct(
        AutoConfig               $config,
        InferenceSession         $session,
        public InferenceSession  $decoderMergedSession,
        public ModelArchitecture $modelArchitecture,
        public GenerationConfig  $generationConfig
    )
    {
        parent::__construct($config, $session, $this->modelArchitecture);

        // Extract configs
        $encoderConfig = $this->config['encoder'];
        $decoderConfig = $this->config['decoder'];

        $decoderConfig = AutoConfig::fromPretrained('', $decoderConfig);

        // Validate encoder
        $encoderModelType = $encoderConfig['model_type'];
        $encoderModel = AutoModel::ENCODER_ONLY_MODEL_MAPPING[$encoderModelType]
            ?? AutoModel::ENCODER_DECODER_MODEL_MAPPING[$encoderModelType];

        if (!$encoderModel) {
            echo "Model type for encoder '{$encoderModelType}' not found, assuming encoder-only architecture. Please report this at https://github.com/CodeWithKyrian/transformers-php/issues/new/choose.";
        }

        // Validate decoder
        $decoderModel = AutoModelForCausalLM::MODEL_CLASS_MAPPING[$decoderConfig['model_type']];

        if (!$decoderModel) {
            throw new \Exception("Unable to construct `VisionEncoderDecoder` due to unsupported decoder: '{$this->config['decoder']['model_type']}'");
        }

        $decoder = new $decoderModel($decoderConfig, $this->decoderMergedSession, ModelArchitecture::DecoderOnly, $this->generationConfig);

        $this->addEncoderPkv = property_exists($decoder, 'numDecoderLayers');

        if ($this->addEncoderPkv) {
            // Decoder is part of an encoder-decoder model
            $this->numDecoderLayers = $decoder->numDecoderLayers;
            $this->numDecoderHeads = $decoder->numDecoderHeads;
            $this->decoderDimKv = $decoder->decoderDimKv;

            $this->numEncoderLayers = $decoder->numEncoderLayers;
            $this->numEncoderHeads = $decoder->numEncoderHeads;
            $this->encoderDimKv = $decoder->encoderDimKv;
        } else {
            // Decoder is a decoder-only model
            $this->numLayers = $decoder->numLayers;
            $this->numHeads = $decoder->numHeads;
            $this->dimKv = $decoder->dimKv;
        }
    }
}

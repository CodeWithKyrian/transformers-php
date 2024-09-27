<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Utils;

/**
 * Class representing a configuration for a generation task.
 */
class GenerationConfig implements \ArrayAccess
{
    /** @var int The maximum length the generated tokens can have. Corresponds to the length of the input prompt + `max_new_tokens`. Its effect is overridden by `max_new_tokens`, if also set. */
    public int $max_length;

    /** @var int|null The maximum numbers of tokens to generate, ignoring the number of tokens in the prompt. */
    public ?int $max_new_tokens;

    /** @var int The minimum length of the sequence to be generated. */
    public int $min_length;

    /** @var int|null The minimum numbers of tokens to generate, ignoring the number of tokens in the prompt. */
    public ?int $min_new_tokens;

    /** @var bool|string Controls the stopping condition for beam-based methods, like beam-search. */
    public bool|string $early_stopping;

    /** @var int|null The maximum amount of time you allow the computation to run for in seconds. */
    public ?int $max_time;

    /** @var bool Whether or not to use sampling; use greedy decoding otherwise. */
    public bool $do_sample;

    /** @var int Number of beams for beam search. */
    public int $num_beams;

    /** @var int Number of groups to divide `num_beams` into in order to ensure diversity among different groups of beams. */
    public int $num_beam_groups;

    /** @var float|null The values balance the model confidence and the degeneration penalty in contrastive search decoding. */
    public ?float $penalty_alpha;

    /** @var bool Whether or not the model should use the past last key/values attentions to speed up decoding. */
    public bool $use_cache;

    /** @var float The value used to modulate the next token probabilities. */
    public float $temperature;

    /** @var int The number of highest probability vocabulary tokens to keep for top-k-filtering. */
    public int $top_k;

    /** @var float If set, only the smallest set of most probable tokens with probabilities that add up to `top_p` or higher are kept for generation. */
    public float $top_p;

    /** @var float Local typicality measures how similar the conditional probability of predicting a target token next is to the expected conditional probability of predicting a random token next, given the partial text already generated. */
    public float $typical_p;

    /** @var float If set to float strictly between 0 and 1, only tokens with a conditional probability greater than `epsilon_cutoff` will be sampled. */
    public float $epsilon_cutoff;

    /** @var float Eta sampling is a hybrid of locally typical sampling and epsilon sampling. */
    public float $eta_cutoff;

    /** @var float This value is subtracted from a beam's score if it generates a token same as any beam from other group at a particular time. */
    public float $diversity_penalty;

    /** @var float The parameter for repetition penalty. */
    public float $repetition_penalty;

    /** @var float The parameter for encoder_repetition_penalty. */
    public float $encoder_repetition_penalty;

    /** @var float Exponential penalty to the length that is used with beam-based generation. */
    public float $length_penalty;

    /** @var int If set to int > 0, all ngrams of that size can only occur once. */
    public int $no_repeat_ngram_size;

    /** @var int[][]|null List of token ids that are not allowed to be generated. */
    public ?array $bad_words_ids;

    /** @var int[][]|int[][][]|null List of token ids that must be generated. */
    public ?array $force_words_ids;

    /** @var bool Whether to re-normalize the logits after applying all the logits processors or warpers (including the custom ones). */
    public bool $renormalize_logits;

    /** @var ?array Custom constraints that can be added to the generation to ensure that the output will contain the use of certain tokens. */
    public ?array $constraints;

    /** @var ?int The id of the token to force as the first generated token after the `decoder_start_token_id`. */
    public ?int $forced_bos_token_id;

    /** @var int|int[]|null The id of the token to force as the last generated token when `max_length` is reached. */
    public int|array|null $forced_eos_token_id;

    /** @var bool Whether to remove possible *nan* and *inf* outputs of the model to prevent the generation method to crash. */
    public bool $remove_invalid_values;

    /** @var int[]|null This Tuple adds an exponentially increasing length penalty, after a certain amount of tokens have been generated. */
    public ?array $exponential_decay_length_penalty;

    /** @var int[]|null A list of tokens that will be suppressed at generation. */
    public ?array $suppress_tokens;

    /** @var int[]|null A list of tokens that will be suppressed at the beginning of the generation. */
    public ?array $begin_suppress_tokens;

    /** @var int[][]|null A list of pairs of integers which indicates a mapping from generation indices to token indices that will be forced before sampling. */
    public ?array $forced_decoder_ids;

    /** @var int The number of independently computed returned sequences for each element in the batch. */
    public int $num_return_sequences;

    /** @var bool Whether or not to return the attentions tensors of all attention layers. */
    public bool $output_attentions;

    /** @var bool Whether or not to return the hidden states of all layers. */
    public bool $output_hidden_states;

    /** @var bool Whether or not to return the prediction scores. */
    public bool $output_scores;

    /** @var bool Whether or not to return a `ModelOutput` instead of a plain tuple. */
    public bool $return_dict_in_generate;

    /** @var int|int[]|null The id of the *padding* token. */
    public int|array|null $pad_token_id;

    /** @var int|null The id of the *beginning-of-sequence* token. */
    public ?int $bos_token_id;

    /** @var int|int[]|null The id of the *end-of-sequence* token. */
    public int|array|null $eos_token_id;

    /** @var int If set to int > 0, all ngrams of that size that occur in the `encoder_input_ids` cannot occur in the `decoder_input_ids`. */
    public int $encoder_no_repeat_ngram_size;

    /** @var int|null If an encoder-decoder model starts decoding with a different token than *bos*, the id of that token. */
    public ?int $decoder_start_token_id;

    /** @var array Additional generation kwargs will be forwarded to the `generate` function of the model. */
    public array $generation_kwargs;
    /**
     * @var mixed|null
     */
    public ?array $decoder_input_ids;

    /**
     * Create a new GenerationConfig object.
     * @param array $kwargs The configuration parameters.
     */
    public function __construct(protected array $kwargs = [])
    {
        $this->max_length = $kwargs['max_length'] ?? 20;
        $this->max_new_tokens = $kwargs['max_new_tokens'] ?? null;
        $this->min_length = $kwargs['min_length'] ?? 0;
        $this->min_new_tokens = $kwargs['min_new_tokens'] ?? null;
        $this->early_stopping = $kwargs['early_stopping'] ?? false;
        $this->max_time = $kwargs['max_time'] ?? null;
        $this->do_sample = $kwargs['do_sample'] ?? false;
        $this->num_beams = $kwargs['num_beams'] ?? 1;
        $this->num_beam_groups = $kwargs['num_beam_groups'] ?? 1;
        $this->penalty_alpha = $kwargs['penalty_alpha'] ?? null;
        $this->use_cache = $kwargs['use_cache'] ?? true;
        $this->temperature = $kwargs['temperature'] ?? 1.0;
        $this->top_k = $kwargs['top_k'] ?? 50;
        $this->top_p = $kwargs['top_p'] ?? 1.0;
        $this->typical_p = $kwargs['typical_p'] ?? 1.0;
        $this->epsilon_cutoff = $kwargs['epsilon_cutoff'] ?? 0.0;
        $this->eta_cutoff = $kwargs['eta_cutoff'] ?? 0.0;
        $this->diversity_penalty = $kwargs['diversity_penalty'] ?? 0.0;
        $this->repetition_penalty = $kwargs['repetition_penalty'] ?? 1.0;
        $this->encoder_repetition_penalty = $kwargs['encoder_repetition_penalty'] ?? 1.0;
        $this->length_penalty = $kwargs['length_penalty'] ?? 1.0;
        $this->no_repeat_ngram_size = $kwargs['no_repeat_ngram_size'] ?? 0;
        $this->bad_words_ids = $kwargs['bad_words_ids'] ?? null;
        $this->force_words_ids = $kwargs['force_words_ids'] ?? null;
        $this->renormalize_logits = $kwargs['renormalize_logits'] ?? false;
        $this->constraints = $kwargs['constraints'] ?? null;
        $this->forced_bos_token_id = $kwargs['forced_bos_token_id'] ?? null;
        $this->forced_eos_token_id = $kwargs['forced_eos_token_id'] ?? null;
        $this->remove_invalid_values = $kwargs['remove_invalid_values'] ?? false;
        $this->exponential_decay_length_penalty = $kwargs['exponential_decay_length_penalty'] ?? null;
        $this->suppress_tokens = $kwargs['suppress_tokens'] ?? null;
        $this->begin_suppress_tokens = $kwargs['begin_suppress_tokens'] ?? null;
        $this->forced_decoder_ids = $kwargs['forced_decoder_ids'] ?? null;
        $this->num_return_sequences = $kwargs['num_return_sequences'] ?? 1;
        $this->output_attentions = $kwargs['output_attentions'] ?? false;
        $this->output_hidden_states = $kwargs['output_hidden_states'] ?? false;
        $this->output_scores = $kwargs['output_scores'] ?? false;
        $this->return_dict_in_generate = $kwargs['return_dict_in_generate'] ?? false;
        $this->pad_token_id = $kwargs['pad_token_id'] ?? null;
        $this->bos_token_id = $kwargs['bos_token_id'] ?? null;
        $this->eos_token_id = $kwargs['eos_token_id'] ?? null;
        $this->encoder_no_repeat_ngram_size = $kwargs['encoder_no_repeat_ngram_size'] ?? 0;
        $this->decoder_start_token_id = $kwargs['decoder_start_token_id'] ?? null;
        $this->generation_kwargs = $kwargs['generation_kwargs'] ?? [];
        $this->decoder_input_ids = $kwargs['decoder_input_ids'] ?? null;
    }

    public function toArray(): array
    {
        $objectProps = array_filter(get_object_vars($this), fn($value) => $value !== null);
        unset($objectProps['kwargs']);
        return array_merge($objectProps, $this->kwargs);
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset) || isset($this->kwargs[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->kwargs[$offset] ?? $this->$offset;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        }

        $this->kwargs[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->kwargs[$offset]);
    }
}

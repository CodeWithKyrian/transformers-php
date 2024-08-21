---
outline: deep
---

# All About Generation

## What is Text Generation?

Text generation is a specialized task in Natural Language Processing (NLP) where models produce sequences of text based
on an input prompt. Unlike other NLP tasks such as classification or named entity recognition, which involve predicting
labels or extracting information from text, generation tasks involve creating new content. This can range from
continuing a given story, answering questions, summarizing text, or generating creative content.

### How Generation Differs from Other NLP Tasks

In text generation, the model is expected to generate coherent, contextually relevant, and often creative sequences of
text. This is distinct from tasks where the focus is on assigning categories to text or identifying specific elements.
For instance:

- **Classification**: Assigns predefined labels to text (e.g., spam detection).
- **Named Entity Recognition**: Identifies and categorizes entities within the text (e.g., names of people,
  organizations).
- **Text Generation**: Produces novel sequences of text, which may involve creative writing or providing a detailed
  response to a prompt.

### Purpose of Generation Options

The various generation options are designed to fine-tune the output of text generation models according to specific
requirements. These options can influence several aspects of the generated text, such as:

- **Length**: Define how long or short the generated output should be.
- **Diversity**: Control the variety of the generated text to avoid repetitive or predictable results.
- **Sampling Methods**: Adjust how the model selects from possible next tokens to balance creativity and coherence.
- **Constraints**: Enforce specific rules or guidelines on the generated text, such as avoiding certain words or forcing
  inclusion of specific terms.

### Model-Specific Configurations

Not all models are suited for every generation task. Many models come with a `generation_config.json` file in their
repository, which provides default configuration settings optimized for the specific model. This configuration is
applied when the model instance is created.

When invoking the model, additional arguments can be passed to override these default values. This allows for dynamic
adjustments to generation parameters based on the specific needs of the task at hand, enabling finer control over the
generation process.

By understanding and utilizing these options, you can better tailor the text generation capabilities of your model to
meet your unique requirements and achieve the desired results.

## Generation Options

### `maxLength` *(int)*

The maximum length the generated tokens can have. Corresponds to the length of the input prompt + `maxNewTokens`. Its
effect is overridden by `maxNewTokens`, if also set.

### `maxNewTokens` *(int)*

The maximum number of tokens to generate, ignoring the number of tokens in the prompt.

### `minNewTokens` *(int)*

The minimum number of tokens to generate, ignoring the number of tokens in the prompt. Default is `null`.

### `earlyStopping` *(boolean | "never")*

Controls the stopping condition for beam-based methods like beam search:

- `true`: Generation stops as soon as there are `numBeams` complete candidates.
- `false`: Stops when it's very unlikely to find better candidates.
- `"never"`: Beam search stops only when no better candidates can be found. Default is `false`.

### `maxTime` *(int)*

The maximum amount of time allowed for computation in seconds. Generation will finish the current pass after the
allocated time. Default is `null`.

### `doSample` *(boolean)*

Toggles between sampling (true) and greedy decoding (false) for generating tokens. Sampling introduces randomness in
the generation, making it possible to get different outputs each time for the same input.

### `numBeams` *(int)*

Determines the beam search size. A setting of 1 disables beam search, resulting in faster but potentially less
accurate results. Default is `1`.

### `numBeamGroups` *(int)*

Number of groups to divide `numBeams` into to ensure diversity among different groups of beams. Default is `1`.

### `penaltyAlpha` *(float)*

Balances model confidence and degeneration penalty in contrastive search decoding. Default is `null`.

### `useCache` *(boolean)*

Whether to use past key/values attentions to speed up decoding. Default is `true`.

### `temperature` *(float)*

Adjusts the probability distribution of the next token to make generation more deterministic or more random. A
temperature of 1.0 maintains randomness, while a temperature of 0.0 makes the output more deterministic.

### `topK` *(int)*

The number of highest probability vocabulary tokens to keep for top-k filtering. Default is `50`.

### `topP` *(float)*

If set to a float < 1, only the smallest set of most probable tokens with probabilities that add up to `topP` or higher
are kept for generation. Default is `1.0`.

### `typicalP` *(float)*

Local typicality measures how similar the conditional probability of predicting a target token next is to the expected
conditional probability of predicting a random token next. Default is `1.0`.

### `epsilonCutoff` *(float)*

Only tokens with a conditional probability greater than `epsilonCutoff` will be sampled. Default is `0.0`.

### `etaCutoff` *(float)*

Eta sampling is a hybrid of locally typical sampling and epsilon sampling. Default is `0.0`.

### `diversityPenalty` *(float)*

Subtracted from a beam's score if it generates a token same as any beam from other groups at a particular time. Default
is `0.0`.

### `repetitionPenalty` *(float)*

Penalizes the repetition of the same token. A setting of 1.0 implies no penalty. This helps in generating more diverse
outputs by reducing the likelihood of repetitive sequences. Default is `1.0`.

### `encoderRepetitionPenalty` *(float)*

Penalizes sequences not in the original input. Default is `1.0`.

### `lengthPenalty` *(float)*

Exponential penalty to the length used with beam-based generation. Default is `1.0`.

### `noRepeatNgramSize` *(int)*

All n-grams of this size can only occur once. Default is `0`.

### `badWordsIds` *(int[][])*

List of token ids that are not allowed to be generated. Default is `null`.

### `forceWordsIds` *(int[][] | int[][][])*

List of token ids that must be generated. Default is `null`.

### `renormalizeLogits` *(boolean)*

Whether to renormalize the logits after applying all logit processors or warpers. Default is `false`.

### `constraints` *(array)*

Custom constraints to ensure the output contains certain tokens. Default is `null`.

### `forcedBosTokenId` *(int)*

The id of the token to force as the first generated token. Default is `null`.

### `forcedEosTokenId` *(int | int[])*

The id of the token to force as the last generated token. Default is `null`.

### `removeInvalidValues` *(boolean)*

Whether to remove possible `NaN` and `Inf` outputs to prevent crashes. Default is `false`.

### `exponentialDecayLengthPenalty` *(int[])*

Adds an exponentially increasing length penalty after a certain number of tokens have been generated. Default is `null`.

### `suppressTokens` *(int[])*

A list of tokens to suppress during generation. Default is `null`.

### `beginSuppressTokens` *(int[])*

A list of tokens to suppress at the beginning of generation. Default is `null`.

### `forcedDecoderIds` *(int[][])*

A list of pairs mapping generation indices to token indices that will be forced before sampling. Default is `null`.

### `numReturnSequences` *(int)*

The number of independently computed returned sequences for each batch element. Default is `1`.

### `outputAttentions` *(boolean)*

Whether to return attention tensors of all attention layers. Default is `false`.

### `outputHiddenStates` *(boolean)*

Whether to return hidden states of all layers. Default is `false`.

### `outputScores` *(boolean)*

Whether to return prediction scores. Default is `false`.

### `returnDictInGenerate` *(boolean)*

Whether to return a `ModelOutput` instead of a plain tuple. Default is `false`.

### `padTokenId` *(int)*

The id of the padding token. Default is `null`.

### `bosTokenId` *(int)*

The id of the beginning-of-sequence token. Default is `null`.

### `eosTokenId` *(int | int[])*

The id of the end-of-sequence token. Default is `null`.

### `encoderNoRepeatNgramSize` *(int)*

All n-grams of this size in the encoder input cannot occur in the decoder input. Default is `0`.

### `decoderStartTokenId` *(int)*

The id of the token that the decoder starts with if different from `bos`. Default is `null`.

## Streamers

# Changelog

All notable changes to `transformers-php` will be documented in this file.

## v0.6.2 - 2025-09-15

### What's Changed

* allow Symfony 8 components by @xabbuh in https://github.com/CodeWithKyrian/transformers-php/pull/94
* Using correct method to add command to CLI application by @CViniciusSDias in https://github.com/CodeWithKyrian/transformers-php/pull/93
* fix: PHP 8.4 nullable deprecations by @chr-hertel in https://github.com/CodeWithKyrian/transformers-php/pull/97
* Fix namespace conflict with Transformers class by @Flatroy in https://github.com/CodeWithKyrian/transformers-php/pull/91

### New Contributors

* @xabbuh made their first contribution in https://github.com/CodeWithKyrian/transformers-php/pull/94
* @CViniciusSDias made their first contribution in https://github.com/CodeWithKyrian/transformers-php/pull/93
* @chr-hertel made their first contribution in https://github.com/CodeWithKyrian/transformers-php/pull/97
* @Flatroy made their first contribution in https://github.com/CodeWithKyrian/transformers-php/pull/91

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.6.1...0.6.2

## 0.6.1 - 2025-07-21

### What's New

- feat: update Rindow Matlib macOS binaries for ARM64 and x86_64 architectures
- fix: update darwin platform directory names
- chore(ci): clean up distribution directory after artifact creation in release workflow

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.6.0...0.6.1

## v0.6.0 - 2025-07-21

### What’s Changed

- Add support for stopping criteria: `MaxLength`, `MaxTime`, and `Interruptable` for more flexible generation control.
- Add PSR-3 logging support
- Extend `PretrainedConfig` to reduce code repetition across model files and improve maintainability.
- Restructure `AutoModel` resolution to prioritize generic model selection when no task-specific class is found.
- Add support for new model families: `Gemma`, `Gemma2`, `Gemma3`, `Qwen3`, and `Phi3`, with their respective causal language models.
- Add support for `eos` and `last_token` pooling strategies in the `FeatureExtractionPipeline`.
- Add support for new merge format in `BPEModel` with better compatibility via JSON-encoded token pair mapping.
- Introduce `PretrainedModel::$sessions `array to streamline model session handling across subclasses.
- Simplify streamer implementation to improve clarity and flexibility.
- Enhance image processing methods and VIPS integration.
- Convert to a platform package with native shared library support for Linux, macOS, and Windows (x86_64 and ARM64).
- Introduce dynamic shared library loading with platform-specific path resolution logic.
- Update example configurations, docs, and usage instructions to reflect new architecture.
- Add tests for image utilities and inference session logic.

### Bug Fixes

- Fix `Tensor::slice()` error during generation.
- Fix logic in `RepetitionPenaltyLogitsProcessor` to properly apply penalties based on generated tokens.
- Fix compatibility issues with PHP 8.1 and adjust dependency versions accordingly.
- Fix bug in `HubTest` for Windows paths by using `DIRECTORY_SEPARATOR`.
- Fix edge case in `BPEModel` when null value encountered in merge map.
- Fix various doc inconsistencies and default constructor bugs in model subclasses.

### Improvements

- Refactor library architecture for improved platform compatibility and modularity.
  
- Refactor `Samplerate`, `Sndfile`, and other FFI wrappers to use instance-based invocation.
  
- Improve generation configuration merging logic and model resolution flow.
  
- Unify code style across components and improve clarity of core components.
  
- Update shared native dependencies:
  
  - onnxruntime → 1.21.0
  - rindowmatlib → 1.1.1
  
- Clean up feature extractors and consolidate model session logic.
  
- Improve error handling in FFI calls and utility classes.
  
- Rename internal config and model mapping classes for better clarity (`PretrainedMixin` → `AutoModelBase`, etc.)
  

### New Contributors

* @panariga made their first contribution in https://github.com/CodeWithKyrian/transformers-php/pull/73
* @Deltachaos made their first contribution in https://github.com/CodeWithKyrian/transformers-php/pull/77

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.5.3...0.6.0

## TransformersPHP v0.5.3 - 2024-09-27

This release brings new features, critical bug fixes, and improvements to enhance the functionality and performance of the package. Below is a summary of the changes.

### What's New

- **feat**: Add support for PostProcessor Sequence by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/2cf18ccd83ad84ad8389a7f8c0198252d523f3f6
- **feat**: New tensor method `random` by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/0564dd1609db203d4665a4c040cf1b55e4503ac9
- **feat**: Correctly parse and use the Precompiled Normalizer by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/7e880dd9917b074d995feb98aed9bd41754429ff
- **feat**: Update conversion notebook to include task by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/ca6fc3b72a688ba739a6d62c062b406e7d821d25

### Bug Fixes

- **fix**: Regex bug in Precompiled Normalizer by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/69089b124b299a7b7bef49b676ee5951ac7ef7fb
- **fix**: Improve Unigram Tokenizer handling of multibyte strings by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/e8a8a9ab48e81692d77260197b42e15f768df086
- **fix**: Correct WhitespaceSplit Pretokenizer handling of invisible space characters by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/6ec3e3e9016952913a951d838b237bfb425e07a9
- **fix**: Correctly handle multibyte strings in Precompiled Normalizer by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/bee47e01858b4c69e34eb061c571498c9e0a4e14
- **fix**: Fuse function not combining unknown token IDs correctly by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/300801334cce6f67651665957e776764584db8bc
- **fix**: Tensor topK error when -1 is passed by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/0564dd1609db203d4665a4c040cf1b55e4503ac9

### Improvements

- **fix**: Precompiled Normalizer improvements by @CodeWithKyrian https://github.com/CodeWithKyrian/transformers-php/commit/b01240076ddd50637b1135d3c5e9e55dab08a592

I encourage everyone to update to this latest version and explore the new features. As always, feel free to report any issues or contribute to the project.

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.5.2...0.5.3

## TransformersPHP v0.5.2 - 2024-08-29

### What's Changed

* Use a static list for byte-unicode and unicode-byte conversion by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/75f5d9cd671791f0542963f4541dfc6d8dad5dcb
* Fix Vips RGBA -> RGBA conversion error by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/54bdee0a2b0542e57922ede1b633e462de80b0e1
* Show output progress when downloading the shared libraries by @CodeWithKyrian  in https://github.com/CodeWithKyrian/transformers-php/commit/d613a0d1c2774ca83bb6743862c12c259cfddf71
* Obviate the need for autoload.php in establishing library base path by @timwhitlock in https://github.com/CodeWithKyrian/transformers-php/pull/65

### New Contributors

* @timwhitlock made their first contribution in https://github.com/CodeWithKyrian/transformers-php/pull/65

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.5.1...0.5.2

## TransformersPHP v0.5.1 - 2024-08-24

### What's new

- **Tensor Operations**: `magnitude`, `sqrt` and `cosSimilarity` added.
- **Vips Binaries**: - Vips binaries are now bundled by default, eliminating need to modify anything on the system to use libvips.

### Bug Fixes

- **Error Handling**: - Adjusted error level to a warning for unknown model types, providing clearer feedback without interrupting the workflow.

### Reversions

- **Dependencies**: Reverted `rokka/vips` from dev back to normal dependencies. Since vips binaries are bundled by default, use of vips is now encouraged.

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.5.0...0.5.1

## TransformersPHP v0.5.0 - 2024-08-21

I'm excited to announce the latest version of TransformersPHP, packed with new features, improvements, and bug fixes. This release brings powerful enhancements to your machine-learning-driven PHP applications, enabling more efficient and versatile operations.

### New Features

- **New Pipeline: Audio Classification** - Easily classify audio clips with a pre-trained model.
  
    ```php
    $classifier = pipeline('audio-classification', 'Xenova/ast-finetuned-audioset-10-10-0.4593');
  $audioUrl = __DIR__ . '/../sounds/cat_meow.wav';
  $output = $classifier($audioUrl);
  // [
  //   [
  //     "label" => "Meow"
  //     "score" => 0.6109990477562
  //   ]
  // ]
  
  
  
  
  
  
  
    ```
- **New Pipeline: Automatic Speech Recognition (ASR)** - Supports models like `wav2vec` and `whisper` for transcribing speech to text. If a specific model is not officially supported, please open an issue with a feature request.
  
  - Example:
    ```php
    $transcriber = pipeline('asr', 'Xenova/whisper-tiny.en');
    $audioUrl = __DIR__ . '/../sounds/preamble.wav';
    $output = $transcriber($audioUrl, maxNewTokens: 256);
    // [
    //   "text" => "We, the people of the United States, ..."
    // ]
    
    
    
    
    
    
    
    ```
  

### Enhancements

- **Shared Libraries Dependencies:** - A revamped workflow for downloading shared libraries dependencies ensures they are versioned correctly, reducing download sizes. These binaries are now thoroughly tested on Apple Silicon, Intel Macs, Linux x86_64, Linux aarch64, and Windows platforms.
  
- **`Transformers::setup` Simplified** - `Transformers::setup()` is now optional. Default settings are automatically applied if not called. The` apply()` method is no longer necessary, but still available for backward compatibility.
  
- **Immutable Image Utility** - The Image utility class is now immutable. Each operation returns a new instance, allowing for method chaining and a more predictable workflow.
  
    ```php
    $image = Image::read($url);
  $resizedImage = $image->resize(100, 100);
  // $image remains unchanged
  
  
  
  
  
  
  
    ```
- **New Tensor Operations** - New operations were added: `copyTo`, `log`, `exp`, `pow`, `sum`, `reciprocal`, `stdMean`. Additionally, overall performance improvements have been made to Tensor operations.
  
- **TextStreamer Improvements** - TextStreamer now prints to stdout by default. You can override this behavior using the `onStream(callable $callback)` method. Consequently, the `StdoutStreamer` class is now obsolete.
  
- **VIPS PHP Driver Update** - The VIPS PHP driver is no longer bundled by default in `composer.json`. Detailed documentation is provided for installing the Vips PHP driver and setting up Vips on your machine.
  
- **ONNX Runtime Upgrade** - Upgraded to version 1.19.0, bringing more performance and compatibility with newer models.
  
- Bug Fixes & Performance Improvements - Various bug fixes have been implemented to enhance stability and performance across the package.
  

I hope you enjoy these updates and improvements. If you encounter any issues or have any suggestions, please don’t hesitate to reach out through our [Issue Tracker](https://github.com/CodeWithKyrian/transformers-php/issues)

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.4.4...0.5.0

## v0.4.4 - 2024-08-14

### What's Changed

* feat: add optional host argument for model download by @k99k5 in https://github.com/CodeWithKyrian/transformers-php/pull/56

### New Contributors

* @k99k5 made their first contribution in https://github.com/CodeWithKyrian/transformers-php/pull/56

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.4.3...0.4.4

## v0.4.3 - 2024-07-31

### What's Changed

* Fix typo in docs by @BlackyDrum in https://github.com/CodeWithKyrian/transformers-php/pull/42
* fix: statically calling FFI::new deprecated in PHP 8.3 by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/48
* fix: improve regex for detecting language codes in NllbTokenizer by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/49
* fix: digits pre-tokenizer returning empty array for text with no digits by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/51
* [feat: allow passing model filename when downloading a model from CLI](https://github.com/CodeWithKyrian/transformers-php/commit/91db063eab90da732f301a028e23a0a00ee25979)
* fix: preTokenizer null error when there's no text pair](https://github.com/CodeWithKyrian/transformers-php/commit/901a049b8bd837c83d3edcd517dd76cf8e3ba6b9)
* feat: implement enforce size divisibility for image feature extractor by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/53

### New Contributors

* @BlackyDrum made their first contribution in https://github.com/CodeWithKyrian/transformers-php/pull/42

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.4.2...0.4.3

## v0.4.2 - 2024-06-05

### What's Changed

* bugfix: Repository url resolution not working properly in Windows by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/41

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.4.1...0.4.2

## v0.4.1 - 2024-05-24

### What's Changed

* configuration.md: fix indentation of Transformers::setup() by @k00ni in https://github.com/CodeWithKyrian/transformers-php/pull/35
* PretrainedTokenizer::truncateHelper: prevent array_slice() error for flawed text input (summarization) by @k00ni in https://github.com/CodeWithKyrian/transformers-php/pull/36
* Fix bug with Download CLI - use named parameters for model construct by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/39

### New Contributors

* @k00ni made their first contribution in https://github.com/CodeWithKyrian/transformers-php/pull/35

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.4.0...0.4.1

## v0.3.1 - 2024-04-22

### What's Changed

* Add Qwen2 model support by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/20
* Add chat input detection for text generation, and refactor streamer API. by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/21
* bugfix: Fix error that occurs when streamer is not used by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/22
* bugfix: Decoder sequence not calling the right method by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/23

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.3.0...0.3.1

## v0.3.0 - 2024-04-13

### What's Changed

* Add Image Classification pipelines support by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/9
* Add Zero shot Image Classification pipelines support by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/9
* Add New Image Driver - VIPS by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/10
* Add Object Detection Pipeline by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/11
* Download ONNXRuntime automatically after composer install by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/12
* Add Zero Shot Object Detection Pipeline and OwlVit models by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/14
* Improve tensor performance by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/13
* Set [MASK] usage in prompts for default Xenova/bert-base-uncased model by @takielias in https://github.com/CodeWithKyrian/transformers-php/pull/15
* Add image feature extraction pipeline by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/16
* Add image to image pipeline by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/17
* bugfix: https slashes affected when joining paths by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/19

### Breaking Changes

* The install command no longer exists, as the required libraries are downloaded automatically on composer install.
* New Image driver configuration settings added that required either GD, Imagick or Vips

### New Contributors

* @takielias made their first contribution in https://github.com/CodeWithKyrian/transformers-php/pull/15

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.2.2...0.3.0

## v0.2.2 - 2024-03-25

### What's new

- bugfix: Fix the wrong argument being passed in Autotokenizer by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/05e55888d9ad0184103061347a427b259afb360e
- feat: cache tokenizer output to improve speed in repetitive tasks leading to 75% speed improvement (11.7687s to 2.9687s) by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/commit/b115c28f526dfbde13457c00f5306d05a51c445b

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.2.1...0.2.2

## v0.2.1 - 2024-03-22

## What's Changed

* bugfix: Add symfony/console explicitly as a dependency by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/7
* bugfix: Autoload errors for `WordPieceTokenizer` on case-sensitive operating systems in https://github.com/CodeWithKyrian/transformers-php/commit/0f1fc8bda91fb3df9492057a4224b171d2e3f2d5

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.2.0...0.2.1

## v0.2.0 - 2024-03-21

### What's Changed

* feat: Add ability to use chat templates in Text Generation by @CodeWithKyrian in https://github.com/CodeWithKyrian/transformers-php/pull/1
* bugfix: Autoload errors for `PretrainedModel` on case-sensitive operating systems by @CodeWithKyrian  in https://github.com/CodeWithKyrian/transformers-php/pull/4
* feat: Bump OnnxRuntime PHP to 0.2.0 in https://github.com/CodeWithKyrian/transformers-php/commit/b3331623cf6696aacbbad0f8c33530086404424d
* feat: Improve download and install command interfaces to show progress bar in https://github.com/CodeWithKyrian/transformers-php/commit/b3331623cf6696aacbbad0f8c33530086404424d

**Full Changelog**: https://github.com/CodeWithKyrian/transformers-php/compare/0.1.0...0.2.0

## v0.1.0 - 2024-03-15

### Initial Release 🎉

We are thrilled to announce the launch of Transformers PHP, a groundbreaking library that brings the power of state-of-the-art machine learning to the PHP community. Inspired by the HuggingFace Transformers and Xenova Transformers.js, Transformers PHP aims to provide an easy-to-use, high-performance toolset for developers looking to integrate advanced NLP, and in future updates potentially more, capabilities into their PHP applications.

#### Key Features:

- **Seamless Integration:** Designed to be functionally equivalent to its Python counterpart, making the transition and usage straightforward for developers familiar with the original Transformers library.
- **Performance Optimized:** Utilizes ONNX Runtime for efficient model inference, ensuring high performance even in demanding scenarios.
- **Comprehensive Model Support:** Access to thousands of pre-trained models across 100+ languages, covering a wide range of tasks including text generation, summarization, translation, sentiment analysis, and more.
- **Easy Model Conversion:** With 🤗 Optimum, easily convert PyTorch or TensorFlow models to ONNX format for use with Transformers PHP.
- **Developer Friendly:** From installation to deployment, every aspect of Transformers PHP is designed with ease of use in mind, featuring extensive documentation and a streamlined API.

#### Getting Started:

Installation is a breeze with Composer:

```bash
composer require codewithkyrian/transformers


















```
And you must initialize the library to download neccesary libraries for ONNX

```bash
./vendor/bin/transformers install


















```
#### Checkout the Documentation

For a comprehensive guide on how to use Transformers PHP, including detailed examples and configuration options, visit our [documentation](https://codewithkyrian.github.io/transformers-docs/docs).

#### Pre-Download Models:

To ensure a smooth user experience, especially with larger models, we recommend pre-downloading models before deployment. Transformers PHP includes a handy CLI tool for this purpose:

```bash
./vendor/bin/transformers download <model_identifier>


















```
#### What's Next?

This initial release lays the groundwork for a versatile machine learning toolkit within the PHP ecosystem. We are committed to continuous improvement and expansion of Transformers PHP, with future updates aimed at increasing supported tasks, enhancing functionality, and broadening the scope of models.

#### Get Involved!

We encourage feedback, contributions, and discussions from the community. Whether you're reporting bugs, requesting features, or contributing code, your input is invaluable in making Transformers PHP better for everyone.

#### Acknowledgments:

A huge thank you to Hugging Face for their incredible work on the Transformers library, to Xenova for inspiring this package,  and to the broader machine learning community for their ongoing research and contributions. Transformers PHP stands on the shoulders of giants, and we are excited to see how it will empower PHP developers to push the boundaries of what's possible.

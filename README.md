<h1 align="center">
   TransformersPHP
</h1>

<h3 align="center">
    <p>State-of-the-art Machine Learning for PHP</p>
</h3>

<p align="center">
<a href="https://packagist.org/packages/codewithkyrian/transformers"><img src="https://img.shields.io/packagist/dt/codewithkyrian/transformers" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/codewithkyrian/transformers"><img src="https://img.shields.io/packagist/v/codewithkyrian/transformers" alt="Latest Stable Version"></a>
<a href="https://github.com/CodeWithKyrian/transformers-php/blob/main/LICENSE"><img src="https://img.shields.io/github/license/codewithkyrian/transformers-php" alt="License"></a>
<a href="https://github.com/codewithkyrian/transformers-php"><img src="https://img.shields.io/github/repo-size/codewithkyrian/transformers-php" alt="Documentation"></a>
</p>

TransformersPHP is designed to be functionally equivalent to the Python library, while still maintaining the same level of performance and ease of use. This library is built on top of the Hugging Face's Transformers library, which provides thousands of pre-trained models in 100+ languages. It is designed to be a simple and easy-to-use library for PHP developers using a similar API to the Python library. These models can be used for a variety of tasks, including text generation, summarization, translation, and more.

TransformersPHP uses [ONNX Runtime](https://onnxruntime.ai/) to run the models, which is a high-performance scoring engine for Open Neural Network Exchange (ONNX) models. You can easily convert any PyTorch or TensorFlow model to ONNX and use it with TransformersPHP using [🤗 Optimum](https://github.com/huggingface/optimum#onnx--onnx-runtime).

TO learn more about the library and how it works, head over to our [extensive documentation](https://codewithkyrian.github.io/transformers-php/introduction).

## Quick tour

Because TransformersPHP is designed to be functionally equivalent to the Python library, it's super easy to learn from existing Python or Javascript code. We provide the `pipeline` API, which is a high-level, easy-to-use API that groups together a model with its necessary preprocessing and postprocessing steps.

<table>
<tr>

<th align="center"><b>Python (original)</b></th>
<th align="center"><b>PHP (ours)</b></th>
<th align="center"><b>Javascript (Xenova)</b></th>

</tr>

<tr>
<td>

```python
from transformers import pipeline

# Allocate a pipeline for sentiment-analysis
pipe = pipeline('sentiment-analysis')

out = pipe('I love transformers!')
# [{'label': 'POSITIVE', 'score': 0.999806941}]
```

</td>
<td>

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

// Allocate a pipeline for sentiment-analysis
$pipe = pipeline('sentiment-analysis');

$out = $pipe('I love transformers!');
// [{'label': 'POSITIVE', 'score': 0.999808732}]
```

</td>
<td>

```javascript
import {pipeline} from '@xenova/transformers';

// Allocate a pipeline for sentiment-analysis
let pipe = await pipeline('sentiment-analysis');

let out = await pipe('I love transformers!');
// [{'label': 'POSITIVE', 'score': 0.999817686}]
```

</td>
</tr>
</table>

You can also use a different model by specifying the model id or path as the second argument to the `pipeline` function.
For example:

```php
use function Codewithkyrian\Transformers\Pipelines\pipeline;

// Allocate a pipeline for translation
$pipe = pipeline('translation', 'Xenova/distilbert-base-uncased-finetuned-sst-2-english');

```

## Installation

You can install the library via Composer. This is the recommended way to install the library.

```bash
composer require codewithkyrian/transformers
```

> [!CAUTION]
> The ONNX library is platform-specific, so it's important to run the composer require command on the target platform where the code will be executed. In most cases, this will be your development machine or a server where you deploy your application, but if you're using a Docker container, run the `composer require` command inside that container.

## PHP FFI Extension

TransformersPHP uses the PHP FFI extension to interact with the ONNX runtime. The FFI extension is included by default in PHP 7.4 and later, but it may not be enabled by default. If the FFI extension is not enabled, you can enable it by uncommenting(remove the `;` from the beginning of the line) the
following line in your `php.ini` file:

```ini
extension = ffi
```

Also, you need to set the `ffi.enable` directive to `true` in your `php.ini` file:

```ini
ffi.enable = true
```

After making these changes, restart your web server or PHP-FPM service, and you should be good to go.

## Documentation

For more detailed information on how to use the library, check out the documentation : [https://codewithkyrian.github.io/transformers-php](https://codewithkyrian.github.io/transformers-php)

## Usage

By default, TransformersPHP uses hosted pretrained ONNX models. For supported tasks, models that have been converted to work with [Xenova's Transformers.js](https://huggingface.co/models?library=transformers.js) on HuggingFace should work out of the box with TransformersPHP.

## Configuration

You can configure the behaviour of the TransformersPHP library as follows:

```php
use Codewithkyrian\Transformers\Transformers;

Transformers::setup()
    ->setCacheDir('...') // Set the default cache directory for transformers models. Defaults to `.transformers-cache/models`
    ->setRemoteHost('...') // Set the remote host for downloading models. Defaults to `https://huggingface.co`
    ->setRemotePathTemplate('...') // Set the remote path template for downloading models. Defaults to `{model}/resolve/{revision}/{file}`
    ->setAuthToken('...') // Set the auth token for downloading models. Defaults to `null`
    ->setUserAgent('...') // Set the user agent for downloading models. Defaults to `transformers-php/{version}`
    ->setImageDriver('...') // Set the image driver for processing images. Defaults to `IMAGICK'
    ->apply(); // Apply the configuration
```

You can call the `set` methods in any order, or leave any out entirely, in which case, it uses the default values. For more information on the configuration options and what they mean, checkout
the [documentation](https://codewithkyrian.github.io/transformers-php/configuration).

## Convert your models to ONNX

TransformersPHP only works with ONNX models, therefore, you must convert your PyTorch, TensorFlow or JAX models to ONNX. We recommend using the [conversion script](https://github.com/huggingface/transformers.js/blob/main/scripts/convert.py) from Transformers.js, which uses the  [🤗 Optimum](https://huggingface.co/docs/optimum) behind the scenes to perform the conversion and quantization of your model.

```
python -m convert --quantize --model_id <model_name_or_path>
```

## Pre-Download Models

By default, TransformersPHP automatically retrieves model weights (ONNX format) from the Hugging Face model hub when you first use a pipeline or pretrained model. This can lead to a slight delay during the initial use. To improve the user experience, it's recommended to pre-download the models you intend to use before running them in your PHP application, especially for larger models. One way to do that is run the request once manually, but TransformersPHP also comes with a command line tool to help you do just that:

```bash
./vendor/bin/transformers download <model_identifier> [<task>] [options]
```

Explanation of Arguments:

- **<model_identifier>**: This specifies the model you want to download. You can find model identifiers by browsing the
  Hugging Face model hub (https://huggingface.co/models?library=transformers.js).
- **[\<task\>]**: (Optional) This parameter allows for downloading task-specific configurations and weights. This can be
  helpful if you know the specific task you'll be using the model for (e.g., "text2text-generation").
- **[options]**: (Optional) You can further customize the download process with additional options:
    - **--cache_dir=\<directory\>**: Specify a directory to store downloaded models (defaults to the configured cache).
      You can
      use -c as a shortcut in the command.
    - **--quantized=\<true|false\>**: Download the quantized model version if available (defaults to true). Quantized
      models are
      smaller and faster, but may have slightly lower accuracy. Use -q as a shortcut in the command.

> [!CAUTION]
> Remember to add your cache directory to your `.gitignore` file to avoid committing the downloaded models to your git
> repository.

## Supported tasks/models

This package is a WIP, but here's a list of tasks and architectures currently tested and supported by TransformersPHP.

### Tasks

#### Natural Language Processing

| Task                                                                                                   | ID                                            | Description                                                                                    | Supported? |
|--------------------------------------------------------------------------------------------------------|-----------------------------------------------|------------------------------------------------------------------------------------------------|------------|
| [Fill-Mask](https://codewithkyrian.github.io/transformers-php/fill-mask)                               | `fill-mask`                                   | Masking some of the words in a sentence and predicting which words should replace those masks. | ✅          |
| [Question Answering](https://codewithkyrian.github.io/transformers-php/question-answering)             | `question-answering`                          | Retrieve the answer to a question from a given text.                                           | ✅          |
| [Sentence Similarity](https://codewithkyrian.github.io/transformers-php/sentence-similarity)           | `sentence-similarity`                         | Determining how similar two texts are.                                                         | ✅          |
| [Summarization](https://codewithkyrian.github.io/transformers-php/summarization)                       | `summarization`                               | Producing a shorter version of a document while preserving its important information.          | ✅          |
| [Table Question Answering](https://huggingface.co/tasks/table-question-answering)                      | `table-question-answering`                    | Answering a question about information from a given table.                                     | ❌          |
| [Text Classification](https://codewithkyrian.github.io/transformers-php/text-classification)           | `text-classification` or `sentiment-analysis` | Assigning a label or class to a given text.                                                    | ✅          |
| [Text Generation](https://codewithkyrian.github.io/transformers-php/text-generation)                   | `text-generation`                             | Producing new text by predicting the next word in a sequence.                                  | ✅          |
| [Text-to-text Generation](https://codewithkyrian.github.io/transformers-php/text-to-text-generation)   | `text2text-generation`                        | Converting one text sequence into another text sequence.                                       | ✅          |
| [Token Classification](https://codewithkyrian.github.io/transformers-php/token-classification)         | `token-classification` or `ner`               | Assigning a label to each token in a text.                                                     | ✅          |
| [Translation](https://codewithkyrian.github.io/transformers-php/translation)                           | `translation`                                 | Converting text from one language to another.                                                  | ✅          |
| [Zero-Shot Classification](https://codewithkyrian.github.io/transformers-php/zero-shot-classification) | `zero-shot-classification`                    | Classifying text into classes that are unseen during training.                                 | ✅          |

#### Vision

| Task                                                                                           | ID                     | Description                                                                                                                                                                             | Supported? |
|------------------------------------------------------------------------------------------------|------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| [Depth Estimation](https://codewithkyrian.github.io/transformers-php/depth-estimation)         | `depth-estimation`     | Predicting the depth of objects present in an image.                                                                                                                                    | ❌          |
| [Image Classification](https://codewithkyrian.github.io/transformers-php/image-classification) | `image-classification` | Assigning a label or class to an entire image.                                                                                                                                          | ✅          |
| [Image Segmentation](https://codewithkyrian.github.io/transformers-php/image-segmentation)     | `image-segmentation`   | Divides an image into segments where each pixel is mapped to an object. This task has multiple variants such as instance segmentation, panoptic segmentation and semantic segmentation. | ❌          |
| [Image-to-Image](https://codewithkyrian.github.io/transformers-php/image-to-image)             | `image-to-image`       | Transforming a source image to match the characteristics of a target image or a target image domain.                                                                                    | ✅          |
| [Mask Generation](https://codewithkyrian.github.io/transformers-php/mask-generation)           | `mask-generation`      | Generate masks for the objects in an image.                                                                                                                                             | ❌          |
| [Object Detection](https://codewithkyrian.github.io/transformers-php/object-detection)         | `object-detection`     | Identify objects of certain defined classes within an image.                                                                                                                            | ✅          |

#### Audio

| Task                                                                                      | ID                                  | Description                                          | Supported? |
|-------------------------------------------------------------------------------------------|-------------------------------------|------------------------------------------------------|------------|
| [Audio Classification](https://huggingface.co/tasks/audio-classification)                 | `audio-classification`              | Assigning a label or class to a given audio.         | ❌          |
| [Audio-to-Audio](https://huggingface.co/tasks/audio-to-audio)                             | N/A                                 | Generating audio from an input audio source.         | ❌          |
| [Automatic Speech Recognition](https://huggingface.co/tasks/automatic-speech-recognition) | `automatic-speech-recognition`      | Transcribing a given audio into text.                | ❌          |
| [Text-to-Speech](https://huggingface.co/tasks/text-to-speech)                             | `text-to-speech` or `text-to-audio` | Generating natural-sounding speech given text input. | ❌          |

#### Tabular

| Task                                                                          | ID  | Description                                                         | Supported? |
|-------------------------------------------------------------------------------|-----|---------------------------------------------------------------------|------------|
| [Tabular Classification](https://huggingface.co/tasks/tabular-classification) | N/A | Classifying a target category (a group) based on set of attributes. | ❌          |
| [Tabular Regression](https://huggingface.co/tasks/tabular-regression)         | N/A | Predicting a numerical value given a set of attributes.             | ❌          |

#### Multimodal

| Task                                                                                                                                      | ID                               | Description                                                                                                                   | Supported? |
|-------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------|-------------------------------------------------------------------------------------------------------------------------------|------------|
| [Document Question Answering](https://huggingface.co/tasks/document-question-answering)                                                   | `document-question-answering`    | Answering questions on document images.                                                                                       | ❌          |
| [Feature Extraction](https://codewithkyrian.github.io/transformers-php/feature-extraction)                                                | `feature-extraction`             | Transforming raw data into numerical features that can be processed while preserving the information in the original dataset. | ✅          |
| [Image Feature Extraction](https://codewithkyrian.github.io/transformers-php/image-feature-extraction)                                    | `image-feature-extraction`       | Extracting features from images.                                                                                              | ✅          |
| [Image-to-Text](https://codewithkyrian.github.io/transformers-php/image-to-text)                                                          | `image-to-text`                  | Output text from a given image.                                                                                               | ✅          |
| [Text-to-Image](https://huggingface.co/tasks/text-to-image)                                                                               | `text-to-image`                  | Generates images from input text.                                                                                             | ❌          |
| [Visual Question Answering](https://huggingface.co/tasks/visual-question-answering)                                                       | `visual-question-answering`      | Answering open-ended questions based on an image.                                                                             | ❌          |
| [Zero-Shot Audio Classification](https://huggingface.co/learn/audio-course/chapter4/classification_models#zero-shot-audio-classification) | `zero-shot-audio-classification` | Classifying audios into classes that are unseen during training.                                                              | ❌          |
| [Zero-Shot Image Classification](https://codewithkyrian.github.io/transformers-php/zero-shot-image-classification)                        | `zero-shot-image-classification` | Classifying images into classes that are unseen during training.                                                              | ✅          |
| [Zero-Shot Object Detection](https://codewithkyrian.github.io/transformers-php/zero-shot-object-detection)                                | `zero-shot-object-detection`     | Identify objects of classes that are unseen during training.                                                                  | ✅          |

#### Reinforcement Learning

| Task                                                                          | ID  | Description                                                                                                                                | Supported? |
|-------------------------------------------------------------------------------|-----|--------------------------------------------------------------------------------------------------------------------------------------------|------------|
| [Reinforcement Learning](https://huggingface.co/tasks/reinforcement-learning) | N/A | Learning from actions by interacting with an environment through trial and error and receiving rewards (negative or positive) as feedback. | ❌          |

### Models

1. **[ALBERT](https://huggingface.co/docs/transformers/model_doc/albert)** (from Google Research and the Toyota
   Technological Institute at Chicago) released with the
   paper [ALBERT: A Lite BERT for Self-supervised Learning of Language Representations](https://arxiv.org/abs/1909.11942),
   by Zhenzhong Lan, Mingda Chen, Sebastian Goodman, Kevin Gimpel, Piyush Sharma, Radu Soricut.
1. **[BART](https://huggingface.co/docs/transformers/model_doc/bart)** (from Facebook) released with the
   paper [BART: Denoising Sequence-to-Sequence Pre-training for Natural Language Generation, Translation, and Comprehension](https://arxiv.org/abs/1910.13461)
   by Mike Lewis, Yinhan Liu, Naman Goyal, Marjan Ghazvininejad, Abdelrahman Mohamed, Omer Levy, Ves Stoyanov and Luke
   Zettlemoyer.
1. **[BERT](https://huggingface.co/docs/transformers/model_doc/bert)** (from Google) released with the
   paper [BERT: Pre-training of Deep Bidirectional Transformers for Language Understanding](https://arxiv.org/abs/1810.04805)
   by Jacob Devlin, Ming-Wei Chang, Kenton Lee, and Kristina Toutanova.
1. **[BERT For Sequence Generation](https://huggingface.co/docs/transformers/model_doc/bert-generation)** (from Google)
   released with the
   paper [Leveraging Pre-trained Checkpoints for Sequence Generation Tasks](https://arxiv.org/abs/1907.12461) by Sascha
   Rothe, Shashi Narayan, Aliaksei Severyn.
1. **[BERTweet](https://huggingface.co/docs/transformers/model_doc/bertweet)** (from VinAI Research) released with the
   paper [BERTweet: A pre-trained language model for English Tweets](https://aclanthology.org/2020.emnlp-demos.2/) by
   Dat Quoc Nguyen, Thanh Vu and Anh Tuan Nguyen.
1. **[BigBird-Pegasus](https://huggingface.co/docs/transformers/model_doc/bigbird_pegasus)** (from Google Research)
   released with the paper [Big Bird: Transformers for Longer Sequences](https://arxiv.org/abs/2007.14062) by Manzil
   Zaheer, Guru Guruganesh, Avinava Dubey, Joshua Ainslie, Chris Alberti, Santiago Ontanon, Philip Pham, Anirudh Ravula,
   Qifan Wang, Li Yang, Amr Ahmed.
1. **[BigBird-RoBERTa](https://huggingface.co/docs/transformers/model_doc/big_bird)** (from Google Research) released
   with the paper [Big Bird: Transformers for Longer Sequences](https://arxiv.org/abs/2007.14062) by Manzil Zaheer, Guru
   Guruganesh, Avinava Dubey, Joshua Ainslie, Chris Alberti, Santiago Ontanon, Philip Pham, Anirudh Ravula, Qifan Wang,
   Li Yang, Amr Ahmed.
1. **[CLIP](https://huggingface.co/docs/transformers/model_doc/clip)** (from OpenAI) released with the
   paper [Learning Transferable Visual Models From Natural Language Supervision](https://arxiv.org/abs/2103.00020) by
   Alec Radford, Jong Wook Kim, Chris Hallacy, Aditya Ramesh, Gabriel Goh, Sandhini Agarwal, Girish Sastry, Amanda
   Askell, Pamela Mishkin, Jack Clark, Gretchen Krueger, Ilya Sutskever.
1. **[CodeGen](https://huggingface.co/docs/transformers/model_doc/codegen)** (from Salesforce) released with the
   paper [A Conversational Paradigm for Program Synthesis](https://arxiv.org/abs/2203.13474) by Erik Nijkamp, Bo Pang,
   Hiroaki Hayashi, Lifu Tu, Huan Wang, Yingbo Zhou, Silvio Savarese, Caiming Xiong.
1. **[ConvBERT](https://huggingface.co/docs/transformers/model_doc/convbert)** (from YituTech) released with the
   paper [ConvBERT: Improving BERT with Span-based Dynamic Convolution](https://arxiv.org/abs/2008.02496) by Zihang
   Jiang, Weihao Yu, Daquan Zhou, Yunpeng Chen, Jiashi Feng, Shuicheng Yan.
1. **[DeBERTa](https://huggingface.co/docs/transformers/model_doc/deberta)** (from Microsoft) released with the
   paper [DeBERTa: Decoding-enhanced BERT with Disentangled Attention](https://arxiv.org/abs/2006.03654) by Pengcheng
   He, Xiaodong Liu, Jianfeng Gao, Weizhu Chen.
1. **[DeBERTa-v2](https://huggingface.co/docs/transformers/model_doc/deberta-v2)** (from Microsoft) released with the
   paper [DeBERTa: Decoding-enhanced BERT with Disentangled Attention](https://arxiv.org/abs/2006.03654) by Pengcheng
   He, Xiaodong Liu, Jianfeng Gao, Weizhu Chen.
1. **[DETR](https://huggingface.co/docs/transformers/model_doc/detr)** (from Facebook) released with the
   paper [End-to-End Object Detection with Transformers](https://arxiv.org/abs/2005.12872) by Nicolas Carion, Francisco
   Massa, Gabriel Synnaeve, Nicolas Usunier, Alexander Kirillov, Sergey Zagoruyko.
1. **[DistilBERT](https://huggingface.co/docs/transformers/model_doc/distilbert)** (from HuggingFace), released together
   with the
   paper [DistilBERT, a distilled version of BERT: smaller, faster, cheaper and lighter](https://arxiv.org/abs/1910.01108)
   by Victor Sanh, Lysandre Debut and Thomas Wolf. The same method has been applied to compress GPT2
   into [DistilGPT2](https://github.com/huggingface/transformers/tree/main/examples/research_projects/distillation),
   RoBERTa
   into [DistilRoBERTa](https://github.com/huggingface/transformers/tree/main/examples/research_projects/distillation),
   Multilingual BERT
   into [DistilmBERT](https://github.com/huggingface/transformers/tree/main/examples/research_projects/distillation) and
   a German version of DistilBERT.
1. **[Donut](https://huggingface.co/docs/transformers/model_doc/donut)** (from NAVER), released together with the
   paper [OCR-free Document Understanding Transformer](https://arxiv.org/abs/2111.15664) by Geewook Kim, Teakgyu Hong,
   Moonbin Yim, Jeongyeon Nam, Jinyoung Park, Jinyeong Yim, Wonseok Hwang, Sangdoo Yun, Dongyoon Han, Seunghyun Park.
1. **[ELECTRA](https://huggingface.co/docs/transformers/model_doc/electra)** (from Google Research/Stanford University)
   released with the
   paper [ELECTRA: Pre-training text encoders as discriminators rather than generators](https://arxiv.org/abs/2003.10555)
   by Kevin Clark, Minh-Thang Luong, Quoc V. Le, Christopher D. Manning.
1. **[FLAN-T5](https://huggingface.co/docs/transformers/model_doc/flan-t5)** (from Google AI) released in the
   repository [google-research/t5x](https://github.com/google-research/t5x/blob/main/docs/models.md#flan-t5-checkpoints)
   by Hyung Won Chung, Le Hou, Shayne Longpre, Barret Zoph, Yi Tay, William Fedus, Eric Li, Xuezhi Wang, Mostafa
   Dehghani, Siddhartha Brahma, Albert Webson, Shixiang Shane Gu, Zhuyun Dai, Mirac Suzgun, Xinyun Chen, Aakanksha
   Chowdhery, Sharan Narang, Gaurav Mishra, Adams Yu, Vincent Zhao, Yanping Huang, Andrew Dai, Hongkun Yu, Slav Petrov,
   Ed H. Chi, Jeff Dean, Jacob Devlin, Adam Roberts, Denny Zhou, Quoc V. Le, and Jason Wei
1. **[GPT-2](https://huggingface.co/docs/transformers/model_doc/gpt2)** (from OpenAI) released with the
   paper [Language Models are Unsupervised Multitask Learners](https://blog.openai.com/better-language-models/) by Alec
   Radford*, Jeffrey Wu*, Rewon Child, David Luan, Dario Amodei** and Ilya Sutskever**.
1. **[GPT-J](https://huggingface.co/docs/transformers/model_doc/gptj)** (from EleutherAI) released in the
   repository [kingoflolz/mesh-transformer-jax](https://github.com/kingoflolz/mesh-transformer-jax/) by Ben Wang and
   Aran Komatsuzaki.
1. **[GPTBigCode](https://huggingface.co/docs/transformers/model_doc/gpt_bigcode)** (from BigCode) released with the
   paper [SantaCoder: don't reach for the stars!](https://arxiv.org/abs/2301.03988) by Loubna Ben Allal, Raymond Li,
   Denis Kocetkov, Chenghao Mou, Christopher Akiki, Carlos Munoz Ferrandis, Niklas Muennighoff, Mayank Mishra, Alex Gu,
   Manan Dey, Logesh Kumar Umapathi, Carolyn Jane Anderson, Yangtian Zi, Joel Lamy Poirier, Hailey Schoelkopf, Sergey
   Troshin, Dmitry Abulkhanov, Manuel Romero, Michael Lappert, Francesco De Toni, Bernardo García del Río, Qian Liu,
   Shamik Bose, Urvashi Bhattacharyya, Terry Yue Zhuo, Ian Yu, Paulo Villegas, Marco Zocca, Sourab Mangrulkar, David
   Lansky, Huu Nguyen, Danish Contractor, Luis Villa, Jia Li, Dzmitry Bahdanau, Yacine Jernite, Sean Hughes, Daniel
   Fried, Arjun Guha, Harm de Vries, Leandro von Werra.
1. **[M2M100](https://huggingface.co/docs/transformers/model_doc/m2m_100)** (from Facebook) released with the
   paper [Beyond English-Centric Multilingual Machine Translation](https://arxiv.org/abs/2010.11125) by Angela Fan,
   Shruti Bhosale, Holger Schwenk, Zhiyi Ma, Ahmed El-Kishky, Siddharth Goyal, Mandeep Baines, Onur Celebi, Guillaume
   Wenzek, Vishrav Chaudhary, Naman Goyal, Tom Birch, Vitaliy Liptchinsky, Sergey Edunov, Edouard Grave, Michael Auli,
   Armand Joulin.
1. **[MobileBERT](https://huggingface.co/docs/transformers/model_doc/mobilebert)** (from CMU/Google Brain) released with
   the paper [MobileBERT: a Compact Task-Agnostic BERT for Resource-Limited Devices](https://arxiv.org/abs/2004.02984)
   by Zhiqing Sun, Hongkun Yu, Xiaodan Song, Renjie Liu, Yiming Yang, and Denny Zhou.
1. **[OWL-ViT](https://huggingface.co/docs/transformers/model_doc/owlvit)** (from Google AI) released with the
   paper [Simple Open-Vocabulary Object Detection with Vision Transformers](https://arxiv.org/abs/2205.06230) by
   Matthias Minderer, Alexey Gritsenko, Austin Stone, Maxim Neumann, Dirk Weissenborn, Alexey Dosovitskiy, Aravindh
   Mahendran, Anurag Arnab, Mostafa Dehghani, Zhuoran Shen, Xiao Wang, Xiaohua Zhai, Thomas Kipf, and Neil Houlsby.
1. **[OWLv2](https://huggingface.co/docs/transformers/model_doc/owlv2)** (from Google AI) released with the
   paper [Scaling Open-Vocabulary Object Detection](https://arxiv.org/abs/2306.09683) by Matthias Minderer, Alexey
   Gritsenko, Neil Houlsby.
1. **[RoBERTa](https://huggingface.co/docs/transformers/model_doc/roberta)** (from Facebook), released together with the
   paper [RoBERTa: A Robustly Optimized BERT Pretraining Approach](https://arxiv.org/abs/1907.11692) by Yinhan Liu, Myle
   Ott, Naman Goyal, Jingfei Du, Mandar Joshi, Danqi Chen, Omer Levy, Mike Lewis, Luke Zettlemoyer, Veselin Stoyanov.
1. **[RoBERTa-PreLayerNorm](https://huggingface.co/docs/transformers/model_doc/roberta-prelayernorm)** (from Facebook)
   released with the paper [fairseq: A Fast, Extensible Toolkit for Sequence Modeling](https://arxiv.org/abs/1904.01038)
   by Myle Ott, Sergey Edunov, Alexei Baevski, Angela Fan, Sam Gross, Nathan Ng, David Grangier, Michael Auli.
1. **[RoFormer](https://huggingface.co/docs/transformers/model_doc/roformer)** (from ZhuiyiTechnology), released
   together with the
   paper [RoFormer: Enhanced Transformer with Rotary Position Embedding](https://arxiv.org/abs/2104.09864) by Jianlin Su
   and Yu Lu and Shengfeng Pan and Bo Wen and Yunfeng Liu.
1. **[SigLIP](https://huggingface.co/docs/transformers/main/model_doc/siglip)** (from Google AI) released with the
   paper [Sigmoid Loss for Language Image Pre-Training](https://arxiv.org/abs/2303.15343) by Xiaohua Zhai, Basil
   Mustafa, Alexander Kolesnikov, Lucas Beyer.
1. **[Swin2SR](https://huggingface.co/docs/transformers/model_doc/swin2sr)** (from University of Würzburg) released with
   the
   paper [Swin2SR: SwinV2 Transformer for Compressed Image Super-Resolution and Restoration](https://arxiv.org/abs/2209.11345)
   by Marcos V. Conde, Ui-Jin Choi, Maxime Burchi, Radu Timofte.
1. **[T5](https://huggingface.co/docs/transformers/model_doc/t5)** (from Google AI) released with the
   paper [Exploring the Limits of Transfer Learning with a Unified Text-to-Text Transformer](https://arxiv.org/abs/1910.10683)
   by Colin Raffel and Noam Shazeer and Adam Roberts and Katherine Lee and Sharan Narang and Michael Matena and Yanqi
   Zhou and Wei Li and Peter J. Liu.
1. **[T5v1.1](https://huggingface.co/docs/transformers/model_doc/t5v1.1)** (from Google AI) released in the
   repository [google-research/text-to-text-transfer-transformer](https://github.com/google-research/text-to-text-transfer-transformer/blob/main/released_checkpoints.md#t511)
   by Colin Raffel and Noam Shazeer and Adam Roberts and Katherine Lee and Sharan Narang and Michael Matena and Yanqi
   Zhou and Wei Li and Peter J. Liu.
1. **[TrOCR](https://huggingface.co/docs/transformers/model_doc/trocr)** (from Microsoft), released together with the
   paper [TrOCR: Transformer-based Optical Character Recognition with Pre-trained Models](https://arxiv.org/abs/2109.10282)
   by Minghao Li, Tengchao Lv, Lei Cui, Yijuan Lu, Dinei Florencio, Cha Zhang, Zhoujun Li, Furu Wei.
1. **[Vision Transformer (ViT)](https://huggingface.co/docs/transformers/model_doc/vit)** (from Google AI) released with
   the
   paper [An Image is Worth 16x16 Words: Transformers for Image Recognition at Scale](https://arxiv.org/abs/2010.11929)
   by Alexey Dosovitskiy, Lucas Beyer, Alexander Kolesnikov, Dirk Weissenborn, Xiaohua Zhai, Thomas Unterthiner, Mostafa
   Dehghani, Matthias Minderer, Georg Heigold, Sylvain Gelly, Jakob Uszkoreit, Neil Houlsby.
1. **[YOLOS](https://huggingface.co/docs/transformers/model_doc/yolos)** (from Huazhong University of Science &
   Technology) released with the
   paper [You Only Look at One Sequence: Rethinking Transformer in Vision through Object Detection](https://arxiv.org/abs/2106.00666)
   by Yuxin Fang, Bencheng Liao, Xinggang Wang, Jiemin Fang, Jiyang Qi, Rui Wu, Jianwei Niu, Wenyu Liu.
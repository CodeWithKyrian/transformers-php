<h1 align="center">
   Transformers PHP
</h1>

<h3 align="center">
    <p>State-of-the-art Machine Learning for PHP</p>
</h3>

Transformers PHP is designed to be functionally equivalent to the Python library, while still maintaining the same level
of performance and ease of use. This library is built on top of the Hugging Face's Transformers library, which provides
thousands of pre-trained models in 100+ languages. It is designed to be a simple and easy-to-use library for PHP
developers using a similar API to the Python library. These models can be used for a variety of tasks, including text
generation, summarization, translation, and more.

Transformers PHP uses [ONNX Runtime](https://onnxruntime.ai/) to run the models, which is a high-performance scoring
engine for Open Neural Network Exchange (ONNX) models. You can easily convert any PyTorch or TensorFlow model to ONNX
and use it with Transformers PHP using [🤗 Optimum](https://github.com/huggingface/optimum#onnx--onnx-runtime).

TO learn more about the library and how it works, head over to
our [extensive documentation](https://codewithkyrian.github.io/transformers-docs/docs).

## Quick tour

Because Transformers PHP is designed to be functionally equivalent to the Python library, it's super easy to learn from
existing Python or Javascript code. We provide the `pipeline` API, which is a high-level, easy-to-use API that groups
together a model with its necessary preprocessing and postprocessing steps.

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

Next, you must run the installation/initialize command to download the shared library necessary to run the ONNX models

```bash
./vendor/bin/transformers install
```

## Documentation

For more detailed information on how to use the library, check out the
documentation : [https://codewithkyrian.github.io/transformers-docs/](https://codewithkyrian.github.io/transformers-docs/)

## Usage

By default, Transformers PHP uses hosted pretrained ONNX models. For supported tasks, models that have been converted to
work with [Xenova's Transformers.js](https://huggingface.co/models?library=transformers.js) on HuggingFace should work
out of the box with Transformers PHP.

## Configuration

You can configure the behaviour of the Transformers PHP library as follows:

```php
use Codewithkyrian\Transformers\Transformers;

Transformers::configure()
    ->setCacheDir('...') // Set the default cache directory for transformers models. Defaults to `.transformers-cache/models`
    ->setRemoteHost('...') // Set the remote host for downloading models. Defaults to `https://huggingface.co`
    ->setRemotePathTemplate('...') // Set the remote path template for downloading models. Defaults to `{model}/resolve/{revision}/{file}`
    ->setAuthToken('...') // Set the auth token for downloading models. Defaults to `null`
    ->setUserAgent('...'); // Set the user agent for downloading models. Defaults to `transformers-php/{version}`
```

You can call the `set` methods in any order, or leave any out entirely, in which case, it uses the default values. For
more information on the configuration options and what they mean, checkout
the [documentation](https://codewithkyrian.github.io/transformers-docs/docs/configuration).

## Convert your models to ONNX

Transformers PHP only works with ONNX models, therefore, you must convert your PyTorch, TensorFlow or JAX models to
ONNX. It is recommended to use [🤗 Optimum](https://huggingface.co/docs/optimum) to perform the conversion and
quantization of your model.

## Pre-Download Models

By default, Transformers PHP automatically retrieves model weights (ONNX format) from the Hugging Face model hub when
you first use a pipeline or pretrained model. This can lead to a slight delay during the initial use. To improve the
user experience, it's recommended to pre-download the models you intend to use before running them in your PHP
application, especially for larger models. One way to do that is run the request once manually, but Transformers PHP
also comes with a command line tool to help you do just that:

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

This package is a WIP, but here's a list of tasks and architectures currently tested and supported by Transformers PHP.

### Tasks

#### Natural Language Processing

| Task                                                                                                   | ID                                            | Description                                                                                    | Supported? |
|--------------------------------------------------------------------------------------------------------|-----------------------------------------------|------------------------------------------------------------------------------------------------|------------|
| [Fill-Mask](https://huggingface.co/tasks/fill-mask)                                                    | `fill-mask`                                   | Masking some of the words in a sentence and predicting which words should replace those masks. | ✅          |
| [Question Answering](https://huggingface.co/tasks/question-answering)                                  | `question-answering`                          | Retrieve the answer to a question from a given text.                                           | ✅          |
| [Sentence Similarity](https://huggingface.co/tasks/sentence-similarity)                                | `sentence-similarity`                         | Determining how similar two texts are.                                                         | ✅          |
| [Summarization](https://huggingface.co/tasks/summarization)                                            | `summarization`                               | Producing a shorter version of a document while preserving its important information.          | ✅          |
| [Table Question Answering](https://huggingface.co/tasks/table-question-answering)                      | `table-question-answering`                    | Answering a question about information from a given table.                                     | ❌          |
| [Text Classification](https://huggingface.co/tasks/text-classification)                                | `text-classification` or `sentiment-analysis` | Assigning a label or class to a given text.                                                    | ✅          |
| [Text Generation](https://huggingface.co/tasks/text-generation#completion-generation-models)           | `text-generation`                             | Producing new text by predicting the next word in a sequence.                                  | ✅          |
| [Text-to-text Generation](https://huggingface.co/tasks/text-generation#text-to-text-generation-models) | `text2text-generation`                        | Converting one text sequence into another text sequence.                                       | ✅          |
| [Token Classification](https://huggingface.co/tasks/token-classification)                              | `token-classification` or `ner`               | Assigning a label to each token in a text.                                                     | ✅          |
| [Translation](https://huggingface.co/tasks/translation)                                                | `translation`                                 | Converting text from one language to another.                                                  | ✅          |
| [Zero-Shot Classification](https://huggingface.co/tasks/zero-shot-classification)                      | `zero-shot-classification`                    | Classifying text into classes that are unseen during training.                                 | ✅          |

#### Vision

| Task                                                                                          | ID                     | Description                                                                                                                                                                             | Supported? |
|-----------------------------------------------------------------------------------------------|------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| [Depth Estimation](https://huggingface.co/tasks/depth-estimation)                             | `depth-estimation`     | Predicting the depth of objects present in an image.                                                                                                                                    | ❌          |
| [Image Classification](https://huggingface.co/tasks/image-classification)                     | `image-classification` | Assigning a label or class to an entire image.                                                                                                                                          | ❌          |
| [Image Segmentation](https://huggingface.co/tasks/image-segmentation)                         | `image-segmentation`   | Divides an image into segments where each pixel is mapped to an object. This task has multiple variants such as instance segmentation, panoptic segmentation and semantic segmentation. | ❌          |
| [Image-to-Image](https://huggingface.co/tasks/image-to-image)                                 | `image-to-image`       | Transforming a source image to match the characteristics of a target image or a target image domain.                                                                                    | ❌          |
| [Mask Generation](https://huggingface.co/tasks/mask-generation)                               | `mask-generation`      | Generate masks for the objects in an image.                                                                                                                                             | ❌          |
| [Object Detection](https://huggingface.co/tasks/object-detection)                             | `object-detection`     | Identify objects of certain defined classes within an image.                                                                                                                            | ❌          |
| [Video Classification](https://huggingface.co/tasks/video-classification)                     | N/A                    | Assigning a label or class to an entire video.                                                                                                                                          | ❌          |
| [Unconditional Image Generation](https://huggingface.co/tasks/unconditional-image-generation) | N/A                    | Generating images with no condition in any context (like a prompt text or another image).                                                                                               | ❌          |

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
| [Feature Extraction](https://huggingface.co/tasks/feature-extraction)                                                                     | `feature-extraction`             | Transforming raw data into numerical features that can be processed while preserving the information in the original dataset. | ✅          |
| [Image-to-Text](https://huggingface.co/tasks/image-to-text)                                                                               | `image-to-text`                  | Output text from a given image.                                                                                               | ❌          |
| [Text-to-Image](https://huggingface.co/tasks/text-to-image)                                                                               | `text-to-image`                  | Generates images from input text.                                                                                             | ❌          |
| [Visual Question Answering](https://huggingface.co/tasks/visual-question-answering)                                                       | `visual-question-answering`      | Answering open-ended questions based on an image.                                                                             | ❌          |
| [Zero-Shot Audio Classification](https://huggingface.co/learn/audio-course/chapter4/classification_models#zero-shot-audio-classification) | `zero-shot-audio-classification` | Classifying audios into classes that are unseen during training.                                                              | ❌          |
| [Zero-Shot Image Classification](https://huggingface.co/tasks/zero-shot-image-classification)                                             | `zero-shot-image-classification` | Classifying images into classes that are unseen during training.                                                              | ❌          |
| [Zero-Shot Object Detection](https://huggingface.co/tasks/zero-shot-object-detection)                                                     | `zero-shot-object-detection`     | Identify objects of classes that are unseen during training.                                                                  | ❌          |

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
1. **[RoBERTa](https://huggingface.co/docs/transformers/model_doc/roberta)** (from Facebook), released together with the
   paper [RoBERTa: A Robustly Optimized BERT Pretraining Approach](https://arxiv.org/abs/1907.11692) by Yinhan Liu, Myle
   Ott, Naman Goyal, Jingfei Du, Mandar Joshi, Danqi Chen, Omer Levy, Mike Lewis, Luke Zettlemoyer, Veselin Stoyanov.
1. **[RoBERTa-PreLayerNorm](https://huggingface.co/docs/transformers/model_doc/roberta-prelayernorm)** (from Facebook)
   released with the paper [fairseq: A Fast, Extensible Toolkit for Sequence Modeling](https://arxiv.org/abs/1904.01038)
   by Myle Ott, Sergey Edunov, Alexei Baevski, Angela Fan, Sam Gross, Nathan Ng, David Grangier, Michael Auli.
1. **[T5](https://huggingface.co/docs/transformers/model_doc/t5)** (from Google AI) released with the
   paper [Exploring the Limits of Transfer Learning with a Unified Text-to-Text Transformer](https://arxiv.org/abs/1910.10683)
   by Colin Raffel and Noam Shazeer and Adam Roberts and Katherine Lee and Sharan Narang and Michael Matena and Yanqi
   Zhou and Wei Li and Peter J. Liu.
1. **[T5v1.1](https://huggingface.co/docs/transformers/model_doc/t5v1.1)** (from Google AI) released in the
   repository [google-research/text-to-text-transfer-transformer](https://github.com/google-research/text-to-text-transfer-transformer/blob/main/released_checkpoints.md#t511)
   by Colin Raffel and Noam Shazeer and Adam Roberts and Katherine Lee and Sharan Narang and Michael Matena and Yanqi
   Zhou and Wei Li and Peter J. Liu.

[//]: # (docker run  -e QUANTIZE=true -e MODEL_ID="sentence-transformers/paraphrase-albert-base-v2" -v ./models:/app/models onnx-converter )

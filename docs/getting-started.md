---
outline: deep
---

# Getting Started

## Prerequisites

Before installing TransformersPHP, ensure your system meets the following requirements:

- PHP 8.1 or above
- Composer
- PHP FFI extension
- JIT compilation (optional, for performance)
- Increased memory limit (for advanced tasks like text generation)
- OpenMP (optional, for multithreaded execution)

## Installation

You can install the library via Composer. This is the recommended way to install the library:

```bash
composer require codewithkyrian/transformers
```

All the shared libraries required by TransformersPHP will be installed automatically. If it fails for some reason, you
can install them manually using the following command:

```bash
./vendor/bin/transformers install
```

> [!CAUTION]
> The shared libraries is platform-specific, so it's important to run the `composer require`, or `transformers install`
> command on the target platform where the code will be executed. In most cases, this will be your development machine
> or a server where you deploy your application, but if you're using a Docker container, run the `composer require`
> command inside that container.

That's it! You're now ready to use TransformersPHP in your PHP application.

## Pre-Download Models

By default, TransformersPHP automatically retrieves model weights (ONNX format) from the Hugging Face model hub when
you first use a pipeline or pretrained model. To save time and enhance the user experience, it's a good idea to download
the ONNX model weights ahead of time, especially for larger models. TransformersPHP includes a command-line tool to
facilitate this:

```bash
./vendor/bin/transformers download <model_name_or_path> [<task>] [options]

```

For example, to download the `Xenova/bert-base-uncased` model, you can run:

```bash
./vendor/bin/transformers download Xenova/bert-base-uncased
```

Arguments:

- `model_name_or_path` (required): The name or path of the model to download. You can find identifiers on the [Hugging
  Face model hub](https://huggingface.co/models?library=transformers.js). The Hub is a repository of pre-trained models
  and works like GitHub for machine learning models. The model identifier is the name of the model or the path to the
  model on the Hub, including the organization or username. For example, `Xenova/bert-base-uncased`.
- `[<task>]` (optional): If you're planning to use the model for a specific task (like "text2text-generation"), you
  can
  specify it here. This downloads any additional configuration or data needed for that task.
- `[options]` (optional): Additional options to customize the download process.
    - `--cache-dir=<directory>`: Choose where to save the models. If you've got a preferred storage spot, mention it
      here. Otherwise, it goes to the default cache location. You can use the shorthand `-c` instead of `--cache_dir`.
    - `--quantized=<true|false>`: Decide whether you want the quantized version of the model, which is smaller and
      faster. The default is true, but if for some reason you prefer the full version, you can set this to false. You
      can use the shorthand `-q` instead of `--quantized`. Example: `--quantized=false`, `-q false`.
    - `--model-filename=<filename>`: Specify the exact model filename to download (without the `.onnx` suffix. Eg. "
      model" or "model_quantized".

The `download` command will download the model weights and save them to the cache directory. The next time you use the
model, TransformersPHP will use the cached weights instead of downloading them again.

> [!CAUTION]
> Remember to add your cache directory to your `.gitignore` file to avoid committing the downloaded models to your git
> repository.

## Use Custom Models

Since TransformersPHP operates exclusively with ONNX models, you'll need to convert any machine learning models you've
developed or plan to use from PyTorch, TensorFlow, or JAX into the ONNX format.

For this conversion process, we recommend using
the [conversion script](https://github.com/xenova/transformers.js/blob/main/scripts/convert.py)
provided by the Transformers.js project. This script is designed to convert models from PyTorch, TensorFlow, and JAX to
ONNX format, and most importantly, outputs it in a folder structure that is compatible with TransformersPHP. Behind the
scenes, the script uses [🤗 Optimum](https://huggingface.co/docs/optimum) from Hugging Face to convert and quantize the
models.

But let's be real, not all PHP developer are fans of Python, or even have a Python environment set up. And that's okay.
To
simplify the process, thanks to this PR [(#610)](https://github.com/xenova/transformers.js/pull/610), we've provided a
Jupyter notebook that is built on top that script. This notebook simplifies the conversion process by offering a
user-friendly interface within a Jupyter Notebook environment.

The steps for conversion are simple:

- Open the Jupyter Notebook:  Open
  the [notebook](https://github.com/CodeWithKyrian/transformers-php/blob/main/scripts/convert_upload_hf.ipynb) in any
  Jupyter environment, such as
  JupyterLab or [Google Colab](https://colab.research.google.com/drive/1_i-vSzfOfAkGA4ZeThIOzcROAnh2M918?usp=sharing).
- Set the `HF_TRANSFER` environment variable.
- Set the Parameters: Configure the notebook with details like the model identifier (found on the Hugging Face Model
  Hub: https://huggingface.co/models) and your huggingface username.
- Run the Notebook: Execute the notebook cells to initiate the conversion process.
- Upload to Hugging Face (Optional): If desired, the notebook can assist you in uploading the converted model to your
  Hugging Face account for sharing and storage.

Whether you convert using the script, or the noteboook, or using TensorFlow's `tf.saved_model` or
PyTorch's `torch.onnx.export`, just make sure the folder structure of the output is compatible with TransformersPHP.
The script and the DOcker image already handle this for you.

The folder structure should look like this:

```plaintext
model_name_or_path/
├── config.json
├── tokenizer.json
|── tokenizer_config.json
└── onnx/
    ├── model.onnx
    └── model_quantized.onnx
```

Where:

- `model_name_or_path` is the name or path of the model you converted e.g. `bert-base-uncased`
- `config.json` is the model configuration file
- `tokenizer.json` is the tokenizer file
- `tokenizer_config.json` is the tokenizer configuration file
- `model.onnx` is the original ONNX model
- `model_quantized.onnx` is the quantized ONNX model

For the full list of supported architectures, see
the [Optimum documentation.](https://huggingface.co/docs/optimum/main/en/exporters/onnx/overview)

## PHP FFI Extension

TransformersPHP uses the PHP FFI extension to interact with the ONNX runtime. The FFI extension is included by default
in PHP 7.4 and later, but it may not be enabled by default. To check if the FFI extension is enabled, run the following
command:

```bash
php -m | grep FFI
```

If the FFI extension is not enabled, you can enable it by uncommenting(remove the `;` from the beginning of the line)
the
following line in your `php.ini` file:

```ini
extension = ffi
```

TransformersPHP does not support FFI preloading yet, so you need to enable the `ffi.enable` directive in your `php.ini`

```ini
ffi.enable = true
```

After making these changes, restart your web server or PHP-FPM service, and you should be good to go.

## JIT Compilation (Optional)

Just-In-Time (JIT) compilation is a feature that allows PHP to compile and execute code at runtime. JIT compilation can
improve the performance of your application by compiling frequently executed code paths into machine code. While you
can use TransformersPHP without JIT compilation, enabling it can provide a significant performance boost (> 2x in some
cases) since there are many matrix multiplications and other mathematical operations involved in running ONNX models.

JIT compilation is available in PHP 8.0 and later, but it may not be enabled by default. To enable JIT compilation,
change the `opcache.jit` directive in your `php.ini` file:

```ini
opcache.jit = tracing
```

Here's a deeper guide by [Brent](https://twitter.com/brendt_gd) on how to configure JIT
compilation: [https://stitcher.io/blog/php-8-jit-setup](https://stitcher.io/blog/php-8-jit-setup)

## Memory Limit

While TransformersPHP is designed to be memory-efficient, not all models are created equal. Some models require more
memory than others, especially when performing advanced tasks like text generation. To avoid memory-related issues, we
recommend increasing the memory limit in your `php.ini` file:

```ini
memory_limit = 512M
```

You can adjust the memory limit based on your system's capabilities and the models you plan to use.

## OpenMP (Optional)

OpenMP is a set of compiler directives and library routines that enable parallel processing in C, C++, and Fortran
programs. TransformersPHP uses OpenMP to enable multithreaded operations in the Tensors, which can improve performance
on multi-core systems. OpenMP is not required, but it can provide a significant performance boost for some operations.
Checkout the [OpenMP website](https://www.openmp.org/) for more information on how to install and configure OpenMP on
your system.

Example: On Ubuntu, you can install OpenMP using the following command:

```bash
sudo apt-get install libomp-dev
```

And for MacOS, you can install OpenMP using Homebrew:

```bash
brew install libomp
```

If OpenMP is not available on your system, TransformersPHP will fall back to single-threaded execution.



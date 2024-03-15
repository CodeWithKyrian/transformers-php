# Changelog

All notable changes to `transformers-php` will be documented in this file.

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

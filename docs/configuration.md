---
outline: deep
---

# Configuration

You can configure TransformersPHP for your specific use case. This page provides an overview of the available
configuration options.

## Overview

Configuring TransformersPHP involves setting parameters such as the cache directory, the remote host for downloading
models, and the remote path template. These settings allow you to tailor how and where models are stored and retrieved.

```php
use Codewithkyrian\Transformers\Transformers;
use Codewithkyrian\Transformers\Utils\ImageDriver;

Transformers::setup()
        ->setCacheDir('/path/to/models')
        ->setRemoteHost('https://yourmodelshost.com')
        ->setRemotePathTemplate('custom/path/{model}/{file}')
        ->setAuthToken('your-token')
        ->setUserAgent('your-user-agent')
        ->setImageDriver(ImageDriver::IMAGICK)
        ->setLogger(new StreamLogger('transformers-php'));
```

::: tip
You can leave out any of the `set*()` methods if you don't need to change the default settings for that field, or
leave out the entire setup if you don't want to change any of the default setting for all fields.
:::

## Configuration Options

### `setCacheDir(?string $cacheDir)`

The cache directory is where TransformersPHP stores the downloaded ONNX models. By default, this is set to
the `.transformers-cache/models` directory from the root of your project. Please ensure this directory is writable by
your application.

### `setRemoteHost(string $remoteHost)`

The remote host defines where TransformersPHP looks to download model files. The default host
is https://huggingface.co, which is where Hugging Face hosts its models. If you host your models on a different server
or use a private repository for models, you can set this to the base URL of that server.

For instance, if you have a private server hosting your models at https://models.example.com, you could set it like
this:

```php
Transformers::setup()
    ->setRemoteHost('https://models.example.com');
```

This setting is particularly useful when you need to comply with data governance policies or want to speed up model
downloads by hosting them closer to your application infrastructure.

### `setRemotePathTemplate(string $remotePathTemplate)`

The remote path template allows you to customize the URL path used to fetch models from the remote host. By default, it
uses a template resembling Hugging Face's (git-like) URL structure: `{model}/resolve/{revision}/{file}`.

Suppose your models are hosted on a custom server without the `resolve/{revision}` part of the path and your models are
directly accessible under a models directory, identified by their name and the file name. You might configure the path
template like this:

```php
Transformers::setup()
    ->setRemotePathTemplate('models/{model}/{file}')
```

In this example, accessing a model named `bert-base-uncased` would result in a URL
like https://models.example.com/models/bert-base-uncased/model.onnx, assuming model.onnx is the file you're fetching.

### `setAuthToken(string $authToken)`

The auth token is used to authenticate requests to the remote host. If your models are hosted on a private server or
private repository on Hugging Face, you can set an authentication token to access them.

```php
Transformers::setup()->setAuthToken('your-token');
```

### `setUserAgent(string $userAgent)`

The user agent is used to identify your application when making requests to the remote host. By default, Transformers
PHP uses a user agent string that identifies the library and its version. You can set a custom user agent string to
identify your application when making requests.

```php
Transformers::setup()->setUserAgent('your-user-agent');
```

### `setImageDriver(ImageDriver $imageDriver)`

This setting allows you to specify the image backend to use for image processing tasks. By default, the image driver is
not set and an error will be thrown if you try to perform any image related task. You can change this to `IMAGICK`, `GD`
or `VIPS` if you prefer, just make sure to have the required extensions installed.

```php
use Codewithkyrian\Transformers\Utils\ImageDriver;

Transformers::setup()
    ->setImageDriver(ImageDriver::GD)
    ->apply();
```

### `setLogger(LoggerInterface $logger)`

This setting allows you to set a custom logger for TransformersPHP. No logger is set by default, but you can set a
logger to debug TransformersPHP's internal behavior. The logger should implement the `Psr\Log\LoggerInterface` interface. TransformersPHP
comes with a `StreamLogger` class, similar to Monolog's `StreamHandler`, which can be used to log to a stream (STDOUT, STDERR,
or a file) and can be customized to log at different levels (debug, info, warning, error, critical). You can also pass in a 
logger that is already configured and ready to use e.g. a Laravel logger.

## Standalone PHP Projects

In a standalone PHP project, the best place to add global configuration is in your project's bootstrap or initialization
script. This script should run before any feature utilizing the TransformersPHP library is called.

::: code-group

```php [bootstrap.php]
require_once 'vendor/autoload.php';

use Codewithkyrian\Transformers\Transformers;

 Transformers::setup()
        ->setCacheDir('/path/to/models')
        ->setRemoteHost('https://yourmodelshost.com')
        ->setRemotePathTemplate('custom/path/{model}/{file}')
        ->setAuthToken('your-token')
        ->setUserAgent('your-user-agent')
        ->apply();
```

:::

## Laravel Projects

In a Laravel project, you can add global configuration in the `AppServiceProvider` class. Laravel service providers are
excellent locations for bootstrap code, making them the best place to set up global configurations. It's recommended to
set the cache directory to the subdirectory of the `storage` directory, as it's writable and not publicly accessible.

::: code-group

```php [AppServiceProvider.php]
use Codewithkyrian\Transformers\Transformers;

public function boot()
{
    Transformers::setup()
        ->setCacheDir(storage_path('app/transformers'))
        ->setRemoteHost('https://yourmodelshost.com')
        ->setRemotePathTemplate('custom/path/{model}/{file}')
        ->setAuthToken('your-token')
        ->setUserAgent('your-user-agent')
        ->apply();
}
```

:::

## Symfony Projects

In a Symfony project, you can add global configuration in the `Kernel` class. This class is loaded before any other
services, making it a good place to set up global configurations.

::: code-group

```php [Kernel.php]
use Codewithkyrian\Transformers\Transformers;

public function boot()
{
    Transformers::setup()
        ->setCacheDir('/path/to/models')
        ->setRemoteHost('https://yourmodelshost.com')
        ->setRemotePathTemplate('custom/path/{model}/{file}')
        ->setAuthToken('your-token')
        ->setUserAgent('your-user-agent')
        ->apply();
}
```

:::

## Next Steps

Now that you've learned how to configure TransformersPHP, you can start using the library to download and use
pre-trained ONNX models. For more information on how to use the library, check out
the [Getting Started](getting-started.md) guide.

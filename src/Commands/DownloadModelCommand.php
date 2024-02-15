<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Commands;

use Codewithkyrian\Transformers\Models\AutoModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'download-model',
    description: 'Download a pre-trained model from Hugging Face.',
    aliases: ['download']
)]
class DownloadModelCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp('This command downloads a pre-trained model from Hugging Face.');

        $this->addArgument('model', InputArgument::REQUIRED, 'The model to download.');

//        $this->addArgument('task', InputArgument::REQUIRED, 'The task to use the model for.');

        $this->addOption(
            'cache-dir',
            'c',
            InputOption::VALUE_OPTIONAL,
            'The directory to cache the model in.',
            '.cache'
        );

        $this->addOption(
            'quantized',
            null,
            InputOption::VALUE_OPTIONAL,
            'Whether to download the quantized version of the model.',
            true
        );

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('✔ Downloading model...');

        $model = $input->getArgument('model');
//        $task = $input->getArgument('task');
        $cacheDir = $input->getOption('cache-dir');
        $quantized = $input->getOption('quantized');

        // Download the model
        try {
            $model = AutoModel::fromPretrained(
                modelNameOrPath: $model,
//                task: $task,
                quantized: $quantized,
                cacheDir: $cacheDir
            );

            $output->writeln('✔ Model downloaded successfully.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('An error occurred while downloading the model: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
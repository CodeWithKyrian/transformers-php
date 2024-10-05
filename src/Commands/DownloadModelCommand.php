<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Commands;

use Codewithkyrian\Transformers\Models\Auto\AutoModel;
use Codewithkyrian\Transformers\Pipelines\Task;
use Codewithkyrian\Transformers\PreTrainedTokenizers\AutoTokenizer;
use Codewithkyrian\Transformers\Transformers;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use function Codewithkyrian\Transformers\Pipelines\pipeline;

#[AsCommand(
    name: 'download-model',
    description: 'Download a pre-trained model from Hugging Face.',
    aliases: ['download']
)]
class DownloadModelCommand extends Command
{
    protected array $progressBars = [];

    protected function configure(): void
    {
        $this->setHelp('This command downloads a pre-trained model from Hugging Face.');

        $this->addArgument('model', InputArgument::REQUIRED, 'The model to download.');

        $this->addArgument('task', InputArgument::OPTIONAL, 'The task to use the model for.');

        $this->addOption(
            'cache-dir',
            'c',
            InputOption::VALUE_OPTIONAL,
            'The directory to cache the model in.'
        );

        $this->addOption(
            'quantized',
            null,
            InputOption::VALUE_OPTIONAL,
            'Whether to download the quantized version of the model.',
            true
        );

        $this->addOption(
            'model-filename',
            null,
            InputOption::VALUE_OPTIONAL,
            'The filename of the exact model weights version to download.',
            null
        );

        $this->addOption(
            'host',
            null,
            InputOption::VALUE_OPTIONAL,
            'The host to download the model from.',
            null
        );

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('✔ Initializing download...');

        $model = $input->getArgument('model');
        $cacheDir = $input->getOption('cache-dir');
        $quantized = filter_var($input->getOption('quantized'), FILTER_VALIDATE_BOOLEAN);
        $task = $input->getArgument('task');
        $modelFilename = $input->getOption('model-filename');
        $host = $input->getOption('host');

        $transformers = Transformers::setup();

        if ($cacheDir != null) $transformers->setCacheDir($cacheDir);
        if ($host != null) $transformers->setRemoteHost($host);

        try {
            $task = $task ? Task::tryFrom($task) : null;

            $onProgress = function ($type, $filename, $downloadSize, $downloaded) use ($output) {
                if ($type === 'advance_download') {
                    $progressBar = $this->getProgressBar($filename, $output);
                    $percent = round(($downloaded / $downloadSize) * 100, 2);
                    $progressBar->setProgress((int)$percent);
                } elseif ($type === 'complete_download') {
                    $progressBar = $this->getProgressBar($filename, $output);
                    $progressBar->finish();
                    $progressBar->clear();
                    $output->writeln("✔ Downloaded <info>$filename</info>");
                }
            };

            if ($task != null) {
                pipeline($task, $model, quantized: $quantized, modelFilename: $modelFilename, onProgress: $onProgress);
            } else {
                AutoTokenizer::fromPretrained($model, onProgress: $onProgress);
                AutoModel::fromPretrained($model, $quantized, modelFilename: $modelFilename, onProgress: $onProgress);
            }

            $output->writeln('✔ Model files downloaded successfully.');

            $random = random_int(1, 100);
            if ($random <= 30) $this->askToStar($input, $output);

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln("<error>✘ {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }

    protected function getProgressBar(string $filename, OutputInterface $output): ProgressBar
    {
        ProgressBar::setFormatDefinition('hub', '✔ Downloading <info>%message%</info> : [%bar%] %percent:3s%%');

        if (!isset($this->progressBars[$filename])) {
            $progressBar = new ProgressBar($output, 100);
            $progressBar->setFormat('hub');
            $progressBar->setBarCharacter('<fg=green>•</>');
            $progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
            $progressBar->setProgressCharacter('<fg=green>➤</>');
            $progressBar->setMessage($filename);
            $this->progressBars[$filename] = $progressBar;
        }

        return $this->progressBars[$filename];
    }

    protected function askToStar(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getOption('no-interaction')) {
            return;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('? Would you like to show some love by starring the Transformers repo on GitHub? ', true);

        if ($helper->ask($input, $output, $question)) {
            if (PHP_OS_FAMILY === 'Darwin') {
                exec('open https://github.com/CodeWithKyrian/transformers-php');
            }
            if (PHP_OS_FAMILY === 'Linux') {
                exec('xdg-open https://github.com/CodeWithKyrian/transformers-php');
            }
            if (PHP_OS_FAMILY === 'Windows') {
                exec('start https://github.com/CodeWithKyrian/transformers-php');
            }

            $output->writeln('✔ Thank you!');
        } else {
            $output->writeln('✔ That\'s okay. You can always star the repo later.');
        }
    }
}

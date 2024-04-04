<?php

declare(strict_types=1);


namespace Codewithkyrian\Transformers\Commands;

use Codewithkyrian\Transformers\Models\Auto\AutoModel;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForCausalLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForSeq2SeqLM;
use Codewithkyrian\Transformers\Models\Auto\AutoModelForSequenceClassification;
use Codewithkyrian\Transformers\Pipelines\Task;
use Codewithkyrian\Transformers\PretrainedTokenizers\AutoTokenizer;
use Codewithkyrian\Transformers\Transformers;
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

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('✔ Initializing download...');

        $model = $input->getArgument('model');
        $cacheDir = $input->getOption('cache-dir');
        $quantized = $input->getOption('quantized');
        $task = $input->getArgument('task');

        Transformers::setup()
            ->setCacheDir($cacheDir)
            ->apply();

        try {
            $task = $task ? Task::tryFrom($task) : null;

            if ($task != null) {
                pipeline($task, $model, output: $output);
            } else {
                AutoTokenizer::fromPretrained($model, quantized: $quantized, output: $output);
                AutoModel::fromPretrained($model, $quantized, output: $output);
            }


            $output->writeln('✔ Model files downloaded successfully.');

            $this->askToStar($input, $output);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('✘ '. $e->getMessage());
            return Command::FAILURE;
        }
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
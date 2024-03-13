<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Commands;

use Codewithkyrian\Transformers\Transformers;
use OnnxRuntime\Exception;
use OnnxRuntime\Vendor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use function Codewithkyrian\Transformers\Utils\ensureDirectory;
use function Codewithkyrian\Transformers\Utils\joinPaths;

#[AsCommand(
    name: 'init',
    description: 'Initialize Transformers PHP and downloads the required shared libraries.',
    aliases: ['initialize', 'install']
)]
class InitCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp('This command initializes Transformers PHP and downloads the required shared libraries.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            if (file_exists(Transformers::libFile())) {
                $output->writeln("✔ Transformer has been formerly initialized");

                return Command::SUCCESS;
            }

            ensureDirectory(Transformers::$cacheDir);

            echo "◌ Downloading ONNX Runtime...\n";

            $file = Transformers::platform('file');
            $ext = Transformers::platform('ext');

            $urlTemplate = "https://github.com/microsoft/onnxruntime/releases/download/v{{version}}/$file.$ext";
            $url = str_replace('{{version}}', Transformers::ONNX_VERSION, $urlTemplate);

            $contents = @file_get_contents($url);

            if (!$contents) {
                throw new \Exception("Something went wrong");
            }

            $checksum = hash('sha256', $contents);
            if ($checksum != Transformers::platform('checksum')) {
                throw new Exception("Bad checksum: $checksum");
            }

            $tempDest = tempnam(sys_get_temp_dir(), 'onnxruntime') . '.' . $ext;

            file_put_contents($tempDest, $contents);

            $archive = new \PharData($tempDest);
            if ($ext != 'zip') {
                $archive = $archive->decompress();
            }

            $archive->extractTo(Transformers::$cacheDir);

            $output->writeln('✔ Initialized Transformers successfully.');

            $this->askToStar($input, $output);

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }
    }


    protected function askToStar(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getOption('no-interaction')) {
            return;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('? All done! Would you like to show some love by starring the Transformers repo on GitHub? ', true);

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
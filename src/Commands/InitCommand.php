<?php

declare(strict_types=1);

namespace Codewithkyrian\Transformers\Commands;

use Codewithkyrian\Transformers\Transformers;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use OnnxRuntime\Exception;
use OnnxRuntime\Vendor;
use PharData;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

        $this->addOption(
            'cache-dir',
            'c',
            InputOption::VALUE_OPTIONAL,
            'The directory to cache the libraries in.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheDir = $input->getOption('cache-dir');

        if ($cacheDir != null) {
            Transformers::$cacheDir = $cacheDir;
        }

        try {
            if (file_exists(Transformers::libFile())) {
                $output->writeln("✔ Transformer has been formerly initialized");

                return Command::SUCCESS;
            }

            ensureDirectory(Transformers::$cacheDir);

            echo "✔ Initializing Transformers...\n";

            $file = Transformers::platform('file');
            $ext = Transformers::platform('ext');

            $urlTemplate = "https://github.com/microsoft/onnxruntime/releases/download/v{{version}}/$file.$ext";
            $url = str_replace('{{version}}', Vendor::VERSION, $urlTemplate);

            $client = new Client();
            $tempDest = tempnam(sys_get_temp_dir(), 'onnxruntime') . '.' . $ext;

            ProgressBar::setFormatDefinition('hub', '%filename% : [%bar%] %percent:3s%%');

            $progressBar = new ProgressBar($output, 100);
            $progressBar->setFormat('hub');
            $progressBar->setBarCharacter('<fg=green>•</>');
            $progressBar->setEmptyBarCharacter("<fg=red>⚬</>");
            $progressBar->setProgressCharacter('<fg=green>➤</>');
            $progressBar->setMessage("✔ Downloading Libraries", 'filename');

            $client->get($url, ['sink' => $tempDest, 'progress' => self::onProgress($progressBar)]);

            $contents = @file_get_contents($tempDest);

            $checksum = hash('sha256', $contents);

            if ($checksum != Transformers::platform('checksum')) {
                throw new Exception("Bad checksum: $checksum");
            }

            $archive = new PharData($tempDest);

            if ($ext != 'zip') {
                $archive = $archive->decompress();
            }

            $archive->extractTo(Transformers::$cacheDir);

            echo "\n"; // New line to since Symphony ProgressBar doesn't add a new line.
            $output->writeln('✔ Initialized Transformers successfully.');

            $this->askToStar($input, $output);

            return Command::SUCCESS;
        } catch (GuzzleException $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        } catch (Exception $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @param resource $stream
     * @return string
     */
    public function calculateHash($stream): string
    {
        $ctx = hash_init('sha256');

        while (!feof($stream)) {
            $buffer = fread($stream, 8192); // Read in 8KB chunks
            hash_update($ctx, $buffer);
        }

        $hash = hash_final($ctx);
        fclose($stream);

        return $hash;
    }

    private static function onProgress(ProgressBar $progressBar): callable
    {
        return function ($totalDownload, $downloadedBytes) use ($progressBar) {
            if ($totalDownload == 0) return;

            $percent = round(($downloadedBytes / $totalDownload) * 100, 2);
            $progressBar->setProgress((int)$percent);
        };
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
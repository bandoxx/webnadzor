<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:generate-image',
    description: 'Generate Chart image for export pdf',
)]
class GenerateImageCommand extends Command
{
    protected static $defaultName = 'app:generate-image';
    protected static $defaultDescription = 'Generates an image using the Highcharts export server.';

    protected function configure(): void
    {
        $this
            ->addArgument('inputFile', InputArgument::REQUIRED, 'Path to the input JSON file (e.g., my-chart-config.json)')
            ->addArgument('outputFile', InputArgument::REQUIRED, 'Path to the output image file (e.g., /path/to/output.jpg)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputFile = $input->getArgument('inputFile');
        $outputFile = $input->getArgument('outputFile');

        $command = [
            'highcharts-export-server',
            '--infile', $inputFile,
            '--outfile', $outputFile,
            '--logLevel', 2,
            '--logDest', 'var/log',
            '--type jpg',
            '--width 1200',
            '--height 800',
            '--scale', '2'
        ];

        $process = new Process($command);
        $process->setTimeout(120);

        try {
            $process->mustRun();
            $output->writeln("<info>Image generated successfully:</info> $outputFile");

            return Command::SUCCESS;
        } catch (ProcessFailedException $exception) {
            $output->writeln("<error>Failed to generate image:</error>");
            $output->writeln($exception->getMessage());

            return Command::FAILURE;
        }
    }
}

<?php

namespace Lochmueller\CustomDatabaseExport\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
    public function __construct(string $name = null)
    {
        parent::__construct('init');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $baseFile = dirname(__DIR__, 2) . '/res/custom-database-export.yaml.dist';
        $baseContent = file_get_contents($baseFile);
        $targetFile = 'custom-database-export.yaml';

        if (is_file($targetFile)) {
            $output->writeln('Target file already exists!');
            return Command::INVALID;
        }

        $output->writeln('Copy base configuration file to ' . $targetFile);
        $output->writeln('Please check the configuration (incl. documentation) and adapt the file for your needs.');

        return file_put_contents($targetFile, $baseContent) !== false ? Command::SUCCESS : Command::FAILURE;
    }

}

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
        $targetFile = 'custom-database-export.yaml';

        $output->writeln('Copy ' . $baseFile . ' to ' . $targetFile);

        return Command::SUCCESS;
    }

}
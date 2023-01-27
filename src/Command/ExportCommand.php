<?php

namespace Lochmueller\CustomDatabaseExport\Command;

use Druidfi\Mysqldump\Mysqldump;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;


class ExportCommand extends Command
{
    public function __construct(string $name = null)
    {
        parent::__construct('export');
    }

    protected function configure(): void
    {
        $this
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Configuration file', 'custom-database-export.yaml');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $configurationFile = $input->getOption('configuration');

        try {
            $configuration = Yaml::parseFile($configurationFile)['custom-database-export'];
        } catch (\Exception $exception) {
            $output->writeln('Errors by parsing configuration files "' . $configurationFile . '": ' . $exception->getMessage());
            return Command::INVALID;
        }

        var_dump($this->addCredentials($configuration, []));

        if (!($configuration['structure']['skip'] ?? false)) {
            $output->writeln('Start DB structure to structure.sql');

            // Structure
            $settings = [
                'no-data' => true,
                'add-drop-table' => true,
                'include-tables' => $configuration['structure']['tableInclude'] ?? [],
                'exclude-tables' => $configuration['structure']['tableExclude'] ?? [],
            ];

            try {
                $dumper = new Mysqldump(...$this->addCredentials($configuration, $settings));
                $dumper->start('structure.sql');
            } catch (\Exception $exception) {
                $output->writeln('Could not create structure file: ' . $exception->getMessage());
                return Command::FAILURE;
            }
        }

        if (!($configuration['data']['skip'] ?? false)) {
            $output->writeln('Start DB structure to data.sql');

            // Data
            $settings = [
                'no-create-info' => true,
                'include-tables' => $configuration['data']['tableInclude'] ?? [],
                'exclude-tables' => $configuration['data']['tableExclude'] ?? [],
            ];


            var_dump($configuration['data']);
            // where
            #$dumper->setTableWheres([
            #    'users' => 'date_registered > NOW() - INTERVAL 3 MONTH AND deleted=0',
            #    'logs' => 'date_logged > NOW() - INTERVAL 1 DAY',
            #    'posts' => 'isLive=1'
            #]);


            try {
                $dumper = new Mysqldump(...$this->addCredentials($configuration, $settings));
                $dumper->start('data.sql');
            } catch (\Exception $exception) {
                $output->writeln('Could not create data file: ' . $exception->getMessage());
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    protected function addCredentials(array $configuration, array $settings): array
    {
        return [
            'dsn' => 'mysql:host=' . $this->resolveValue($configuration['connection']['host'] ?? '') . ';dbname=' . $this->resolveValue($configuration['connection']['dbname'] ?? ''),
            'user' => $this->resolveValue($configuration['connection']['username'] ?? ''),
            'pass' => $this->resolveValue($configuration['connection']['password'] ?? ''),
            'settings' => $settings,
        ];
    }

    protected function resolveValue(string $value): string
    {
        if (str_starts_with($value, 'ENV:')) {
            return (string) getenv(substr($value, 4));
        }
        return $value;
    }

}

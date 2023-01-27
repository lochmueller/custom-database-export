<?php

namespace Lochmueller\CustomDatabaseExport\Command;

use Druidfi\Mysqldump\Compress\CompressManagerFactory;
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

        $targetFilename = $configuration['target']['fileName'] ?? 'php://stdout';
        $exportFiles = [];

        if (!($configuration['structure']['skip'] ?? false)) {

            // Structure
            $settings = [
                'no-data' => true,
                'add-drop-table' => true,
                'compress' => $configuration['target']['compress'] ?? 'None',
                'include-tables' => $configuration['structure']['tableInclude'] ?? [],
                'exclude-tables' => $configuration['structure']['tableExclude'] ?? [],
            ];

            try {
                $dumper = new Mysqldump(...$this->addCredentials($configuration, $settings));

                if ($targetFilename === 'php://stdout') {
                    $dumper->start($targetFilename);
                } else {
                    $dumper->start($targetFilename . 'structure.sql');
                    $exportFiles[] = $targetFilename . '.structure.sql';
                }
            } catch (\Exception $exception) {
                $output->writeln('Could not create structure file: ' . $exception->getMessage());
                return Command::FAILURE;
            }
        }

        if (!($configuration['data']['skip'] ?? false)) {
            // Data
            $settings = [
                'no-create-info' => true,
                'compress' => $configuration['target']['compress'] ?? 'None',
                'include-tables' => $configuration['data']['tableInclude'] ?? [],
                'exclude-tables' => array_merge($configuration['structure']['tableExclude'] ?? [], $configuration['data']['tableExclude'] ?? []),
            ];

            $fakerConfiguration = $configuration['data']['faker'] ?? [];

            try {
                $dumper = new Mysqldump(...$this->addCredentials($configuration, $settings));

                $dumper->setTableLimits($configuration['data']['limits'] ?? []);
                $dumper->setTableWheres($configuration['data']['wheres'] ?? []);
                if (!empty($fakerConfiguration)) {
                    $dumper->setTransformTableRowHook(function (string $tableName, array $row) use ($fakerConfiguration) {
                        // var_dump($tableName);
                        return $row;
                    });
                }

                if ($targetFilename === 'php://stdout') {
                    $dumper->start($targetFilename);
                } else {
                    $dumper->start($targetFilename . 'data.sql');
                    $exportFiles[] = $targetFilename . '.data.sql';
                }
            } catch (\Exception $exception) {
                $output->writeln('Could not create data file: ' . $exception->getMessage());
                return Command::FAILURE;
            }
        }

        if (!empty($exportFiles)) {
            // @todo Handle file Merge

            if (str_ends_with($targetFilename, '.gz')) {
                $output = CompressManagerFactory::create(CompressManagerFactory::GZIP);
            } else {
                $output = CompressManagerFactory::create(CompressManagerFactory::NONE);
            }

            $output->open($targetFilename);
            $output->write($this->getContent($exportFiles));

            var_dump($exportFiles);
        }

        return Command::SUCCESS;
    }

    protected function getContent(array $exportFiles)
    {
        return implode("\n", array_map(function (string $fileName) {
            return file_get_contents($fileName);
        }, $exportFiles));
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

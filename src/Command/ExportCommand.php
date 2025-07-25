<?php

namespace Lochmueller\CustomDatabaseExport\Command;

use Druidfi\Mysqldump\Compress\CompressManagerFactory;
use Druidfi\Mysqldump\Mysqldump;
use Lochmueller\CustomDatabaseExport\Compress\CompressWrapper;
use Lochmueller\CustomDatabaseExport\Dumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ExportCommand extends Command
{
    public function __construct(?string $name = null)
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
        $compression = $configuration['target']['compress'] ?? 'None';

        // Init Wrapper class
        CompressWrapper::start($compression);
        CompressManagerFactory::$methods[] = 'Wrapper';
        class_alias(CompressWrapper::class, 'Druidfi\\Mysqldump\\Compress\\CompressWrapper');

        if (!($configuration['structure']['skip'] ?? false)) {

            // Structure
            $settings = [
                'no-data' => true,
                'add-drop-table' => true,
                'compress' => 'Wrapper',
                'include-tables' => $configuration['structure']['tableInclude'] ?? [],
                'exclude-tables' => $configuration['structure']['tableExclude'] ?? [],
            ];

            try {
                $dumper = new Mysqldump(...$this->addCredentials($configuration, $settings));
                $dumper->start($targetFilename);
            } catch (\Exception $exception) {
                $output->writeln('Could not create structure file: ' . $exception->getMessage());
                return Command::FAILURE;
            }
        }

        if (!($configuration['data']['skip'] ?? false)) {
            // Data
            $settings = [
                'no-create-info' => true,
                'compress' => 'Wrapper',
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
                        if (array_key_exists($tableName, $fakerConfiguration)) {
                            return $this->modifyRecordByFaker($row, (array)$fakerConfiguration[$tableName]);
                        }
                        return $row;
                    });
                }

                $dumper->start($targetFilename);
            } catch (\Exception $exception) {
                $output->writeln('Could not create data file: ' . $exception->getMessage());
                return Command::FAILURE;
            }
        }

        if (!($dumper instanceof Dumper)) {
            return Command::FAILURE;
        }

        CompressWrapper::finish();

        return Command::SUCCESS;
    }

    protected function modifyRecordByFaker(array $row, array $configuration): array
    {
        foreach ($configuration as $field => $target) {
            if (array_key_exists($field, $row)) {
                $row[$field] = $this->modifyFieldByFaker($row[$field], $target);
            }
        }

        return $row;
    }

    protected function modifyFieldByFaker($originalValue, $target)
    {
        if (str_starts_with($target, 'FAKE:')) {
            return $this->getFakeValue($originalValue, substr($target, 5));
        }
        return $target;
    }

    protected function getFakeValue($originalValue, $fakerType)
    {
        $faker = \Faker\Factory::create();
        if (!is_callable([$faker, $fakerType])) {
            throw new \Exception('Faker ' . $fakerType . ' is not callable', 12368);
        }
        return $faker->$fakerType();
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
            return (string)getenv(substr($value, 4));
        }
        return $value;
    }

}

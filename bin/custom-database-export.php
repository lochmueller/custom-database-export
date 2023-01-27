#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Lochmueller\CustomDatabaseExport\Command\ExportCommand;
use Lochmueller\CustomDatabaseExport\Command\InitCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$command = new ExportCommand();
$application = new Application();
$application->add($command);
$application->add(new InitCommand());
$application->setDefaultCommand($command->getName());
$application->run();

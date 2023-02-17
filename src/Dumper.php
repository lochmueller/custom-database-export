<?php

namespace Lochmueller\CustomDatabaseExport;

use Druidfi\Mysqldump\Compress\CompressInterface;
use Druidfi\Mysqldump\Compress\CompressManagerFactory;
use Druidfi\Mysqldump\DumpSettings;
use Druidfi\Mysqldump\Mysqldump;

class Dumper extends Mysqldump
{
    static ?CompressInterface $ioStatic = null;


    // handle
    protected function ioStart(string $destination): void
    {
        if (static::$ioStatic === null) {
            static::$ioStatic = CompressManagerFactory::create($this->settings->getCompressMethod());
            static::$ioStatic->open($destination);
        }
    }

    protected function write(string $data): int
    {
        return static::$ioStatic->write($data);
    }

    // Handle
    protected function ioClose(): void
    {
    }

    public function ioFinish(): void
    {
        if (static::$ioStatic !== null) {
            static::$ioStatic->close();
            static::$ioStatic = null;
        }
    }

}
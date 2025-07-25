<?php

namespace Lochmueller\CustomDatabaseExport\Compress;

use Druidfi\Mysqldump\Compress\CompressInterface;
use Druidfi\Mysqldump\Compress\CompressManagerFactory;

class CompressWrapper implements CompressInterface
{
    private static CompressInterface $io;
    private static bool $alreadyOpen = false;

    public static function start(string $compression)
    {
        self::$io = CompressManagerFactory::create($compression);
    }

    public static function finish(): bool
    {
        self::$alreadyOpen = false;
        return self::$io->close();
    }

    public function open(string $filename): bool
    {
        if (!self::$alreadyOpen) {
            $result = self::$io->open($filename);
            self::$alreadyOpen = true;
            return $result;
        }
        return true;
    }

    public function write(string $str): int
    {
        return self::$io->write($str);
    }

    public function close(): bool
    {
        // Nothing to do here, because the stream is closed via finish method
        return true;
    }
}

<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\DateTimeToDateTimeInterfaceRector;
use Rector\Config\RectorConfig;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->parallel();
    $rectorConfig->paths([__DIR__.'/src', __DIR__.'/tests']);
    $rectorConfig->phpVersion(PhpVersion::PHP_74);
    $rectorConfig->phpstanConfig(__DIR__.'/phpstan-rector.neon');
    $rectorConfig->skip([
        DateTimeToDateTimeInterfaceRector::class,
    ]);
    $rectorConfig->importNames();
    $rectorConfig->parameters()->set(Option::IMPORT_SHORT_CLASSES, false);

    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        LevelSetList::UP_TO_PHP_74,
    ]);
};

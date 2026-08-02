<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src', __DIR__.'/tests'])
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPHPStanConfigs([__DIR__.'/phpstan-rector.neon'])
    ->withImportNames(importShortClasses: false)
    ->withComposerBased(phpunit: true)
    ->withPreparedSets(codeQuality: true)
    ->withPhpSets(php84: true);

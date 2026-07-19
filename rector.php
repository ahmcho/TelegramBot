<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\ClassMethod\NewInInitializerRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
    ]);

    $rectorConfig->skip([
        // Replaces clean `$x === null` checks with a verbose, unimported
        // FQCN `!$x instanceof \Fully\Qualified\ClassName` — worse
        // readability for no behavioral gain.
        FlipTypeControlToUseExclusiveTypeRector::class,
        // Would turn ReplyKeyboardBuilder's public `?ReplyKeyboardOptions
        // $options = null` constructor param non-nullable, a breaking
        // change to this published package's public API even though no
        // internal call site passes an explicit null.
        NewInInitializerRector::class,
    ]);
};

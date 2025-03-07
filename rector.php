<?php
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(TypedPropertyFromStrictConstructorRector::class);

    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::CODING_STYLE,
        SetList::MYSQL_TO_MYSQLI,
        SetList::EARLY_RETURN,
        LevelSetList::UP_TO_PHP_82
    ]);
};
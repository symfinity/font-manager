<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests;

final class FonttrioTestFixtures
{
    public const EDITORIAL_SOURCE = '@fonttrio/editorial';

    public static function directory(): string
    {
        return __DIR__ . '/resources/fonttrio';
    }
}
